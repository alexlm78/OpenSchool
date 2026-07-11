# Primeros Pasos para Desarrollo y Pruebas de Usuario

## Requisitos Previos

- PHP 8.3+
- Node.js y npm
- Composer
- PostgreSQL
- Docker o Podman (opcional, recomendado para BD local)

## Configuración del Entorno

1. Copia el archivo de entorno de ejemplo al archivo de entorno esperado por Laravel y genera una clave de aplicación:
   php artisan key:generate
2. Inicia PostgreSQL localmente:
   docker compose up -d
   # o
   podman compose up -d
3. Instala las dependencias:
   composer install
   npm install
4. Ejecuta las migraciones:
   php artisan migrate

### Configuración de base de datos por defecto

El proyecto ahora usa PostgreSQL por defecto a través del archivo de entorno de ejemplo:

- DB_CONNECTION=pgsql
- DB_HOST=localhost
- DB_PORT=5432
- DB_DATABASE=oschool
- DB_USERNAME=oschool
- DB_PASSWORD=oschool

Si Laravel se ejecuta en un contenedor dentro de la misma red de compose, usa `postgres` como `DB_HOST`.

## Iniciando la Aplicación

### Para Desarrollo (todos los servicios)

Ejecuta todos los servicios con un solo comando:
composer run dev

#### Esto inicia:
- Servidor de desarrollo de Laravel (php artisan serve)
- Servidor de desarrollo de Vite (npm run dev)
- Trabajador de colas (php artisan queue:listen --tries=1 --timeout=0) en segundo plano

#### Servicios Individuales

- Servidor de desarrollo de Laravel: php artisan serve (http://localhost:8000)
- Servidor de desarrollo de Vite: npm run dev
- Trabajador de colas: php artisan queue:listen --tries=1 --timeout=0
- Visor de logs: php artisan pail --timeout=0

### Accediendo a las Interfaces

Después de iniciar los servidores:
- Panel Admin: http://localhost:8000/admin
- Panel Docente: http://localhost:8000/docente
- Portal Alumno: http://localhost:8000/alumno
- Portal Apoderado: http://localhost:8000/apoderado

### Ejecutando Pruebas

- php artisan test o ./vendor/bin/phpunit
- Con cobertura: php artisan test --coverage

### Escenarios de Prueba de Usuario

Ver docs/architecture_es.md para los flujos de prueba detallados (matrícula, evaluaciones, notificaciones, multitenancy, API).

### Calidad de Código

- Corregir estilo: ./vendor/bin/pint
- Verificar estilo: ./vendor/bin/pint --test
</content>
