# Plan de Implementación de OpenSchool (Reutilizable y Ejecutable)

Este plan está diseñado para que un agente de código pueda implementarlo sin ambigüedad. Está organizado por **líneas de trabajo (workstreams)**, con **tareas atómicas**, **archivos objetivo** y **criterios de aceptación**.

## 0) Contexto y Alineación (Obligatorio)

### Hechos Verificados del Repositorio
- Laravel **13.8** (no 11): ver dependencias en `composer.json`.
- `routes/api.php` no existe hoy (solo `routes/web.php` y `routes/console.php`).
- La multitenancy actual tiene un riesgo crítico: el scope lee `school_id` desde el input del request (no confiable).

### Principios Arquitectónicos para Ejecutar Este Plan
1. **Seguridad primero**: la multitenancy debe ser server-side, no derivada del input del cliente.
2. **Un único mecanismo de tenancy**: evitar duplicación (global scope + traits + scopes paralelos).
3. **Dominio explícito**: decisiones como "Student es un User con un rol + perfil" deben implementarse y probarse.
4. **Gates de calidad desde el principio**: las pruebas de aislamiento por escuela deben existir antes de agregar portales/API.

### Definición Global de Hecho (DoD) por Tarea
- `php artisan test` pasa.
- No hay fugas de datos entre escuelas (incluyendo pruebas).
- Las acciones sensibles (descarga de archivos, calificación, matrícula) están protegidas por Policies/Gates.
- No se introducen dependencias no usadas ni configuraciones a medio hacer.

## 1) Línea de Trabajo A - Multitenancy Segura y Unificada (Bloqueante)

### A1. Definir una Única Fuente de Verdad para el Tenant (`TenantContext`)
- Objetivo: asegurar que `school_id` se resuelva **server-side** (auth/sesión en web/Filament, token en API, opcionalmente subdominio en el futuro).
- Tareas:
  - Crear un servicio `TenantContext` (en el contenedor) con:
    - `getSchoolId(): ?int`
    - `setSchoolId(?int $schoolId): void`
    - `requireSchoolId(): int` (lanza excepción si falta y es requerido)
  - Crear middleware `SetTenantFromAuth`:
    - Si `auth()->check()` y `auth()->user()->school_id`, setear el tenant.
    - Si no hay auth, el tenant queda null.
  - Registrar middleware para:
    - Web/Filament (para `/admin` y `/docente`).
    - API futura (Sanctum).
- Archivos objetivo:
  - `app/Http/Middleware/SetTenantFromAuth.php` (nuevo)
  - `bootstrap/app.php` o `app/Http/Kernel.php` (según Laravel 13) para registrar middleware
  - `app/Support/TenantContext.php` (nuevo) o `app/Tenancy/TenantContext.php` (nuevo)
- Criterios de aceptación:
  - En un request autenticado, el tenant queda seteado sin depender del input del request.

### A2. Corregir el Scope Global de Tenancy (Prohibir `Request::input('school_id')`)
- Objetivo: eliminar el escape de tenant.
- Tareas:
  - Modificar `app/Models/Scopes/TenancyScope.php` para usar **solo** `TenantContext`.
  - Prohibir resolver el tenant desde query/body input.
  - Asegurar que el scope solo se aplique a tablas con `school_id` (usando `Schema::hasColumn`).
- Archivos objetivo:
  - `app/Models/Scopes/TenancyScope.php`
  - `app/Providers/AppServiceProvider.php` (mantener solo un `Model::addGlobalScope(new TenancyScope())`)
- Criterios de aceptación:
  - Un usuario autenticado no puede cambiar `school_id` enviando `?school_id=...`.
  - Las pruebas demuestran que la "Escuela A" no puede ver datos de la "Escuela B".

### A3. Eliminar Duplicación de Scope (Limpieza)
- Objetivo: un único enfoque, mínimo y entendible.
- Tareas:
  - Eliminar `SchoolScopeTrait` de los modelos (o convertirlo en un wrapper vacío/no-op si se necesita compatibilidad temporal).
  - Eliminar/archivar el scope alternativo `app/Scopes/SchoolScope.php` si no se usa.
  - Confirmar que el único filtro es el `TenancyScope` global.
