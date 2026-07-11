# OpenSchool

Educational management platform (multitenant) built with Laravel, aimed at managing multiple educational institutions with differentiated roles and workflows covering everything from enrollment to evaluations and academic tracking.

## Current goal

The immediate goal of the project is to deliver a functional version of the application within the scope defined for phases 1 and 2. The mobile API is out of scope for the current MVP and is considered a later phase.

## Stack (summary)

- Backend: Laravel 13.x (PHP 8.3+)
- Admin panels: Filament 5.6
- Reactive UI: Livewire 4.3
- RBAC: Spatie Laravel Permission (isolated per school)
- Frontend: Vite + Tailwind
- DB: PostgreSQL (target), SQLite for fast local development

## MVP scope

The current focus is on a first functional version of the system, prioritizing:

- Academic domain foundation and multitenancy
- Initial operational management from web panels
- Functional administration and teaching workflows
- Solid foundation to later evolve toward a mobile API and cloud deployment

Out of immediate scope:

- Production-ready mobile API
- Definitive multi-tenant cloud strategy
- Advanced horizontal scaling for multiple schools on a single platform

## Architecture (high level)

```mermaid
flowchart LR
  Browser[User] -->|HTTP| App[OpenSchool (Laravel)]
  App --> DB[(DB: PostgreSQL)]
  App --> Queue[(Queue Worker)]

  subgraph UI[Interfaces]
    Admin[/Admin Panel (Filament)\/]
    Teacher[/Teacher Panel (Filament)\/]
  end

  App --> Admin
  App --> Teacher
```

## Prerequisites

- PHP 8.3+
- Composer
- Node.js + npm
- PostgreSQL

For local development, SQLite can also be used when a quick bootstrap is needed.

## Installation (development)

### Option A: automatic setup

```bash
composer run setup
```

Note: the current `setup` script uses a simple local database for a quick bootstrap. If you're working aligned with the target environment, configure PostgreSQL in the environment file before migrating.

### Option B: step by step

```bash
composer install
npm install
```

Copy the example environment file to the environment file expected by Laravel, then:

```bash
php artisan key:generate
php artisan migrate
```

If you use PostgreSQL, configure at least these variables in the environment file before running migrations:

```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=openschool
DB_USERNAME=postgres
DB_PASSWORD=secret_placeholder
```

If you need an ephemeral development environment, you can also use SQLite.

## Running in development

Start all services (server + queue + logs + vite):

```bash
composer run dev
```

This starts:

- Laravel dev server: `php artisan serve` (http://localhost:8000)
- Vite dev server: `npm run dev`
- Queue worker: `php artisan queue:listen --tries=1 --timeout=0`
- Log viewer: `php artisan pail --timeout=0`

## Access (interfaces)

- Admin Panel (Filament): http://localhost:8000/admin
- Teacher Panel (Filament): http://localhost:8000/docente

Note: The Student/Guardian Livewire portals are considered work in progress (see `docs/current_state.md`).

## Deployment strategy

The initial strategy contemplates one instance per school in a self-hosted environment with native PostgreSQL. In the future, the product can evolve to a cloud model with two possible paths:

- One instance per school: greater operational and data isolation
- Multiple schools on a single instance: better infrastructure utilization

The current implementation is already oriented toward multitenancy via `school_id`, which allows evaluating both models later without rebuilding the core domain.

## First user (admin/teacher) to access Filament

Access to each panel depends on the user's role:

- Admin Panel requires the `admin` role
- Teacher Panel requires the `teacher` role

Quick example with Tinker (adjust emails/password):

```bash
php artisan tinker
```

```php
$school = \App\Models\School::create(['name' => 'Demo School', 'email' => 'demo@school.test']);

$user = \App\Models\User::create([
    'name' => 'Admin Demo',
    'email' => 'admin@school.test',
    'password' => 'password',
    'school_id' => $school->id,
]);

app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($school->id);

\Spatie\Permission\Models\Role::firstOrCreate([
    'name' => 'admin',
    'guard_name' => 'web',
    'school_id' => $school->id,
]);

$user->assignRole('admin');
```

## Tests

```bash
php artisan test
```

## Code quality

```bash
./vendor/bin/pint
./vendor/bin/pint --test
```

## Project docs

- This `README.md` serves as the entry point for onboarding and basic operations
- Getting started guide: `docs/getting-starter.md`
- Architectural/functional design: `docs/architecture.md`
- Current project status: `docs/current_state.md`
</content>
