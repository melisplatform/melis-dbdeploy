---
title: MelisDbDeploy module
package: melisplatform/melis-dbdeploy
doc_type: module-documentation
audience: [users, developers, ai]
language: en
module_version: unversioned
last_reviewed: 2026-06-08
maintainer: Melis Technology
keywords: [dbdeploy, database, migrations, deltas, schema, sql, install, update, phing, melis, core, foundation]
screenshots_dir: ./images
---

# MelisDbDeploy — Functional & Technical Documentation (for AI)

> **What this is.** MelisDbDeploy is the **database-migration runner** of the platform. When a
> module is installed or updated, MelisDbDeploy finds that module's SQL **deltas**
> (`install/dbdeploy/*.sql`), applies the ones not yet run, and records what's been applied — so
> the database schema always matches the installed code. It's part of the platform foundation
> (§0) and is invisible to end-users.
>
> **Two parts:** **[Part A — Functional Guide](#part-a--functional-guide)** ·
> **[Part B — Technical Reference](#part-b--technical-reference)** (developers/AI, with examples).
> Consumed by the **MelisAI** MCP. **No screenshots** — headless infrastructure. Reviewed 2026-06-08.

---

## 0. The MelisCore platform foundation (this family of modules)

> These modules are the **foundation of the Melis platform** — collectively referred to as
> **"MelisCore"**. *MelisCore* proper is the back-office heart everything depends on; the other
> four are the infrastructure that installs, deploys, serves and migrates the platform.

- **MelisCore** — the **back-office foundation** (login, users/rights, tools framework, dashboard,
  config, events, base services). **Every module depends on it.**
  → [MelisCore doc](../../../melis-core/etc/MelisAI/doc/MelisCore.md)
- **MelisAssetManager** — serves module assets & bundles them; module discovery.
  → [MelisAssetManager doc](../../../melis-asset-manager/etc/MelisAI/doc/MelisAssetManager.md)
- **MelisDbDeploy** *(this module)* — **applies database migrations** (each module's
  `install/dbdeploy/*.sql`).
- **MelisComposerDeploy** — runs Composer from inside the platform to install/update/remove modules.
  → [MelisComposerDeploy doc](../../../melis-composerdeploy/etc/MelisAI/doc/MelisComposerDeploy.md)
- **MelisInstaller** — the first-run installer wizard.
  → [MelisInstaller doc](../../../melis-installer/etc/MelisAI/doc/MelisInstaller.md)

**Dependency note:** MelisDbDeploy is a low-level standalone tool (depends only on **phing**); it
is driven by the installer and by module install/update flows. MelisCore and MelisInstaller orchestrate it.

---
---

# PART A — Functional Guide

## A1. What it does for you (invisibly)

You never open MelisDbDeploy. It is the reason **the database stays in sync with the code**:

- When you **install a module**, that module may need new database tables/columns. MelisDbDeploy
  runs the module's SQL migration scripts so those tables exist.
- When you **update a module**, it runs only the **new** scripts (the ones not applied yet), so you
  never run the same change twice and never miss one.

In short: it's the automatic "update my database to match what's installed" mechanism. If a freshly
installed/updated module errors with "table/column not found", an un-run migration is the usual cause.

---
---

# PART B — Technical Reference

## B1. Metadata & dependencies

| Item | Value |
|---|---|
| Package | `melisplatform/melis-dbdeploy` (module `MelisDbDeploy`) · namespace `MelisDbDeploy\` |
| Requires | `phing/phing` (`2.17.4`) — no `melis-core` dependency (standalone tool) |

## B2. How migrations work (the dbdeploy convention)

- A module opts in by declaring `"extra": { "dbdeploy": true }` in its `composer.json`.
- Its migrations live in **`install/dbdeploy/*.sql`** — each file is a **delta** with a numeric
  prefix that orders it (e.g. `23051701_*.sql`).
- MelisDbDeploy **discovers** every dbdeploy module's deltas, **copies** them into a working
  location, then **applies** the deltas that haven't been run yet, recording each in a **changelog**
  so it's applied exactly once. (Built on **phing**'s dbdeploy task.)

## B3. Services (with examples)

**`MelisDbDeployDiscoveryService`** — find & gather every module's deltas:

```php
$discovery = $sm->get(\MelisDbDeploy\Service\MelisDbDeployDiscoveryService::class);
$discovery->setComposer($composer);
$discovery->processing();   // discover dbdeploy modules and copyDeltas() their *.sql
```

Methods: `setServiceManager`, `processing`, `copyDeltas`, `getComposer`/`setComposer`.

**`MelisDbDeployDeployService`** — apply the gathered deltas:

```php
$deploy = new \MelisDbDeploy\Service\MelisDbDeployDeployService(/* db params */);
if (!$deploy->isInstalled()) { $deploy->install(); }   // set up the changelog table
$count = $deploy->changeLogCount();                    // how many deltas applied so far
$deploy->applyDeltaPath($pathToDeltas);                // run the not-yet-applied deltas
```

Methods: `__construct`, `isInstalled`, `install`, `changeLogCount`, `applyDeltaPath`.

## B4. When it runs

It is invoked by **MelisInstaller** (first install) and by the module install/update flows
(triggered through the back-office Modules tool / marketplace and Composer hooks). It is not a
user-facing tool and has no controllers.

## B5. Quick code map

```
melis-dbdeploy/
├── composer.json                 → dep: phing
├── src/   Service/ (MelisDbDeployDiscoveryService, MelisDbDeployDeployService) · Model/
└── etc/   MarketPlace + MelisAI/doc (this doc)
```

---

*Document for AI consumption (MelisAI MCP) — `melisplatform/melis-dbdeploy`. Part A = functional;
Part B = technical with examples. Part of the MelisCore platform foundation. Last reviewed 2026-06-08.*
