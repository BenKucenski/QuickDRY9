# QuickDRY9

A lightweight PHP 8 web framework: folder-based page routing, MySQL / MSSQL / Access connectors, database-to-code generation, JWT API security, mailer, and assorted utilities.

This repository is a Composer package (`benkucenski/quickdry9`). The `skeleton/` folder is a ready-to-run starter app that uses it.

## Start a new project

1. Copy `skeleton/` somewhere and rename it (it is not shipped inside the Composer package).
2. Run `composer install` in that folder.
3. Point your web server's document root at the folder:
   - Apache uses the included `.htaccess`.
   - IIS: rename `web.config.sample.txt` to `web.config`.
   - Quick local test: `php -S localhost:8000 index.php`.
4. Edit `env.php` to set `SESSION_NAME`, `DATA_FOLDER`, database credentials and other constants. `stubs/defines.php` lists every constant QuickDRY looks for.
   - `DATA_FOLDER` must be the absolute path of a folder that already exists, is writable by the web server, and is outside the web root. QuickDRY never creates it or falls back to another folder; it only creates sub-folders (such as `logs/`) inside it. See `QuickDRY\Utilities\DataFolder`.

`composer install` pulls QuickDRY from GitHub. It then runs `quickdry-publish`, which copies QuickDRY's `js/`, `css/`, `images/` and `controls/` into `<project>/QuickDRY/`, so existing URLs like `/QuickDRY/js/QuickDRY.js` keep working.

## Add QuickDRY to an existing project

