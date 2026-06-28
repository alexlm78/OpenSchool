---
name: current_state
description: Current state of the OpenSchool project
metadata: 
  node_type: memory
  type: project
  originSessionId: 48852a79-510b-4022-82c4-37308772d4bc
---

# Estado actual y mejoras propuestas al plan (2026-05-29)

## Resumen ejecutivo

El repositorio contiene una base sólida de “scaffolding” (Laravel + Filament + migraciones principales), pero hoy hay una brecha relevante entre:

- Lo que declara el plan/arquitectura y lo que realmente está implementado.
- El objetivo de multitenancy seguro y el código actual (hay un riesgo de aislamiento entre escuelas).

La recomendación principal es **endurecer y unificar multitenancy + autorización** antes de seguir construyendo portales/API, para evitar refactors costosos y riesgos de seguridad.

## Evidencia del stack y estado real del repositorio

- Framework y dependencias PHP: [composer.json](file:///Users/alexlm78/workspaces/php/OpenSchool/composer.json#L8-L24) indica **Laravel 13.8**, Filament 5.6, Livewire 4.3 y Spatie Permission 7.4.
- El plan menciona “Laravel 11” y “Sanctum/Scout/Redis instalados” pero hoy **Sanctum y Scout no aparecen como dependencias** en composer.json (lo cual sugiere que el plan está desactualizado).
- Paneles Filament:
  - Admin: [AdminPanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/AdminPanelProvider.php#L22-L58)
  - Docente: [DocentePanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/DocentePanelProvider.php#L22-L58)
- Migraciones y modelo de datos base (escuelas, usuarios, cursos, matrículas, evaluaciones, entregas, etc.) existen en [database/migrations](file:///Users/alexlm78/workspaces/php/OpenSchool/database/migrations/).

## Hallazgos técnicos clave (priorizados)

### Bloqueantes (robustez + seguridad)

1) Riesgo de “tenant escape” por entrada no confiable  
El scope global de tenancy toma `school_id` desde el request:
- [TenancyScope.php:L19-L25](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/Scopes/TenancyScope.php#L19-L25)

Esto permite que un atacante fuerce `school_id` mediante query/body y potencialmente consulte datos de otra escuela si no hay otras barreras. El tenant **no debe venir de Request::input**; debe resolverse por servidor (sesión/auth/subdominio/selector explícito y controlado).

2) Implementación duplicada/rota de scopes de escuela  
Hay al menos dos enfoques simultáneos (scope global en AppServiceProvider + trait por modelo). El trait actual introduce un scope adicional y contiene inconsistencias (dependencias no importadas y duplicidad de responsabilidades):
- [SchoolScopeTrait.php:L16-L60](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Traits/SchoolScopeTrait.php#L16-L60)
- Además existe otro scope alternativo: [SchoolScope.php:L9-L49](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Scopes/SchoolScope.php#L9-L49)

Esto complica depuración y eleva el riesgo de fallos/consultas incorrectas. Recomendación: un único mecanismo de tenancy.

### Importantes (consistencia del dominio + mantenibilidad)

3) Ambigüedad “User vs Student” en el dominio  
Existen tablas/modelos `students` y `users`. Sin embargo, `enrollments.student_id` referencia `users`:
- [create_enrollments_table.php:L16-L29](file:///Users/alexlm78/workspaces/php/OpenSchool/database/migrations/2026_05_27_190129_create_enrollments_table.php#L16-L29)
- El modelo Enrollment asocia `student()` a User: [Enrollment.php:L28-L36](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/Enrollment.php#L28-L36)

Esto puede ser válido si “estudiante = usuario con rol student”, pero entonces el modelo `Student` pasa a ser un “perfil” opcional y el naming “student_id” es confuso. El plan/arquitectura deben fijar esta decisión.

4) Compleción desigual de modelos (relaciones/reglas)  
Varios modelos están aún vacíos (por ejemplo CourseOffering, SubmissionFile), lo que implica que las reglas del dominio (capacidad, conflictos de horario, políticas de descarga, etc.) todavía no están codificadas en el núcleo.

5) Teams de Spatie: configurado, pero revisar contrato del resolver  
En config se activa teams y se define `team_resolver`:
- [permission.php:L151-L167](file:///Users/alexlm78/workspaces/php/OpenSchool/config/permission.php#L151-L167)
- Resolver actual: [SchoolTeamResolver.php:L8-L25](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Permission/SchoolTeamResolver.php#L8-L25)

Conviene validar que el resolver implementa el contrato esperado por Spatie (para evitar comportamientos no deterministas).

## Brechas entre PLAN.md, arquitectura y código

- PLAN.md afirma Laravel 11 y librerías instaladas que no están reflejadas en composer.json (ej.: Sanctum/Scout).  
- docs/architecture.md describe middleware de tenancy por subdominio/tokens, pero no existe implementación de middleware de tenancy en `app/Http/Middleware`.
- memory/current_state.md anterior estaba desalineado con el repo actual; este documento lo reemplaza con evidencia verificable.

## Flujo recomendado de tenancy (server-side) vs. flujo actual (riesgo)

```mermaid
flowchart LR
    A[Request Web/Filament/API] --> B{Resolver Tenant Context}
    B -->|Auth session| C[school_id]
    B -->|Subdominio/Header controlado| C
    C --> D[TenancyScope (Eloquent)]
    D --> E[Queries con school_id]
    style B fill:#bbdefb,color:#0d47a1
    style D fill:#c8e6c9,color:#1a5e20
```

```mermaid
flowchart LR
    A[Request] --> B[Request::input('school_id')]
    B --> C[TenancyScope actual]
    C --> D[Queries con school_id forzado]
    style B fill:#fff3e0,color:#e65100
    style D fill:#f3e5f5,color:#7b1fa2
```

## Mejoras que haría en PLAN.md (para “mejor software posible”)

### 1) Reestructurar el plan por “capas” + entregables verificables

El plan actual lista tareas, pero carece de criterios de aceptación. Propongo reescribir cada fase con:

- Entregable (qué se considera “terminado”)
- Criterios de aceptación (tests/escenarios)
- Riesgos y mitigación
- Dependencias (p.ej. tenancy antes de portales/API)

### 2) Introducir un Workstream explícito de Seguridad-by-Design (desde Fase 2)

Agregar una fase transversal con controles mínimos:

- Tenancy no derivado de input de usuario; tenant context único y auditable.
- Autorización granular (Policies/Gates) para recursos críticos (descargas, calificaciones, matrícula).
- Auditoría de accesos: logs estructurados con `school_id`, `user_id`, `route`, `action`.
- OWASP Top 10: validación, CSRF, rate limiting, seguridad en subida/descarga de archivos.

### 3) Añadir Observabilidad y Operación temprana (no al final)

Mover parte de “Infraestructura y Rendimiento” hacia antes del MVP1:

- Correlation ID / Request ID en logs.
- Métricas mínimas (latencia, errores 4xx/5xx, jobs fallidos).
- Alertas básicas (errores, cola creciendo, storage lleno).

### 4) Definir claramente el modelo de identidad y el modelo académico

Decisión arquitectónica a explicitar en el plan:

- ¿Estudiante/Docente/Apoderado son “Users con roles” + perfiles (Student/Teacher/Guardian)?  
  - Si sí: normalizar naming (ej.: `enrollments.user_id` en lugar de `student_id`) o documentar la convención.
  - Si no: entonces `enrollments` debe referenciar `students` y las reglas de sincronización con `users` deben estar claras.

### 5) “Quality gates” por fase (para evitar deuda técnica acumulada)

Antes de pasar de Fase 4 → Fase 5/6:

- Linter/formatter (Pint) y análisis estático (PHPStan/Psalm) en CI.
- Pruebas mínimas de tenancy (dos escuelas; no leakage).
- Pruebas mínimas del dominio: matrícula (capacidad + conflicto), evaluaciones, entregas, calificación.

## Roadmap sugerido (ajuste del plan sin cambiar el alcance)

- MVP1 (base segura): Tenancy + permisos + panel Admin + modelo académico + matrícula con validaciones + evaluaciones/entregas básicas + descarga segura de archivos.
- MVP1.1 (operabilidad): colas + notificaciones mínimas + observabilidad.
- MVP2: portal alumno (Livewire) + mejoras UX.
- MVP2.1: portal apoderado + reportes básicos.
- MVP3: API móvil (Sanctum) + búsqueda (Scout/Meilisearch) + analítica/avanzado.

## Próximas acciones recomendadas (top 10)

1) Unificar tenancy (un solo “TenantContext”) y eliminar el uso de `Request::input('school_id')`.
2) Eliminar/reestructurar la duplicidad de scopes (AppServiceProvider vs SchoolScopeTrait vs App\Scopes\SchoolScope).
3) Definir y documentar “User vs Student” (y ajustar naming/relaciones coherentemente).
4) Añadir Policies para SubmissionFile y rutas de descarga desde storage privado.
5) Asegurar RBAC en Filament (visibilidad/acciones por rol y escuela).
6) Crear pruebas de multitenancy (dos escuelas) como gate de CI.
7) Implementar validación de conflicto de horario y capacidad en matrícula (a nivel dominio y UI).
8) Establecer una estrategia de eventos/jobs para notificaciones (con idempotencia).
9) Introducir correlation id en logs y propagación a jobs.
10) Revisar migraciones adicionales de roles/permisos para evitar duplicidad/confusión con teams.
