# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Running the app

Start the PHP built-in server from the project root:

```bash
php -S localhost:8000
```

Endpoints are then reachable at `http://localhost:8000/api/<resource>.php?action=<action>`.

**Reset the database** by deleting `data/beverages.sqlite` — it is auto-created and seeded from `data/init.sql` on the next request that calls `get_db()`.

## Architecture

Pure PHP backend, no framework. SQLite database. No frontend yet — only the API layer exists.

```
api/          One file per resource, handles all HTTP methods for that resource
includes/     Shared utilities loaded by every endpoint
data/         init.sql schema + beverages.sqlite (gitignored, auto-created)
```

### Request routing

Each `api/*.php` file routes internally using a `?action=` query parameter dispatched with a `match` statement. There is no central router. Example from `api/auth.php`:

```php
match ($action) {
    'register' => handle_register(),
    'login'    => handle_login(),
    ...
};
```

### Shared includes

Every endpoint begins with:

```php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
```

- **`includes/db.php`** — `get_db(): PDO` singleton. Creates and seeds the DB from `init.sql` on first call if the file does not exist.
- **`includes/auth.php`** — Session-based auth. Key guards: `require_auth()` (401 if not logged in), `require_admin()` (403 if not admin). `login_user($user)` writes to `$_SESSION`; `logout_user()` destroys it.
- **`includes/helpers.php`** — Response helpers: `json_response($data, $status)` and `json_error($message, $status)` both `exit` after sending. Input helpers: `json_body()`, `post()`, `get_param()`, `missing_field()`.

### Auth model

Two roles: `user` (default) and `admin`. Authentication uses PHP native sessions (`$_SESSION['user_id']`, `'username'`, `'email'`, `'role']`). The default seeded admin account is `admin@beverages.local` / `password`.

### Implementation status

Only `api/auth.php` is fully implemented. All other `api/*.php` files are stubs that return `501`. When implementing a new endpoint, follow the pattern in `api/auth.php`: validate with `missing_field()`, query via `get_db()`, respond with `json_response()` / `json_error()`.

## Database schema

Key tables and relationships (all in `data/init.sql`):

- `users` — `role` is `'user'` or `'admin'`
- `beverages` — linked to `categories`, `ingredients` (via `beverage_ingredients`), `allergens` (via `beverage_allergens`), `venues` (via `beverage_venues`)
- `groups` / `group_members` — user groups for shared preferences and shopping lists
- `shopping_lists` — can be personal (`group_id = NULL`) or group-owned
- `user_preferences` / `group_preferences` — ratings (1–5) and notes per beverage
- All foreign keys enforce `ON DELETE CASCADE` or `ON DELETE SET NULL`; `PRAGMA foreign_keys = ON` is set on every connection
