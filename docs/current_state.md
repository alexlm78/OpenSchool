---
name: current_state
description: Estado actual del proyecto OpenSchool (actualizado automáticamente)
updated_at: 2026-05-31
---

# Estado actual de OpenSchool

## Stack verificado
- Laravel 13.x (ver [composer.json](file:///Users/alexlm78/workspaces/php/OpenSchool/composer.json#L8-L15))
- Filament 5.6, Livewire 4.3, Spatie Permission 7.4
- Frontend build: Vite + Tailwind (ver [package.json](file:///Users/alexlm78/workspaces/php/OpenSchool/package.json#L1-L15))

## Estructura funcional existente
- Paneles Filament:
  - Admin: [AdminPanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/AdminPanelProvider.php#L22-L58)
  - Docente: [DocentePanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/DocentePanelProvider.php#L22-L58)
- Modelo de datos base (migraciones) para:
  - Tenancy (schools), usuarios, perfiles, modelo académico, evaluaciones/entregas/calificaciones (ver [database/migrations](file:///Users/alexlm78/workspaces/php/OpenSchool/database/migrations/))

## Cambios recientes aplicados (multitenancy hardening)

### TenantContext (fuente de verdad server-side)
- Se agregó un contexto de tenant para almacenar el `school_id` resuelto por servidor:
  - [TenantContext.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Tenancy/TenantContext.php)

### Middleware SetTenantFromAuth
- Se agregó middleware que setea el tenant desde el usuario autenticado y lo incorpora al stack web:
  - [SetTenantFromAuth.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Middleware/SetTenantFromAuth.php)
  - Registrado en: [bootstrap/app.php](file:///Users/alexlm78/workspaces/php/OpenSchool/bootstrap/app.php#L8-L23)

### Scope global de tenancy sin inputs no confiables
- El scope global ya no lee `school_id` desde el request; solo aplica filtro cuando `TenantContext` tiene valor:
  - [TenancyScope.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/Scopes/TenancyScope.php#L1-L33)
- El scope se aplica por modelo mediante clases base:
  - [TenantModel.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/TenantModel.php)
  - [TenantAuthenticatable.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/TenantAuthenticatable.php)

### Limpieza de scopes duplicados
- Se removió el uso de `SchoolScopeTrait` de modelos y se eliminaron implementaciones duplicadas.

### Spatie Permissions Teams (corrección del resolver)
- Se corrigió el team resolver para que implemente el contrato esperado por Spatie:
  - [SchoolTeamResolver.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Permission/SchoolTeamResolver.php)

## Tests agregados (gates de tenancy)
- Se agregó un test feature que valida:
  - Filtrado por tenant context
  - Middleware ignorando `school_id` enviado por querystring
  - [TenancyTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/TenancyTest.php)

## Reglas de matrícula (C1) implementadas
- Servicio de dominio (transacción + validaciones):
  - [EnrollStudent.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Domain/Enrollment/EnrollStudent.php)
  - Excepciones: [Exceptions](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Domain/Enrollment/Exceptions/)
- Integración inicial en Filament (Create Enrollment):
  - [CreateEnrollment.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/Enrollments/Pages/CreateEnrollment.php)
  - Formulario actualizado a Selects: [EnrollmentForm.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/Enrollments/Schemas/EnrollmentForm.php)
- Tests:
  - [EnrollmentRulesTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/EnrollmentRulesTest.php)

## Descarga segura de archivos (C2) implementada
- Policy de autorización (dueño/alumno, admin, docente asignado):
  - [SubmissionFilePolicy.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Policies/SubmissionFilePolicy.php)
- Controlador de descarga (storage privado):
  - [SubmissionFileDownloadController.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Controllers/SubmissionFileDownloadController.php)
- Ruta protegida:
  - [web.php](file:///Users/alexlm78/workspaces/php/OpenSchool/routes/web.php#L1-L11)
- Acción de descarga en Filament (SubmissionFiles):
  - [SubmissionFilesTable.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/SubmissionFiles/Tables/SubmissionFilesTable.php#L1-L60)
- Tests:
  - [SubmissionFileDownloadTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/SubmissionFileDownloadTest.php)

## C3 (parcial): Evaluaciones y Calificación (mínimo)
- Docente panel:
  - Namespaces corregidos para que `discoverResources` del panel docente encuentre sus resources.
  - Filtro por docente asignado:
    - Evaluations: [EvaluationResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Evaluations/EvaluationResource.php)
    - Submissions: [SubmissionResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Submissions/SubmissionResource.php)
    - CourseOfferings: [CourseOfferingResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/CourseOfferings/CourseOfferingResource.php)
- Crear evaluación:
  - Ahora crea automáticamente un `AssignmentDetails` asociado: [CreateEvaluation.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Evaluations/Pages/CreateEvaluation.php)
  - Formulario evita `school_id` manual y restringe `course_offering_id` a secciones asignadas: [EvaluationForm.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Evaluations/Schemas/EvaluationForm.php)
- Calificar submission:
  - Acción "Grade" en tabla de submissions crea/actualiza Grade: [SubmissionsTable.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Submissions/Tables/SubmissionsTable.php)
  - Policy para autorizar la acción: [SubmissionPolicy.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Policies/SubmissionPolicy.php)
- Modelos completados:
  - [Grade.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/Grade.php)
  - Details: [AssignmentDetails.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/AssignmentDetails.php), [ExamDetails.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/ExamDetails.php), [ProjectDetails.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/ProjectDetails.php)
- TenantModel: asignación automática de `school_id` al crear, cuando el TenantContext está seteado:
  - [TenantModel.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/TenantModel.php)
- Tests:
  - Policy de calificación: [SubmissionGradingPolicyTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/SubmissionGradingPolicyTest.php)

## D1: RBAC y visibilidad por rol (Admin vs Docente)
- Middlewares por panel (aplicados solo a rutas autenticadas del panel):
  - Admin: [EnsureAdminRole.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Middleware/EnsureAdminRole.php), configurado en [AdminPanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/AdminPanelProvider.php)
  - Docente: [EnsureTeacherRole.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Middleware/EnsureTeacherRole.php), configurado en [DocentePanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/DocentePanelProvider.php)
- Gate de acceso al panel (FilamentUser):
  - [User.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/User.php) implementa `canAccessPanel()` para permitir Admin/Docente según rol cuando `APP_ENV != local`.
- Bases de Resource para restringir visibilidad/creación por rol:
  - Admin: [AdminResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/AdminResource.php)
  - Docente: [DocenteResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/DocenteResource.php)
- Migración de Resources:
  - Admin panel: Resources bajo [app/Filament/Resources](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/) ahora extienden `AdminResource`.
  - Docente panel: Resources bajo [app/Filament/DocenteResources](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/) ahora extienden `DocenteResource`.
- Tests:
  - Acceso a paneles por rol y login accesible para guests: [PanelAccessTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/PanelAccessTest.php)

## Brechas actuales para “app totalmente funcional”

### Bloqueantes (MVP1)
- Completar reglas de matrícula: definir comportamiento de `capacity=0` y mejorar UX de mensajes/selección en UI.
- Ajustar Docente Panel: limitar queries a secciones asignadas y acciones por permisos/rol.

### Funcionalidad pendiente
- Portales Livewire (Alumno y Apoderado) según [architecture.md](file:///Users/alexlm78/workspaces/php/OpenSchool/docs/architecture.md).
- API móvil (Sanctum): hoy no existe `routes/api.php` y falta implementar autenticación/endpoints.
- Notificaciones: events/listeners/jobs y estrategia de idempotencia.
- Observabilidad: correlation id en logs y propagación a jobs.
- Quality gates: Pint + PHPStan/Psalm + CI.