Add this to the project's `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/BenKucenski/QuickDRY9" }
  ],
  "require": {
    "benkucenski/quickdry9": "dev-main"
  },
  "scripts": {
    "post-install-cmd": ["@php vendor/benkucenski/quickdry9/bin/quickdry-publish"],
    "post-update-cmd":  ["@php vendor/benkucenski/quickdry9/bin/quickdry-publish"]
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

## Talking to the server from the browser

`QuickDRY/js/QuickDRY.HTTP.js` (published to `/QuickDRY/js/`) has two styles:

- `HTTP.Send(url, fields, files, options)` returns a Promise (it uses `fetch`) and can upload files. It resolves with the reply's JSON. It rejects with an `Error` when the HTTP status isn't OK, the reply isn't JSON, it says `success: false`, or it has an `error` key (what `HTTP::ExitJSON(['error' => ...])` sends). The error's message is the reply's `error`, `message` or `notice`, and `err.data` / `err.status` hold the reply and the status. It shows nothing itself.

  ```js
  try {
      const d = await HTTP.Send('/editor/thing', { action: 'save', name }, { image: [blob, 'pic.png'] });
  } catch (err) {
      HTTP.Notice('Error', err.message);
  }
  ```

  `files` is a Blob/File (sent as `file`) or `{ field: Blob | [Blob, filename] }`. `options.method` may be `'GET'` (fields go in the query string).

- `HTTP.Post(url, vars, callback, error_callback, dialog)` / `HTTP.Get(...)` is the older jQuery and callbacks style. It shows a wait dialog while it works if `dialog` is given, and shows errors itself unless `error_callback` handles them.

`HTTP.Notice(title, html)` and `HTTP.Wait(title, text, callback)` use the page's dialog controls from `QuickDRY/controls/` (`ctl_*`, `BS5/` or `SWAL/`) when the page includes them. Without them, notices fall back to SweetAlert if the page loads it, else `alert()`, and the wait dialog is skipped. `QuickDRY.js` uses the same fallbacks, so a page without the dialog controls no longer throws on its first error. With the `SWAL/` controls, `QuickDRY.DialogIsOpen('wait_dialog')` and `QuickDRY.CloseDialogIfOpen('wait_dialog')` act on the SweetAlert wait popup (and never close a notice or confirm), `QuickDRY.Delete` asks through `ConfirmDialog` instead of the browser's `confirm()`, and SweetAlert stays above and keeps focus over Bootstrap modals.

## Application conventions

- Pages live in `pages/<path>/<name>.php` (class `pages\<path>\<name>` extending `QuickDRY\Web\BasePage`), with an optional `<name>.html.php` view.
- Master pages live in `<app root>/masterpages/`. The app root is the third argument to `Web::Init()`, normally `__DIR__` in `index.php`.
- Code generation: `php tasks_db/mysql2code.php -d<database>` or `php tasks_db/mssql2code.php -d<database>`. See below.

## Code generation

The generator writes a model per table (`common/<db>/`), and for each table a JSON endpoint (`pages/json/<db>/<Table>/`, used by `QuickDRY.Read/Create/Update/ConfirmDelete`) and an admin page (`pages/manage/<db>/<Table>/`).

Only generate endpoints for the tables your screens use. Every generated endpoint can be called by any signed-in user, so tables like MFA codes, trusted devices, API keys or settings shouldn't have one:

```
php tasks_db/mysql2code.php -dmydb -t"invoice*,project,project_task" -x"invoice_adjustment" -g0
```

- `-t` tables to generate endpoints for, comma-separated (default: all). A table name or its class name (`user_mfa_code` or `UserMfaCode`), shell wildcards allowed, case ignored.
- `-x` tables to skip; wins over `-t`.
- `-g0` skips the `/manage` pages (default `-g1`).

In your own script: `$CodeGen->SetJSONTables(['invoice*'], ['invoice_adjustment']); $CodeGen->SetGenerateManage(false);` before `GenerateJSON()`. The generator never deletes files, so remove endpoints you no longer want by hand.

What the generated code enforces:

- **Endpoints** (`base/<Table>Base.php`, rewritten on every run; customize in `<Table>.php`): records load by primary key and must pass the model's `VisibleTo()` (and `CanDelete()` to delete); otherwise it's "Invalid ID". Only the columns in `Fields()` are taken from a request: the ones the generated form edits, minus the primary key, `user_id`, `*_by_id`, `*_at`, `*_file` and secret-looking columns. A new record's `user_id` is the signed-in user. `HiddenColumns()` (passwords, `*_hash`, tokens, secrets, keys) are never sent back. Foreign keys into tables with a `user_id` must point at rows the user can see (`References()`); add other checks by overriding `CheckReferences()`, `Fields()` or `HiddenColumns()`.
- **Models**: a table with a `user_id` column is visible to its owner; one without is visible only to `ROLE_ID_ADMIN`, which sees and changes everything. So never give customers `ROLE_ID_ADMIN`, and make `CurrentUser::Is()` return true only for roles the user really has.
- **Manage pages** list every row, so they require a signed-in `ROLE_ID_ADMIN` user (`CurrentUser::ID()` and `CurrentUser::Is([ROLE_ID_ADMIN])`). Pages generated before this check existed don't have it: regenerate them, or delete them.

## Page access

`Web::Exec()` checks who may open the page before running it, the way the legacy framework did:

1. `$web->PublicPaths` (e.g. `['/api']` for endpoints with their own keys): anyone.
2. Allowed by the visitor's navigation (`$web->SetNavigation($nav)`): yes. Matching ignores case, a trailing slash and dashes vs underscores. To make a generated `/json` or `/manage` page public, allow it for your signed-out role.
3. `/manage` (`$web->NavigationRequiredPaths`), when there's a navigation: no, signed in or not.
4. `/json` and `/manage` (`$web->ProtectedPaths`): signed-in users only. Signed-out visitors are sent to `$web->SignInPage` (default: the first argument to `Init()`) with `?continue=` set to the page they asked for.
5. A page on a secure master page (`$web->SetSecureMasterPages([...])`): signed in, and allowed by the navigation if there is one.
6. Anything else (a public master page, or none): anyone.

JSON requests get a 401/403 instead of a redirect. Rules 1-4 run before the page's `Init()`, rule 5 after it, because `Init()` sets the master page; don't change data in `Init()`. Command-line runs skip the check.

Signed in means `models\CurrentUser::ID()` (or a static `CurrentUser::$id`) is non-zero; change that with `$web->SetSignedInCheck(fn() => ...)`. `$web->AccessDecision()` returns what the rules decide without acting on it. A project that already checks access in its own `Web` subclass keeps working, and the framework check runs as well.

## Security helpers

- **Request verbs**: `QuickDRY.js` posts with a `verb` parameter (PUT, POST, DELETE, GET). On a GET request, `verb` can only pick a read-only handler (GET, FIND, HISTORY), so a link or `<img>` can't delete or save (`Web::ResolveVerb()`).
- **CSRF**: set `$web->RequireCSRFToken = true;` in `index.php` to require the session's token on anything that can change data. Pages send it by putting `<?php echo Csrf::MetaTag(); ?>` in `<head>` and loading `/QuickDRY/js/QuickDRY.Csrf.js` after jQuery. That script adds the token to same-origin jQuery requests, `fetch` and POST forms, and provides `PostLink(a)` / `PostTo(url)` for buttons that change data. Forms built without it can include `Csrf::HiddenInput()`. Without a valid token the request gets a JSON 403, or a redirect back with an error.
- **Passwords**: `Security::HashPassword()`, `Security::VerifyPassword()` and `Security::PasswordNeedsRehash()`. `VerifyPassword()` also accepts the unsalted MD5 hashes (`Security::MD5()`) older apps stored, so you can re-hash them at the next sign-in. Don't use `Security::MD5()` for passwords.
- **Rate limits**: `RateLimit::TooMany($bucket, $key, $max, $seconds)`, `RateLimit::Hit(...)` and `RateLimit::Clear(...)` for sign-in, sign-up, reset and MFA attempts. Counters are files under `RateLimit::$Folder` (default: the system temp folder), so it suits a single web server.
- **Skeleton defaults**: the session cookie is `HttpOnly`, `SameSite=Lax` and `Secure` over HTTPS. Errors are displayed only when `env.php` sets `SHOW_ERRORS`. The placeholder `CurrentUser::Is()` returns false.

## Optional extensions

| Connector | Needs |
| --- | --- |
| MySQL | `ext-mysqli` |
| MSSQL | `ext-sqlsrv` |
| MS Access | `ext-odbc` |

These are listed under `suggest`, so installation doesn't fail on machines that only use one database.