- Archivos objetivo:
  - `app/Traits/SchoolScopeTrait.php`
  - Modelos que lo usan (por ejemplo `app/Models/User.php`, `app/Models/AcademicPeriod.php`, `app/Models/CourseTemplate.php`)
  - `app/Scopes/SchoolScope.php`
- Criterios de aceptación:
  - No hay doble filtrado, no faltan dependencias, y toda consulta multi-tenant usa el scope global.

### A4. Integrar Spatie Permissions (Teams) con `school_id`
- Objetivo: aislar roles/permisos por escuela usando Teams.
- Tareas:
  - Validar el contrato `team_resolver` (debe implementar la interfaz/contrato requerido por Spatie v7).
  - Asegurar que en cada request, el "team id" se setea al `school_id` actual.
    - Ruta recomendada: middleware que, después de setear el tenant, ejecuta `setPermissionsTeamId($schoolId)`.
  - Revisar migraciones extra para `roles.school_id` / `permissions.school_id` para evitar duplicar el concepto con `team_foreign_key`.
    - Decisión recomendada: usar **solo** `team_foreign_key = school_id` y evitar columnas redundantes salvo que agreguen valor claro.
- Archivos objetivo:
  - `config/permission.php`
  - `app/Permission/SchoolTeamResolver.php`
  - Middleware de tenancy (extenderlo para también setear el team)
  - Migración `database/migrations/2026_05_28_112246_add_school_id_to_roles_and_permissions_table.php` (revisar necesidad)
- Criterios de aceptación:
  - Un rol/permiso asignado en la Escuela A no es visible ni usable en la Escuela B.
  - Las pruebas cubren creación/asignación de roles entre dos escuelas.

### A5. Pruebas Obligatorias de Multitenancy (Gate a Nivel de Proyecto)
- Objetivo: bloquear regresiones de seguridad.
- Tareas:
  - Crear pruebas que creen dos escuelas y usuarios, y verifiquen aislamiento en al menos:
    - `CourseTemplate` / `CourseOffering`
    - `Enrollment`
    - `Evaluation` / `Submission` / `Grade`
  - Asegurar que cambiar de usuario (A vs B) no mezcla resultados de consultas.
- Archivos objetivo:
  - `tests/Feature/Tenancy/*.php` (nuevo) o `tests/Feature/TenancyTest.php`
- Criterios de aceptación:
  - Las pruebas fallan si se reintroduce `Request::input('school_id')`.

## 2) Línea de Trabajo B - Dominio y Modelos Completos (Consistencia + Mantenibilidad)

### B1. Decisión de Identidad: "Student/Teacher/Guardian"
- Decisión recomendada (por consistencia con las migraciones actuales):
  - **Student/Teacher/Guardian son perfiles** y el actor autenticado es `User`.
  - "Alumno" = `users` con el rol `student` + (opcional) perfil en la tabla `students`.
- Tareas:
  - Documentar la decisión en el propio código mediante nombres y relaciones (sin comentarios).
  - Normalizar relaciones:
    - `Enrollment::student()` actualmente retorna `User` (ok), pero definir `StudentProfile` si se necesita información extra.
  - Definir políticas de acceso basadas en roles + ownership.
- Archivos objetivo:
  - `app/Models/Enrollment.php`
  - `app/Models/Student.php`, `app/Models/Teacher.php`, `app/Models/Guardian.php`
  - `app/Models/User.php`
- Criterios de aceptación:
  - El modelo de identidad es consistente entre UI (Filament), portales y API.

### B2. Completar Modelos Vacíos y Relaciones Académicas Centrales
- Tareas (mínimo):
  - Completar `CourseOffering`, `SubmissionFile`, y cualquier otro modelo stub con:
    - `$fillable`, `casts()`, relaciones (`belongsTo`, `hasMany`, `belongsToMany`)
  - Confirmar que todo modelo con `school_id` está filtrado por `TenancyScope`.
