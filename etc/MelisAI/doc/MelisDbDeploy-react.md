---
title: MelisDbDeploy module — React back-office
package: melisplatform/melis-dbdeploy
doc_type: module-documentation-react
audience: [users, developers, ai]
language: en
module_version: unversioned
last_reviewed: 2026-08-19
maintainer: Melis Technology
keywords: [dbdeploy, migration, sql, deltas, changelog, deployment, infrastructure, react, back-office, schema, phing]
related_docs: [./MelisDbDeploy.md]
---

# MelisDbDeploy (React back-office) — Infrastructure Role Documentation (for AI)

> **What this is — and what it is not.** MelisDbDeploy has **no React back-office tool and no
> user interface** at all. There is **no brick** (`ui-react/`, `public/ui-react/brick.manifest.json`),
> **no** `config/react-api.php`, **no** `config/react.capabilities.php` and **no** `src/Controller/`.
> It is a **headless database-migration runner** that applies each module's SQL deltas
> (`install/dbdeploy/*.sql`). This document exists so that an AI building inside the platform
> understands MelisDbDeploy's **relationship to the React back-office** — which is **indirect**:
> the React tools depend on schema that dbdeploy migrations create, and dbdeploy is part of the
> deploy pipeline that ships the committed React build. For the full mechanism (data model,
> services, examples) see the [legacy doc](./MelisDbDeploy.md).
>
> **Audience**: consumed by the **MelisAI** MCP. **Status**: reviewed 2026-08-19.

---

## 0. Where this lives relative to the React back-office — read this first

MelisDbDeploy does **not** appear anywhere in `/melis-react`. It has no menu entry, no sidebar
section, no route, no toggle, no iframe. It runs during **install/update/deploy** flows, not
inside the running back-office (React or legacy). Its connection to the React UI is entirely
**indirect**, via two facts:

1. **It creates the tables/columns React tools read and write.** Every React tool (Users,
   Pages CMS, Sites, Media, etc.) reads and writes database tables. Those tables — and the
   columns that newer React features add (e.g. the `usr_rights_cache` / `usr_rights_cache_sig`
   columns on `melis_core_user` used by the React menu/capabilities resolver) — are created by
   the owning module's `install/dbdeploy/*.sql` deltas, which **MelisDbDeploy applies**. If a
   React tool errors with "table/column not found", an un-run migration is the usual cause.
2. **It is part of the deploy pipeline that ships the React build.** The committed React build
   (`vendor/melisplatform/melis-core/public/ui-react/`, served under `/MelisCore/ui-react/`,
   app at `/melis-react`) is shipped by the same deployment that runs schema migrations.
   MelisDbDeploy handles the **module-shipped SQL deltas**; **Flyway** handles the versioned
   platform migrations (`V*.sql`). They are complementary migration mechanisms in the same
   deploy step — see `CLAUDE.md` (deployment / `BUILD=true`).

> **In short:** MelisDbDeploy is invisible infrastructure. It never shows up in the React BO —
> it only makes the React BO's data layer possible and travels alongside the React build at
> deploy time.

---
---

# PART A — Functional Guide

## A1. Do I ever see this in the React back-office?

No. There is nothing to open, click, or configure. MelisDbDeploy has **no React tool, no page,
no button**. It is silent plumbing.

## A2. What it does for the React back-office (invisibly)

- When a module that a React tool depends on is **installed or updated**, MelisDbDeploy runs
  that module's SQL migration scripts so the tables/columns the React tool needs exist.
- It runs only the **new, not-yet-applied** deltas (tracked in a `changelog` table), so a
  migration is applied exactly once — never twice, never skipped.
- On a fresh **deployment**, it is part of the same pipeline that ships the committed React
  build, keeping the database schema in step with the React front-end that queries it.

## A3. Symptom to recognise

If a React tool shows empty data or a 500/"table doesn't exist" right after a module install,
update or deploy, an **un-run dbdeploy delta** (or a missing Flyway migration) is the typical
root cause — not the React code. Re-running the migration flow fixes it.

For everything else (what a delta is, when it runs, the services), see the
[legacy doc](./MelisDbDeploy.md).

---
---

# PART B — Technical Reference

## B1. React presence at a glance

| Aspect | Value |
|---|---|
| React brick | **None** — no `ui-react/`, no `public/ui-react/brick.manifest.json` |
| `react-api` endpoints | **None** — no `config/react-api.php`, no `MelisReactApi*Controller` |
| Capabilities | **None** — no `config/react.capabilities.php` |
| Controllers | **None** — no `src/Controller/` (headless, service-only module) |
| Menu / route / toggle | **None** — never surfaces in `/melis-react` |
| Relationship to React BO | **Indirect** — supplies the schema React tools use; runs in the deploy pipeline that ships the React build |

There is nothing React-specific to document as UI. What follows is the **real migration
mechanism**, so an AI knows what actually runs.

## B2. The real mechanism — SQL delta migrations

A module opts in with `"extra": { "dbdeploy": true }` in its `composer.json` and ships deltas
under **`install/dbdeploy/*.sql`**. Each file is a numerically-prefixed delta whose number
orders it. MelisDbDeploy **discovers** every `melisplatform/*` package's deltas, **copies** them
into a working cache, then **applies** the not-yet-run ones, recording each in a **changelog**
table so it runs exactly once. It is built on **Phing**'s `DbDeployTask` + `PDOSQLExecTask`.

### Discovery — `MelisDbDeployDiscoveryService` (runtime path)

`src/Service/MelisDbDeployDiscoveryService.php`:

- `setServiceManager()` pulls the Composer instance from `MelisAssetManagerModulesService`
  (`getComposer()`).
