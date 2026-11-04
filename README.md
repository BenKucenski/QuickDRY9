# QuickDRY8

A lightweight PHP 8 web framework: folder-based page routing, MySQL / MSSQL / Access connectors, database-to-code generation, JWT API security, mailer, and assorted utilities.

This repository is a Composer package (`benkucenski/quickdry8`). The `skeleton/` folder is a ready-to-run starter app that uses it.

## Start a new project

1. Copy `skeleton/` somewhere and rename it (it is not shipped inside the Composer package).
2. Run `composer install` in that folder.
3. Point your web server's document root at the folder:
   - Apache uses the included `.htaccess`.
   - IIS: rename `web.config.sample.txt` to `web.config`.
   - Quick local test: `php -S localhost:8000 index.php`.
4. Edit `env.php` to set `SESSION_NAME`, `DATA_FOLDER`, database credentials and other constants. `stubs/defines.php` lists every constant QuickDRY looks for.

`composer install` pulls QuickDRY from GitHub. It then runs `quickdry-publish`, which copies QuickDRY's `js/`, `css/`, `images/` and `controls/` into `<project>/QuickDRY/`, so existing URLs like `/QuickDRY/js/QuickDRY.js` keep working.

## Add QuickDRY to an existing project

Add this to the project's `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/BenKucenski/QuickDRY8" }
  ],
  "require": {
    "benkucenski/quickdry8": "dev-main"
  },
  "scripts": {
    "post-install-cmd": ["@php vendor/benkucenski/quickdry8/bin/quickdry-publish"],
    "post-update-cmd":  ["@php vendor/benkucenski/quickdry8/bin/quickdry-publish"]
  }
}
```

Then, in the existing project:

- Delete the old copied-in `QuickDRY/` folder. After `composer install` it will be recreated, holding only the published browser assets.
- Make sure your bootstrap (usually `modules.php`) requires `vendor/autoload.php`.
- Remove any `function Debug()` you define yourself. QuickDRY defines `Debug()`, `Exception()` and `Testing()` globally, so defining them again is a fatal "cannot redeclare" error. To run your own code after an error is logged, define `post_debug_handler()`.

## Layout

| Path | What it is |
| --- | --- |
| `QuickDRY/` | The framework (namespace `QuickDRY\`, PSR-4 autoloaded) |
| `QuickDRY/QuickDRY.php` | Global helpers and constants, loaded automatically by Composer |
| `bin/quickdry-publish` | Copies browser assets into the app |
| `skeleton/` | Starter application (not in the package) |
| `stubs/defines.php` | IDE stub of configurable constants (not loaded) |

## Application conventions

- Pages live in `pages/<path>/<name>.php` (class `pages\<path>\<name>` extending `QuickDRY\Web\BasePage`), with an optional `<name>.html.php` view.
- Master pages live in `<app root>/masterpages/`. The app root is the third argument to `Web::Init()`, normally `__DIR__` in `index.php`.
- Code generation: `php tasks_db/mysql2code.php -d<database>` or `php tasks_db/mssql2code.php -d<database>`.

## Optional extensions

| Connector | Needs |
| --- | --- |
| MySQL | `ext-mysqli` |
| MSSQL | `ext-sqlsrv` |
| MS Access | `ext-odbc` |

These are listed under `suggest`, so installation doesn't fail on machines that only use one database.