- Archivos objetivo:
  - `app/Models/CourseOffering.php`
  - `app/Models/SubmissionFile.php`
  - Modelos relacionados (`TeachingAssignment`, `OfferingTimeSlot`, `Evaluation`, `Submission`, `Grade`, etc.)
- Criterios de aceptación:
  - Filament puede listar/crear registros sin errores de relación.

## 3) Línea de Trabajo C - Reglas de Negocio Críticas (MVP1)

### C1. Matrícula: Capacidad y Conflictos de Horario (Dominio + UI)
- Objetivo: bloquear sobre-matrícula y choques de horario.
- Reglas:
  - Capacidad: `enrollments(active)` para `course_offering_id` no debe exceder `course_offerings.capacity` (si `capacity=0`, decidir entre "sin cupos disponibles" o "ilimitado" y documentar la regla; recomendación: `0 = sin cupos disponibles`).
  - Conflicto: un estudiante no puede matricularse en dos ofertas con valores de `TimeSlot` superpuestos el mismo día.
- Implementación recomendada:
  - Servicio de dominio `EnrollStudent` con transacción de BD.
  - Consultas con locking o verificación consistente para evitar condiciones de carrera (si la base de datos lo soporta).
  - Validación reutilizable para Filament + Portales + API.
- Archivos objetivo:
  - Servicio: `app/Domain/Enrollment/EnrollStudent.php` (nuevo) o `app/Actions/EnrollStudent.php`
  - Formulario/tabla de Matrícula en Filament: `app/Filament/Resources/Enrollments/...`
  - Pruebas: `tests/Feature/Enrollment/*.php`
- Criterios de aceptación:
  - Camino feliz: la matrícula tiene éxito.
  - Caso sobre-capacidad: error claro.
  - Caso conflicto de horario: error claro.

### C2. Descarga Segura de Archivos (`SubmissionFile`)
- Objetivo: archivos en almacenamiento privado autorizados por Policies.
- Reglas:
  - Solo el estudiante dueño (o el docente asignado, o el admin de la escuela) puede descargar.
- Implementación recomendada:
  - Almacenar archivos en un disco privado.
  - Servir rutas de descarga desde un controlador que valide la Policy y use `Storage::download`.
- Archivos objetivo:
  - `app/Policies/SubmissionFilePolicy.php` (nuevo)
  - `app/Http/Controllers/SubmissionFileDownloadController.php` (nuevo)
  - `routes/web.php` (agregar ruta protegida)
  - `config/filesystems.php` (revisar nombramiento del disco; hoy `local` apunta a `storage/app/private`)
- Criterios de aceptación:
  - Un usuario de otra escuela obtiene 404/403.
  - Un estudiante no puede descargar archivos de otro estudiante.

### C3. Evaluaciones/Entregas/Calificación (Mínimo Extremo a Extremo)
- Objetivo: flujo completo docente -> alumno -> docente -> alumno.
- Tareas:
  - Confirmar relaciones:
    - `Evaluation` -> detalles polimórficos (assignment/exam/project)
    - `Submission` -> `SubmissionFiles`
    - `Grade` -> `Submission`
  - Implementar estados mínimos (`draft/published`, `on-time/late`) si aplica.
  - Garantizar Policies: un docente solo administra sus propias secciones.
- Criterios de aceptación:
  - El flujo crítico del documento de arquitectura puede ejecutarse desde la UI.

## 4) Línea de Trabajo D - Filament Listo para Operar (Admin + Docente)

### D1. RBAC y Visibilidad por Rol
- Objetivo: cada panel muestra solo lo que está permitido.
- Tareas:
  - Panel Admin:
    - Definir permisos: gestionar catálogo, usuarios, roles, períodos, cursos, matrículas.
  - Panel Docente:
    - Listar solo `CourseOfferings` donde el docente tiene una `TeachingAssignment`.
    - Crear evaluaciones solo dentro de sus propias ofertas.
    - Ver/calificar entregas solo de sus propias evaluaciones/ofertas.
  - Endurecer el acceso basado en roles:
    - Middleware de panel aplicado a rutas autenticadas (`authMiddleware`) para que no se bloquee el login del panel.
    - `User` implementa `FilamentUser::canAccessPanel()` para controlar el acceso al panel cuando `APP_ENV != local`.
    - Resources base: `AdminResource` / `DocenteResource` con `canViewAny()` / `canCreate()` por rol.
    - Migrar los Resources existentes para que extiendan la base correcta por panel.
  - Implementar `->query()` / `getEloquentQuery()` por Resource para filtrar datos.
