# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Setup

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```

2. Environment setup:
   ```bash
   copy the example environment file to the environment file expected by Laravel
   php artisan key:generate
   touch database/database.sqlite
   ```

3. Database:
   ```bash
   php artisan migrate
   php artisan db:seed  # if seeders exist
   ```

## Common Commands

### Server & Background Processes
- Start dev server: `php artisan serve`
- Queue worker: `php artisan queue:listen --tries=1 --timeout=0`
- Log viewer: `php artisan pail --timeout=0`
- Vite dev server: `npm run dev`
- All together (dev): `composer run dev`

### Testing
- Run all tests: `php artisan test` or `./vendor/bin/phpunit`
- Run specific test: `php artisan test --filter=TestName`
- Run tests with coverage: `php artisan test --coverage`

### Code Quality
- Fix styling: `./vendor/bin/pint`
- Run Pint (dry run): `./vendor/bin/pint --test`

### Laravel Boosters (AI Development)
- Install: `composer require laravel/boost --dev`
- Setup: `php artisan boost:install`
- Provides 15+ AI agent tools for Laravel development

## Project Structure

- **app/** - Core application code
  - Models/ - Eloquent models (User, Student, Course, etc.)
  - Providers/ - Service providers
- **database/** - Migrations, factories, seeders
- **routes/** - Web routes (web.php) and console commands (console.php)
- **resources/** - Views, assets (managed by Vite)
- **tests/** - Unit and Feature tests
- **config/** - Laravel configuration files

## Key Technologies

- Laravel 13.x (PHP framework)
- Filament v5.6 (Admin panel)
- Livewire v4.3 (Reactive components)
- Spatie Laravel Permissions (Role/permission management)
- Vite (Asset bundling)
- Pest/PHPUnit (Testing)
- Laravel Pint (Code styling)
- Laravel Pail/Pao (Logging/monitoring)

## Development Notes

- Follow Laravel conventions for naming and structure
- Use Eloquent ORM for database interactions
- Blade templates for server-side rendering (or Livewire for reactive UI)
- Filament for admin interfaces
- Keep migrations and seeders updated for DB changes
- Run tests before committing changes