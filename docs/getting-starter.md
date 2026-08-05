# Getting Started for Development and User Testing

## Prerequisites

- PHP 8.3+
- Node.js 22 LTS & npm
- Composer
- PostgreSQL
- Docker or Podman (optional, recommended for local DB)

## Environment Setup

1. Copy the example environment file and generate an application key:
   cp env.example .env
   php artisan key:generate
2. Start PostgreSQL locally:
   docker compose up -d
   # or
   podman compose up -d
3. Install dependencies:
   composer install
   npm install
4. Run migrations:
   php artisan migrate

### Default database configuration

The project now uses PostgreSQL by default through `env.example`:

- DB_CONNECTION=pgsql
- DB_HOST=127.0.0.1
- DB_PORT=5432
- DB_DATABASE=oschool
- DB_USERNAME=oschool
- DB_PASSWORD=oschool

If Laravel runs in a container within the same compose network, use `postgres` as `DB_HOST`.

## Starting the Application

### For Development (all services)

Run all services with one command:
composer run dev

#### This starts:
- Laravel dev server (php artisan serve --host=127.0.0.1 --port=8000)
- Vite dev server (npm run dev)
- Queue worker (php artisan queue:work --tries=1 --timeout=0) in background

#### Individual Services

- Laravel dev server: php artisan serve --host=127.0.0.1 --port=8000 (http://127.0.0.1:8000)
- Vite dev server: npm run dev
- Queue worker: php artisan queue:work --tries=1 --timeout=0
- Log viewer: php artisan pail --timeout=0

### Accessing Interfaces

After starting servers:
- Admin Panel: http://127.0.0.1:8000/admin
- Docente Panel: http://127.0.0.1:8000/docente
- Alumno Portal: http://127.0.0.1:8000/alumno
- Apoderado Portal: http://127.0.0.1:8000/apoderado

### Running Tests

- php artisan test or ./vendor/bin/phpunit
- With coverage: php artisan test --coverage

### User Testing Scenarios

See docs/architecture.md for detailed testing flows (matrícula, evaluaciones, notificaciones, multitenancy, API).

### Code Quality

- Fix styling: ./vendor/bin/pint
- Check styling: ./vendor/bin/pint --test