- Archivos objetivo:
  - `app/Providers/Filament/AdminPanelProvider.php`
  - `app/Providers/Filament/DocentePanelProvider.php`
  - Resources en `app/Filament/Resources/**` y `app/Filament/DocenteResources/**`
- Criterios de aceptación:
  - Un docente no puede ver registros fuera de sus secciones.
  - Un admin no puede ver datos de otra escuela.

### D2. Sembrar Roles/Permisos para un Bootstrap Más Rápido (Opcional pero Recomendado)
- Objetivo: bootstrap de permisos.
- Tareas:
  - Crear un seeder que cree roles base (`admin`, `docente`, `student`, `guardian`) por escuela.
  - Asignar permisos a los roles.
- Criterios de aceptación:
  - Una instalación limpia permite entrar y operar los paneles sin configuración manual extensa.

## 5) Línea de Trabajo E - Portales Livewire (Alumno y Apoderado)

### E1. Portal Alumno
- Funcionalidades mínimas:
  - Ver cursos matriculados en el período activo.
  - Ver evaluaciones por curso (pendientes/calificadas).
  - Crear `Submission` con archivos.
  - Ver feedback/calificación.
- Archivos objetivo:
  - `app/Livewire/**` (nuevo)
  - `routes/web.php` (agregar rutas `/alumno`)
  - `resources/views/**` (si aplica)
- Criterios de aceptación:
  - Un alumno solo ve y opera sobre sus propios datos y escuela.

### E2. Portal Apoderado
- Funcionalidades mínimas:
  - Vinculación de estudiantes (por código o solicitud).
  - Vista consolidada de calificaciones/evaluaciones.
- Criterios de aceptación:
  - Un apoderado solo ve hijos vinculados de su propia escuela.

## 6) Línea de Trabajo F - API Móvil (Sanctum) + Contratos Estables

### F1. Preparación de la API
- Tareas:
  - Agregar `routes/api.php`.
  - Instalar/configurar Sanctum (si no está ya instalado en el repositorio).
  - Implementar middleware de tenancy para la API (token -> usuario -> `school_id` -> `TenantContext`).
- Archivos objetivo:
  - `routes/api.php` (nuevo)
  - `app/Http/Controllers/Api/**` (nuevo)
  - `config/sanctum.php` (si está instalado)
- Criterios de aceptación:
  - Los tokens funcionan y respetan tenancy/policies.

### F2. Endpoints MVP
- Endpoints recomendados:
  - Auth: login/token, me
  - Cursos: listar, detalle (matrículas)
  - Evaluaciones: listar por curso, detalle
  - Entregas: crear, listar, detalle
  - Calificaciones: listar por estudiante
- Criterios de aceptación:
  - Respuestas 403/404 correctas fuera del tenant o sin permiso.

## 7) Línea de Trabajo G - Notificaciones, Colas y Observabilidad

### G1. Eventos y Jobs (Idempotentes)
- Tareas:
  - Eventos: `EvaluationPublished`, `SubmissionCreated`, `GradePublished`.
  - Listeners en cola que notifiquen a alumnos/apoderados.
  - Idempotencia: evitar duplicados en reintentos (clave de idempotencia por evento/usuario).
- Criterios de aceptación:
  - `queue:work` procesa jobs y no duplica notificaciones en reintentos.

### G2. Correlation ID en Logs
- Tareas:
  - Middleware que asigna `X-Request-Id` si falta.
  - Inyectar `request_id` y `school_id` en los logs (contexto).
  - Propagar `request_id` a los jobs.
- Criterios de aceptación:
  - Los logs incluyen consistentemente `request_id` y `school_id`.

