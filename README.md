# TskMngr

TskMngr is a lightweight task management app with a modern Laravel + Vue setup. I built it as a clean starting point for tracking work, organizing team flows, and keeping the interface simple without sacrificing polish.

The project leans on Laravel for the backend, Inertia + Vue on the frontend, and a fairly modern stack for fast iteration.

## What this project includes

- Laravel 13 backend with PHP 8.3
- Vue 3 + Inertia SPA experience
- Vite-based frontend build pipeline
- Tailwind CSS styling with a UI-friendly component structure
- Team-aware routing and dashboard flow
- Authentication and verification setup, ready for app growth
- Pest, PHPStan, and lint tooling already included

## Tech stack

- Backend: PHP, Laravel
- Frontend: Vue 3, Inertia, TypeScript
- Styling: Tailwind CSS
- Build tooling: Vite
- Testing: Pest, PHPStan

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
routes/              # Route definitions
storage/             # Runtime-generated files
tests/               # Automated tests
```

## Getting started

If you want to run this locally, here’s the usual flow:

1. Clone the repo
2. Install PHP dependencies:

```bash
composer install
```

3. Install frontend dependencies:

```bash
npm install
```

4. Set up your environment:

```bash
cp .env.example .env
php artisan key:generate
```

5. Run the database migrations:

```bash
php artisan migrate
```

6. Start the app:

```bash
composer run dev
```

This starts the Laravel backend and the Vite frontend together.

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

This repo feels like a practical foundation rather than a gimmick-heavy starter. It has the pieces a real app needs: auth, team-aware structure, a dashboard, and a clean frontend stack. I wanted something that can grow without feeling like a massive framework dump.

If you’re using it as a base, the easiest path is to build the actual product features on top of the existing auth and dashboard structure instead of reworking the whole app from scratch.

## License

This project is open-source and uses the MIT license.