- `processing($module = null)`:
  - ensures the working dir `DOCUMENT_ROOT/../dbdeploy` exists (creates + chmods it),
  - gets `MelisDbDeployDeployService`; if `isInstalled()` is false → `install()` (creates the
    changelog table),
  - `copyDeltas($module)` then `applyDeltaPath(realpath('dbdeploy/data'))`.
- `copyDeltas()` iterates the Composer **local repository** canonical packages, keeps only those
  under vendor `melisplatform`, reads each package's `extra`, and for each copies
  `install/dbdeploy/*.sql` into `dbdeploy/data/` (`copyDeltasFromPackage()`). Passing a
  `$module` name (matched on `extra['module-name']`) restricts to a single module; otherwise all
  are gathered.

### Apply — `MelisDbDeployDeployService`

`src/Service/MelisDbDeployDeployService.php`:

- `__construct()` → `prepare()` reads DB creds from `config/autoload/platforms/*.php` (selected
  via `MELIS_PLATFORM` when several exist), builds a PDO `Laminas\Db\Adapter\Adapter`, sets the
  Phing include path (`vendor/phing/phing/classes/`), and configures a `\DbDeployTask`
  (`setAppliedBy('MelisDbDeploy')`, `setCheckAll(true)`, output file
  `melisplatform-dbdeploy.sql`, undo file `melisplatform-dbdeploy-reverse.sql`).
- `isInstalled()` → `describe changelog` (false on `PDOException`).
- `install()` → runs `data/changelog.sql` to create the changelog table.
- `changeLogCount()` → `SELECT COUNT(change_number) FROM changelog`.
- `applyDeltaPath($path)` → `Phing::startup()`, `chdir` into `dbdeploy/`, then `execute()`:
  `DbDeployTask->main()` generates the combined SQL, and `PDOSQLExecTask` executes it against
  the DB; the generated file is unlinked on success.

### The changelog table (the "applied once" ledger)

`data/changelog.sql` (table name fixed to `changelog` — required by the Phing task):

```sql
CREATE TABLE IF NOT EXISTS changelog (
  `change_number` BIGINT NOT NULL,
  `delta_set`     VARCHAR(10) NOT NULL,
  `start_dt`      TIMESTAMP NOT NULL,
  `complete_dt`   TIMESTAMP NULL,
  `applied_by`    VARCHAR(100) NOT NULL,
  `description`   VARCHAR(500) NOT NULL,
  PRIMARY KEY `Pkchangelog` (`change_number`, `delta_set`)
);
```

Model access: `MelisDbDeploy\Model\Table\ChangelogTable` (const `TABLE = 'changelog'`,
`PRIMARY_KEY = 'change_number'`), aliased `ChangelogTable` in `config/module.config.php`.

## B3. Where it runs in the deploy pipeline

Two entry points, neither of which involves the React UI:

- **Composer hook (CLI/deploy)** — `src/DbDeployOnComposerUpdate.php::postUpdate()`: after a
  Composer update it enumerates Melis packages (`MelisComposerDeploy\MelisComposer`), copies each
  module's `install/dbdeploy/*.sql` into `dbdeploy/data/`, then repeatedly runs
  `MelisDbDeployDeployService::applyDeltaPath()` until `changeLogCount()` equals the number of
  delta files (idempotent convergence).
- **Runtime service** — `MelisDbDeployDiscoveryService::processing()`, invoked by
  **MelisInstaller** (first install) and module install/update flows (Modules tool / marketplace,
  via MelisComposerDeploy).

In the platform's deployment terms, MelisDbDeploy applies the **module-shipped** SQL deltas,
while **Flyway** applies the versioned platform migrations (`V*.sql`) — both run in the same
deploy that ships the committed React build (`melis-core/public/ui-react/`). MelisDbDeploy has
**no React-specific behaviour**; it treats a React feature's schema delta exactly like any other
module's delta.

> ⚠ MelisDbDeploy has **no `melis-core` dependency** — it is a low-level standalone tool
> (requires only `phing/phing`). It is orchestrated by the installer and module flows, never
> called from React.

## B4. Quick code map

```
melis-dbdeploy/
├── composer.json                        → type melisplatform-module; extra.dbdeploy=true; requires phing/phing
├── config/
│   ├── module.config.php                → service_manager aliases (Deploy/Discovery services, ChangelogTable)
│   └── diagnostic.config.php
├── data/
│   └── changelog.sql                    → DDL for the `changelog` ledger table (applied-once tracking)
├── src/
│   ├── Module.php
│   ├── DbDeployOnComposerUpdate.php     → Composer post-update hook: copy deltas + apply until converged
│   ├── PhingListener.php                → Phing build listener
│   ├── ConfigFileNotFoundException.php
│   ├── Model/
│   │   ├── Changelog.php
│   │   └── Table/ChangelogTable.php     → TableGateway over `changelog`
│   └── Service/
│       ├── MelisDbDeployDiscoveryService.php  → discover melisplatform packages + copyDeltas()
│       └── MelisDbDeployDeployService.php      → Phing DbDeployTask + PDOSQLExecTask (apply deltas)
└── etc/   MelisAI/doc (this doc + legacy MelisDbDeploy.md) · MarketPlace
```

**No** `ui-react/`, **no** `public/ui-react/`, **no** `config/react-api.php`, **no**
`config/react.capabilities.php`, **no** `src/Controller/` — confirmed absent.

---

*Document for AI consumption (MelisAI MCP) — infrastructure role of `melisplatform/melis-dbdeploy`
relative to the React back-office. This module has **no React tool/UI**; its link to `/melis-react`
is indirect (schema for React tools + shared deploy pipeline). Full mechanism:
[./MelisDbDeploy.md](./MelisDbDeploy.md). Last reviewed 2026-08-19.*