## 8) Línea de Trabajo H - Pruebas, Análisis Estático y CI (Gates)

### H1. Pruebas de Dominio y Seguridad
- Obligatorio:
  - Tenancy (A5)
  - Matrícula (capacidad + conflicto)
  - Descarga de archivos (Policy)
  - Docente limitado a sus propias secciones

### H2. Estilo + Análisis Estático
- Tareas:
  - Pint configurado y ejecutado en CI.
  - PHPStan o Psalm agregado y configurado.
- Criterios de aceptación:
  - CI falla ante errores o regresiones de tenancy.

### H3. Pipeline de CI
- Jobs mínimos:
  - Composer install
  - Pint (check)
  - Pruebas
  - Dry-run de migraciones (`migrate:fresh` en sqlite)

## 9) Línea de Trabajo I - Despliegue y Operaciones

### I1. Configuración de Entorno
- Tareas:
  - Archivo de entorno de ejemplo alineado con discos/colas/logs.
  - Separación dev/staging/prod.

### I2. Workers y Scheduler
- Tareas:
  - Documentar y configurar `queue:work` / supervisor.
  - Configurar el scheduler (`schedule:run`) para tareas futuras (recordatorios, limpieza).

### I3. Backups y DR (Recuperación ante Desastres)
- Tareas:
  - Estrategia de backup para la base de datos y el almacenamiento privado.
  - Procedimiento de restauración.

## 10) Definición de Release Funcional

### Release R1 (MVP1 "Funcional y Seguro")
- Endurecimiento completo de tenancy (Línea de Trabajo A)
- Paneles Admin + Docente operativos (Línea de Trabajo D)
- Matrícula con reglas (Línea de Trabajo C1)
- Evaluaciones/Entregas/Calificación mínimas (Línea de Trabajo C3)
- Descarga segura de archivos (Línea de Trabajo C2)
- Pruebas y gates mínimos (Línea de Trabajo H)

### Release R2 (Portales Web)
- Portal Alumno + Apoderado (Línea de Trabajo E)
- Notificaciones base (Línea de Trabajo G1)

### Release R3 (API Móvil + Búsqueda)
- API con Sanctum (Línea de Trabajo F)
- Scout/Meilisearch (si el equipo decide agregarlo y operarlo en el repositorio)

## Checklist de Ejecución (Orden Recomendado)

- [x] Ejecutar toda la Línea de Trabajo A (A1-A5) antes de tocar UI/API
- [~] Completar modelos y relaciones (B1-B2)
- [~] Implementar reglas críticas (C1-C3)
- [x] Endurecer paneles (D1)
- [ ] Sembrar roles/permisos (D2)
- [ ] Portales (E1-E2)
- [ ] API (F1-F2)
- [ ] Observabilidad y colas (G1-G2)
- [ ] Calidad/CI (H1-H3)
- [ ] Operaciones/DR (I1-I3)

## Estado de Ejecución (Última Actualización)

- Fecha: 2026-05-31
- Implementado:
  - Línea de Trabajo A (endurecimiento de tenancy): `TenantContext` + middleware, `TenancyScope` sin input no confiable, resolver de Teams corregido, pruebas de tenancy.
  - C1 (matrícula): servicio de dominio + pruebas + integración inicial en Filament (`Create Enrollment`).
  - C2 (descarga segura): Policy + controlador/ruta + acción en Filament + pruebas.
  - D1 (RBAC por rol): middleware por panel (Admin/Docente) aplicado a rutas autenticadas, Resources base (`AdminResource`/`DocenteResource`), migración de resources, pruebas de acceso a paneles.
- Parcial / en progreso:
  - C3 (evaluaciones/entregas/calificación): panel docente corregido (resources descubribles + filtros) + creación de evaluación tipo Assignment + acción de calificación de entregas + pruebas de policy.
- En progreso:
  - B2 (modelos): `CourseOffering`/`TeachingAssignment` completados, quedan stubs por completar y estandarizar casts/relaciones.
  - C1 (matrícula): todavía falta decidir y aplicar la regla de `capacity=0` en UI/validaciones.
</content>
