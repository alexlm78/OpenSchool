# OpenSchool

Plataforma de gestión educativa (multitenant) construida con Laravel, orientada a administrar múltiples instituciones educativas con roles diferenciados y flujos que cubren desde matrícula hasta evaluaciones y seguimiento académico.

## Objetivo actual

El objetivo inmediato del proyecto es entregar una version funcional del aplicativo con el alcance definido en las fases 1 y 2. El API movil queda fuera del MVP actual y se considera una fase posterior.

## Stack (resumen)

- Backend: Laravel 13.x (PHP 8.3+)
- Admin panels: Filament 5.6
- UI reactiva: Livewire 4.3
- RBAC: Spatie Laravel Permission (aislado por escuela)
- Frontend: Vite + Tailwind
- DB: PostgreSQL (objetivo), SQLite para desarrollo rapido

## Alcance del MVP

El foco actual esta en una primera version funcional del sistema, priorizando:

- Base del dominio academico y multitenancy
- Gestion operativa inicial desde paneles web
- Flujos funcionales de administracion y trabajo docente
- Base solida para evolucionar luego hacia API movil y despliegue cloud

Queda fuera del alcance inmediato:

- API movil productiva
- Estrategia cloud multi-tenant definitiva
- Escalado horizontal avanzado para multiples escuelas en una sola plataforma

## Arquitectura (alto nivel)

```mermaid
flowchart LR
  Browser[Usuario] -->|HTTP| App[OpenSchool (Laravel)]
  App --> DB[(DB: PostgreSQL)]
  App --> Queue[(Queue Worker)]

  subgraph UI[Interfaces]
    Admin[/Admin Panel (Filament)\/]
    Teacher[/Docente Panel (Filament)\/]
  end

  App --> Admin
  App --> Teacher
```

## Prerrequisitos

- PHP 8.3+
- Composer
- Node.js + npm
- PostgreSQL

Para desarrollo local tambien puede usarse SQLite cuando se necesite un arranque rapido.

## Instalación (desarrollo)

### Opción A: setup automático

```bash
composer run setup
```

Nota: el script actual de `setup` usa una base local simple para bootstrap rapido. Si vas a trabajar alineado al entorno objetivo, configura PostgreSQL en `.env` antes de migrar.

### Opción B: paso a paso

```bash
cp .env.example .env
php artisan key:generate
composer install
npm install
php artisan migrate
```

Si usas PostgreSQL, configura al menos estas variables en `.env` antes de correr migraciones:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=openschool
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Si necesitas un entorno efimero de desarrollo, tambien puedes usar SQLite.

## Ejecutar en desarrollo

Levanta todos los servicios (server + queue + logs + vite):

```bash
composer run dev
```

Esto inicia:

- Laravel dev server: `php artisan serve` (http://127.0.0.1:8000)
- Vite dev server: `npm run dev`
- Queue worker: `php artisan queue:listen --tries=1 --timeout=0`
- Log viewer: `php artisan pail --timeout=0`

## Accesos (interfaces)

- Admin Panel (Filament): http://127.0.0.1:8000/admin
- Docente Panel (Filament): http://127.0.0.1:8000/docente

Nota: Los portales Livewire de Alumno/Apoderado se consideran trabajo en progreso (ver `docs/current_state.md`).

## Estrategia de despliegue

La estrategia inicial contempla una instancia por escuela en entorno self-hosted con PostgreSQL nativo. A futuro, el producto puede evolucionar a una modalidad cloud con dos caminos posibles:

- Una instancia por escuela: mayor aislamiento operativo y de datos
- Multiples escuelas en una sola instancia: mejor aprovechamiento de infraestructura

La implementacion actual ya esta orientada a multitenancy por `school_id`, lo que permite evaluar ambos modelos mas adelante sin rehacer el dominio principal.

## Primer usuario (admin/docente) para entrar a Filament

El acceso a cada panel depende del rol del usuario:

- Admin Panel requiere rol `admin`
- Docente Panel requiere rol `teacher`

Ejemplo rápido con Tinker (ajusta emails/contraseña):

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

## Calidad de código

```bash
./vendor/bin/pint
./vendor/bin/pint --test
```

## Docs del proyecto

- Este `README.md` funciona como punto de entrada para onboarding y operacion basica
- Guía de arranque: `docs/getting-starter.md`
- Diseño arquitectural/funcional: `docs/architecture.md`
- Estado actual del proyecto: `docs/current_state.md`
