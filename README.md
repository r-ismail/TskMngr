# TskMngr — Laravel + Vue Task Manager with MCP

A task manager built on **Laravel 13** + **Vue 3 (Inertia)** with:

- **Task CRUD API** protected by **Laravel Sanctum** (`auth:sanctum` bearer tokens)
- **API-driven Vue frontend** (`/tasks` page calls `login`, `register`, list, create, update, delete)
- **MCP server** (`laravel/mcp`) exposing a `get-open-tasks` tool for AI clients
- Existing app features: teams, invitations, roles, settings, 2FA (Fortify)

## Requirements

- PHP ^8.3 (8.4 recommended), Composer 2
- Node 22+, npm
- SQLite (default) or any Laravel-supported database

## Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
touch database/database.sqlite   # if using sqlite
php artisan migrate

# 4. Run (three processes, or use `composer dev`)
php artisan serve      # http://localhost:8000
npm run dev            # Vite HMR
php artisan queue:listen
```

Or simply run the prepared script:

```bash
composer setup   # install + key + migrate + npm install + build
```

## Using the task manager

1. Open `http://localhost:8000/tasks`.
2. Register or log in — the page calls `POST /api/register` / `POST /api/login` and stores
   the Sanctum token in `localStorage`.
3. If you are already logged in through the web app, the page silently mints an API token
   via `POST /api/token` so you do not need to log in twice.
4. Create, toggle, edit and delete tasks — all through `GET|POST|PUT|DELETE /api/tasks`.

## API reference

Public:

| Method | Endpoint        | Description                    |
| ------ | --------------- | ------------------------------ |
| POST   | `/api/register` | Register + receive API token   |
| POST   | `/api/login`    | Log in + receive API token     |

Protected (`Authorization: Bearer <token>`):

| Method     | Endpoint          | Description              |
| ---------- | ----------------- | ------------------------ |
| GET        | `/api/user`       | Current user             |
| POST       | `/api/logout`     | Revoke current token     |
| GET        | `/api/tasks`      | List own tasks           |
| POST       | `/api/tasks`      | Create task              |
| GET        | `/api/tasks/{id}` | Show own task            |
| PUT/PATCH  | `/api/tasks/{id}` | Update own task          |
| DELETE     | `/api/tasks/{id}` | Delete own task          |

Web bridge (session auth):

| Method | Endpoint     | Description                              |
| ------ | ------------ | ---------------------------------------- |
| POST   | `/api/token` | Mint an API token for the logged-in user |

Task payload: `{ "title": "…", "description": "…", "is_completed": false }`.

Example with curl:

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"you@example.com","password":"secret"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')

curl http://localhost:8000/api/tasks -H "Authorization: Bearer $TOKEN"
```

## MCP (Model Context Protocol)

Install + scaffolding is already done (`composer require laravel/mcp`,
`php artisan vendor:publish --tag=ai-routes`,
`php artisan make:mcp-server TaskServer`,
`php artisan make:mcp-tool GetOpenTasksTool`).

- **Server**: `app/Mcp/Servers/TaskServer.php` (tool: `get-open-tasks-tool`)
- **Tool**: `app/Mcp/Tools/GetOpenTasksTool.php` — accepts `user_id` (integer, required),
  returns the user's tasks with `is_completed = false` via `Response::structured()`.
- **Registration** (`routes/ai.php`):

```php
Mcp::web('/mcp/tasks', TaskServer::class)->middleware(['auth:sanctum']);
Mcp::local('tasks', TaskServer::class);
```

Web endpoint (authenticated):

```bash
curl -X POST http://localhost:8000/mcp/tasks \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"get-open-tasks-tool","arguments":{"user_id":1}}}'
```

Local (stdio) endpoint for Claude Desktop / Cursor (`mcp.json`):

```json
{
    "mcpServers": {
        "tasks": {
            "command": "php",
            "args": ["artisan", "mcp:start", "tasks"],
            "cwd": "/absolute/path/to/TskMngr"
        }
    }
}
```

> Security note: the tool returns tasks for any supplied `user_id`. The web route is
> protected with `auth:sanctum`; the stdio server intentionally has direct access
> (standard for local MCP servers).

## Testing & quality

```bash
php artisan test          # Pest suite (API + MCP + existing feature tests)
composer lint             # Pint
composer types:check      # PHPStan
composer test             # config:clear + pint check + phpstan + tests
composer ci:check         # npm check + vue-tsc + full test script
npm run build             # production frontend build
```

## Project structure

```bash
app/                 # Laravel application logic
bootstrap/           # Bootstrap files
config/              # App configuration
database/            # Migrations, seeders, and DB setup
public/              # Public web assets
resources/
  js/                # Vue components, pages, composables, layouts
  views/             # Blade views
routes/              # Route definitions (web, api, ai for MCP, settings)
storage/             # Runtime-generated files
tests/               # Automated tests (Pest)
```

## Useful commands

```bash
php artisan serve
npm run dev
npm run build
php artisan test
composer run lint
php artisan config:clear
```

## Notes

This repo feels like a practical foundation rather than a gimmick-heavy starter.
It has the pieces a real app needs: auth, team-aware structure, a dashboard,
and a clean frontend stack. If you are using it as a base, the easiest path is to
build the actual product features on top of the existing auth and dashboard
structure instead of reworking the whole app from scratch.

## License

This project is open-source and uses the MIT license.

