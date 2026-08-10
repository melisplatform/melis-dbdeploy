# melis-dbdeploy

MelisDbDeploy is the database-migration runner of the Melis Platform. When a module is installed or updated, it finds that module's SQL deltas (`install/dbdeploy/*.sql`), applies the ones not yet run, and records what has been applied — so the database schema always stays in sync with the installed code.

## Getting Started

### Prerequisites

A module opts in to MelisDbDeploy by declaring `"extra": { "dbdeploy": true }` in its `composer.json` and providing its migration scripts under `install/dbdeploy/*.sql`.

### Installing

Run the composer command:
```
composer require melisplatform/melis-dbdeploy
```

## Running the code

MelisDbDeploy runs automatically during module install/update flows (driven by MelisCore and the platform installer). It is a low-level, standalone tool - it depends only on `phing/phing`, not on `melis-core`.

## Authors

* **Melis Technology** - [www.melisplatform.com](https://www.melisplatform.com/)

See also the list of [contributors](https://github.com/melisplatform/melis-dbdeploy/contributors) who participated in this project.


## License

This project is licensed under the OSL-3.0 License - see the [LICENSE.md](LICENSE.md) file for details
