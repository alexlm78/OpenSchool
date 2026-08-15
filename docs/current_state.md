---
name: current_state
description: Current state of the OpenSchool project (auto-updated)
updated_at: 2026-05-31
---

# Current State of OpenSchool

## Verified stack
- Laravel 13.x (see [composer.json](file:///Users/alexlm78/workspaces/php/OpenSchool/composer.json#L8-L15))
- Filament 5.6, Livewire 4.3, Spatie Permission 7.4
- Frontend build: Vite + Tailwind (see [package.json](file:///Users/alexlm78/workspaces/php/OpenSchool/package.json#L1-L15))

## Existing functional structure
- Filament panels:
  - Admin: [AdminPanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/AdminPanelProvider.php#L22-L58)
  - Teacher: [DocentePanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/DocentePanelProvider.php#L22-L58)
- Base data model (migrations) for:
  - Tenancy (schools), users, profiles, academic model, evaluations/submissions/grades (see [database/migrations](file:///Users/alexlm78/workspaces/php/OpenSchool/database/migrations/))

## Recent changes applied (multitenancy hardening)

### TenantContext (server-side source of truth)
- Added a tenant context to store the `school_id` resolved by the server:
  - [TenantContext.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Tenancy/TenantContext.php)

### SetTenantFromAuth Middleware
- Added middleware that sets the tenant from the authenticated user and adds it to the web stack:
  - [SetTenantFromAuth.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Middleware/SetTenantFromAuth.php)
  - Registered in: [bootstrap/app.php](file:///Users/alexlm78/workspaces/php/OpenSchool/bootstrap/app.php#L8-L23)

### Global tenancy scope without untrusted input
- The global scope no longer reads `school_id` from the request; it only applies the filter when `TenantContext` has a value:
  - [TenancyScope.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/Scopes/TenancyScope.php#L1-L33)
- The scope is applied per model via base classes:
  - [TenantModel.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/TenantModel.php)
  - [TenantAuthenticatable.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/TenantAuthenticatable.php)

### Cleanup of duplicated scopes
- Removed use of `SchoolScopeTrait` from models and eliminated duplicated implementations.

### Spatie Permissions Teams (resolver fix)
- Fixed the team resolver to implement the contract expected by Spatie:
  - [SchoolTeamResolver.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Permission/SchoolTeamResolver.php)

## Tests added (tenancy gates)
- Added a feature test that validates:
  - Filtering by tenant context
  - Middleware ignoring `school_id` sent via querystring
  - [TenancyTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/TenancyTest.php)

## Enrollment rules (C1) implemented
- Domain service (transaction + validations):
  - [EnrollStudent.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Domain/Enrollment/EnrollStudent.php)
  - Exceptions: [Exceptions](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Domain/Enrollment/Exceptions/)
- Initial integration in Filament (Create Enrollment):
  - [CreateEnrollment.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/Enrollments/Pages/CreateEnrollment.php)
  - Form updated to Selects: [EnrollmentForm.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/Enrollments/Schemas/EnrollmentForm.php)
- Tests:
  - [EnrollmentRulesTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/EnrollmentRulesTest.php)

## Secure file download (C2) implemented
- Authorization policy (owner/student, admin, assigned teacher):
  - [SubmissionFilePolicy.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Policies/SubmissionFilePolicy.php)
- Download controller (private storage):
  - [SubmissionFileDownloadController.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Controllers/SubmissionFileDownloadController.php)
- Protected route:
  - [web.php](file:///Users/alexlm78/workspaces/php/OpenSchool/routes/web.php#L1-L11)
- Download action in Filament (SubmissionFiles):
  - [SubmissionFilesTable.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/SubmissionFiles/Tables/SubmissionFilesTable.php#L1-L60)
- Tests:
  - [SubmissionFileDownloadTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/SubmissionFileDownloadTest.php)

## C3 (partial): Evaluations and Grading (minimum)
- Teacher panel:
  - Fixed namespaces so the teacher panel's `discoverResources` finds its resources.
  - Filter by assigned teacher:
    - Evaluations: [EvaluationResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Evaluations/EvaluationResource.php)
    - Submissions: [SubmissionResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Submissions/SubmissionResource.php)
    - CourseOfferings: [CourseOfferingResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/CourseOfferings/CourseOfferingResource.php)
- Create evaluation:
  - Now automatically creates an associated `AssignmentDetails`: [CreateEvaluation.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Evaluations/Pages/CreateEvaluation.php)
  - Form avoids manual `school_id` and restricts `course_offering_id` to assigned sections: [EvaluationForm.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Evaluations/Schemas/EvaluationForm.php)
- Grading a submission:
  - "Grade" action in the submissions table creates/updates Grade: [SubmissionsTable.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/Submissions/Tables/SubmissionsTable.php)
  - Policy to authorize the action: [SubmissionPolicy.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Policies/SubmissionPolicy.php)
- Completed models:
  - [Grade.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/Grade.php)
  - Details: [AssignmentDetails.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/AssignmentDetails.php), [ExamDetails.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/ExamDetails.php), [ProjectDetails.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/ProjectDetails.php)
- TenantModel: automatic assignment of `school_id` on creation, when TenantContext is set:
  - [TenantModel.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/TenantModel.php)
- Tests:
  - Grading policy: [SubmissionGradingPolicyTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/SubmissionGradingPolicyTest.php)

## D1: RBAC and role-based visibility (Admin vs Teacher)
- Per-panel middlewares (applied only to authenticated panel routes):
  - Admin: [EnsureAdminRole.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Middleware/EnsureAdminRole.php), configured in [AdminPanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/AdminPanelProvider.php)
  - Teacher: [EnsureTeacherRole.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Http/Middleware/EnsureTeacherRole.php), configured in [DocentePanelProvider.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Providers/Filament/DocentePanelProvider.php)
- Panel access gate (FilamentUser):
  - [User.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Models/User.php) implements `canAccessPanel()` to allow Admin/Teacher based on role when `APP_ENV != local`.
- Base Resource classes to restrict visibility/creation by role:
  - Admin: [AdminResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/AdminResource.php)
  - Teacher: [DocenteResource.php](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/DocenteResource.php)
- Resource migration:
  - Admin panel: Resources under [app/Filament/Resources](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/Resources/) now extend `AdminResource`.
  - Teacher panel: Resources under [app/Filament/DocenteResources](file:///Users/alexlm78/workspaces/php/OpenSchool/app/Filament/DocenteResources/) now extend `DocenteResource`.
- Tests:
  - Panel access by role and login accessible for guests: [PanelAccessTest.php](file:///Users/alexlm78/workspaces/php/OpenSchool/tests/Feature/PanelAccessTest.php)

## Current gaps for a "fully functional app"

### Blockers (MVP1)
- Complete enrollment rules: define behavior for `capacity=0` and improve message/selection UX in the UI.
- Adjust Teacher Panel: limit queries to assigned sections and actions by permissions/role.

### Pending functionality
- Livewire portals (Student and Guardian) per [architecture.md](file:///Users/alexlm78/workspaces/php/OpenSchool/docs/architecture.md).
- Mobile API (Sanctum): `routes/api.php` does not exist today and authentication/endpoints still need to be implemented.
- Notifications: events/listeners/jobs and idempotency strategy.
- Observability: correlation id in logs and propagation to jobs.
- Quality gates: Pint + PHPStan/Psalm + CI.
