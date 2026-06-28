# OpenSchool Implementation Plan (Reusable and Executable)

This plan is designed so that a coding agent can implement it without ambiguity. It is organized by **workstreams**, with **atomic tasks**, **target files**, and **acceptance criteria**.

## 0) Context and Alignment (Mandatory)

### Verified Repository Facts
- Laravel **13.8** (not 11): see dependencies in `composer.json`.
- `routes/api.php` does not exist today (only `routes/web.php` and `routes/console.php`).
- Current multitenancy has a critical risk: the scope reads `school_id` from request input (not trustworthy).

### Architecture Principles to Execute This Plan
1. **Security first**: multitenancy must be server-side, not derived from client input.
2. **A single tenancy mechanism**: avoid duplication (global scope + traits + parallel scopes).
3. **Explicit domain**: decisions such as "Student is a User with a role + profile" must be implemented and tested.
4. **Quality gates from the beginning**: school isolation tests must exist before adding portals/API.

### Global Definition of Done (DoD) per Task
- `php artisan test` passes.
- No data leaks between schools (including tests).
- Sensitive actions (file download, grading, enrollment) are protected by Policies/Gates.
- No unused dependencies or half-configured settings are introduced.

## 1) Workstream A - Secure and Unified Multitenancy (Blocking)

### A1. Define a Single Source of Truth for the Tenant (`TenantContext`)
- Goal: ensure `school_id` is resolved **server-side** (auth/session in web/Filament, token in API, optional future subdomain).
- Tasks:
  - Create a `TenantContext` service (in the container) with:
    - `getSchoolId(): ?int`
    - `setSchoolId(?int $schoolId): void`
    - `requireSchoolId(): int` (throws if it is missing and required)
  - Create `SetTenantFromAuth` middleware:
    - If `auth()->check()` and `auth()->user()->school_id`, set the tenant.
    - If there is no auth, tenant remains null.
  - Register middleware for:
    - Web/Filament (for `/admin` and `/docente`).
    - Future API (Sanctum).
- Target files:
  - `app/Http/Middleware/SetTenantFromAuth.php` (new)
  - `bootstrap/app.php` or `app/Http/Kernel.php` (depending on Laravel 13) to register middleware
  - `app/Support/TenantContext.php` (new) or `app/Tenancy/TenantContext.php` (new)
- Acceptance criteria:
  - In an authenticated request, the tenant is set without relying on request input.

### A2. Fix the Global Tenancy Scope (Disallow `Request::input('school_id')`)
- Goal: eliminate tenant escape.
- Tasks:
  - Modify `app/Models/Scopes/TenancyScope.php` to use **only** `TenantContext`.
  - Forbid resolving the tenant from query/body input.
  - Ensure the scope only applies to tables with `school_id` (using `Schema::hasColumn`).
- Target files:
  - `app/Models/Scopes/TenancyScope.php`
  - `app/Providers/AppServiceProvider.php` (keep only one `Model::addGlobalScope(new TenancyScope())`)
- Acceptance criteria:
  - An authenticated user cannot switch `school_id` by sending `?school_id=...`.
  - Tests prove that "School A" cannot see "School B" data.

### A3. Remove Scope Duplication (Cleanup)
- Goal: one single approach, minimal and understandable.
- Tasks:
  - Remove `SchoolScopeTrait` from models (or turn it into an empty wrapper/no-op if temporary compatibility is needed).
  - Remove/archive the alternate scope `app/Scopes/SchoolScope.php` if unused.
  - Confirm that the only filter is the global `TenancyScope`.
- Target files:
  - `app/Traits/SchoolScopeTrait.php`
  - Models that use it (for example `app/Models/User.php`, `app/Models/AcademicPeriod.php`, `app/Models/CourseTemplate.php`)
  - `app/Scopes/SchoolScope.php`
- Acceptance criteria:
  - There is no double filtering, no missing dependencies, and every multi-tenant query uses the global scope.

### A4. Integrate Spatie Permissions (Teams) with `school_id`
- Goal: isolate roles/permissions by school using Teams.
- Tasks:
  - Validate the `team_resolver` contract (it must implement the interface/contract required by Spatie v7).
  - Ensure that on every request, the "team id" is set to the current `school_id`.
    - Recommended path: middleware that, after setting the tenant, runs `setPermissionsTeamId($schoolId)`.
  - Review extra migrations for `roles.school_id` / `permissions.school_id` to avoid duplicating the concept with `team_foreign_key`.
    - Recommended decision: use **only** `team_foreign_key = school_id` and avoid redundant columns unless they add clear value.
- Target files:
  - `config/permission.php`
  - `app/Permission/SchoolTeamResolver.php`
  - Tenancy middleware (extend it to also set the team)
  - Migration `database/migrations/2026_05_28_112246_add_school_id_to_roles_and_permissions_table.php` (review necessity)
- Acceptance criteria:
  - A role/permission assigned in School A is not visible or usable in School B.
  - Tests cover role creation/assignment across two schools.

### A5. Mandatory Multitenancy Tests (Project-wide Gate)
- Goal: block security regressions.
- Tasks:
  - Create tests that create two schools and users, and verify isolation in at least:
    - `CourseTemplate` / `CourseOffering`
    - `Enrollment`
    - `Evaluation` / `Submission` / `Grade`
  - Ensure that switching users (A vs B) does not mix query results.
- Target files:
  - `tests/Feature/Tenancy/*.php` (new) or `tests/Feature/TenancyTest.php`
- Acceptance criteria:
  - The tests fail if `Request::input('school_id')` is reintroduced.

## 2) Workstream B - Complete Domain and Models (Consistency + Maintainability)

### B1. Identity Decision: "Student/Teacher/Guardian"
- Recommended decision (for consistency with the current migrations):
  - **Student/Teacher/Guardian are profiles** and the authenticated actor is `User`.
  - "Alumno" = `users` with the `student` role + (optional) profile in the `students` table.
- Tasks:
  - Document the decision in the code itself through naming and relationships (without comments).
  - Normalize relationships:
    - `Enrollment::student()` currently returns `User` (ok), but define `StudentProfile` if extra information is needed.
  - Define access policies based on roles + ownership.
- Target files:
  - `app/Models/Enrollment.php`
  - `app/Models/Student.php`, `app/Models/Teacher.php`, `app/Models/Guardian.php`
  - `app/Models/User.php`
- Acceptance criteria:
  - The identity model is consistent across UI (Filament), portals, and API.

### B2. Complete Empty Models and Core Academic Relationships
- Tasks (minimum):
  - Complete `CourseOffering`, `SubmissionFile`, and any other stub models with:
    - `$fillable`, `casts()`, relationships (`belongsTo`, `hasMany`, `belongsToMany`)
  - Confirm that every model with `school_id` is filtered by `TenancyScope`.
- Target files:
  - `app/Models/CourseOffering.php`
  - `app/Models/SubmissionFile.php`
  - Related models (`TeachingAssignment`, `OfferingTimeSlot`, `Evaluation`, `Submission`, `Grade`, etc.)
- Acceptance criteria:
  - Filament can list/create records without relationship errors.

## 3) Workstream C - Critical Business Rules (MVP1)

### C1. Enrollment: Capacity and Schedule Conflicts (Domain + UI)
- Goal: block over-enrollment and schedule clashes.
- Rules:
  - Capacity: `enrollments(active)` for `course_offering_id` must not exceed `course_offerings.capacity` (if `capacity=0`, decide between "no seats available" or "unlimited" and document the rule; recommendation: `0 = no seats available`).
  - Conflict: a student cannot enroll in two offerings with overlapping `TimeSlot` values on the same day.
- Recommended implementation:
  - `EnrollStudent` domain service with DB transaction.
  - Queries with locking or consistent verification to avoid races (if the database supports it).
  - Reusable validation for Filament + Portals + API.
- Target files:
  - Service: `app/Domain/Enrollment/EnrollStudent.php` (new) or `app/Actions/EnrollStudent.php`
  - Filament Enrollment form/table: `app/Filament/Resources/Enrollments/...`
  - Tests: `tests/Feature/Enrollment/*.php`
- Acceptance criteria:
  - Happy path: enrollment succeeds.
  - Over-capacity case: clear error.
  - Schedule conflict case: clear error.

### C2. Secure File Download (`SubmissionFile`)
- Goal: private storage files authorized by Policies.
- Rules:
  - Only the owning student (or assigned teacher, or school admin) can download.
- Recommended implementation:
  - Store files on a private disk.
  - Serve download routes from a controller that validates Policy and uses `Storage::download`.
- Target files:
  - `app/Policies/SubmissionFilePolicy.php` (new)
  - `app/Http/Controllers/SubmissionFileDownloadController.php` (new)
  - `routes/web.php` (add protected route)
  - `config/filesystems.php` (review disk naming; today `local` points to `storage/app/private`)
- Acceptance criteria:
  - A user from another school gets 404/403.
  - A student cannot download another student's files.

### C3. Evaluations/Submissions/Grading (Minimum End-to-End)
- Goal: full flow teacher -> student -> teacher -> student.
- Tasks:
  - Confirm relationships:
    - `Evaluation` -> polymorphic details (assignment/exam/project)
    - `Submission` -> `SubmissionFiles`
    - `Grade` -> `Submission`
  - Implement minimum states (`draft/published`, `on-time/late`) if applicable.
  - Guarantee Policies: a teacher only manages their own sections.
- Acceptance criteria:
  - The critical architecture document flow can be executed from the UI.

## 4) Workstream D - Filament Ready for Operations (Admin + Teacher)

### D1. RBAC and Visibility by Role
- Goal: each panel shows only what is allowed.
- Tasks:
  - Admin panel:
    - Define permissions: manage catalog, users, roles, periods, courses, enrollments.
  - Teacher panel:
    - List only `CourseOfferings` where the teacher has a `TeachingAssignment`.
    - Create evaluations only within their own offerings.
    - View/grade submissions only for their own evaluations/offerings.
  - Harden role-based access:
    - Panel middleware applied to authenticated routes (`authMiddleware`) so panel login is not blocked.
    - `User` implements `FilamentUser::canAccessPanel()` to control panel access when `APP_ENV != local`.
    - Base resources: `AdminResource` / `DocenteResource` with `canViewAny()` / `canCreate()` by role.
    - Migrate existing Resources so they extend the correct base per panel.
  - Implement `->query()` / `getEloquentQuery()` per Resource to filter data.
- Target files:
  - `app/Providers/Filament/AdminPanelProvider.php`
  - `app/Providers/Filament/DocentePanelProvider.php`
  - Resources in `app/Filament/Resources/**` and `app/Filament/DocenteResources/**`
- Acceptance criteria:
  - A teacher cannot see records outside their sections.
  - An admin cannot see data from another school.

### D2. Seed Roles/Permissions for Faster Bootstrap (Optional but Recommended)
- Goal: permission bootstrap.
- Tasks:
  - Create a seeder that creates base roles (`admin`, `docente`, `student`, `guardian`) per school.
  - Assign permissions to roles.
- Acceptance criteria:
  - A clean installation allows entering and operating the panels without extensive manual setup.

## 5) Workstream E - Livewire Portals (Student and Guardian)

### E1. Student Portal
- Minimum features:
  - View enrolled courses for the active period.
  - View evaluations by course (pending/graded).
  - Create `Submission` with files.
  - View feedback/grade.
- Target files:
  - `app/Livewire/**` (new)
  - `routes/web.php` (add `/alumno` routes)
  - `resources/views/**` (if applicable)
- Acceptance criteria:
  - A student only sees and operates on their own data and school.

### E2. Guardian Portal
- Minimum features:
  - Student linking (via code or request).
  - Consolidated view of grades/evaluations.
- Acceptance criteria:
  - A guardian only sees linked children from their own school.

## 6) Workstream F - Mobile API (Sanctum) + Stable Contracts

### F1. API Preparation
- Tasks:
  - Add `routes/api.php`.
  - Install/configure Sanctum (if not already installed in the repository).
  - Implement tenancy middleware for API (token -> user -> `school_id` -> `TenantContext`).
- Target files:
  - `routes/api.php` (new)
  - `app/Http/Controllers/Api/**` (new)
  - `config/sanctum.php` (if installed)
- Acceptance criteria:
  - Tokens work and respect tenancy/policies.

### F2. MVP Endpoints
- Recommended endpoints:
  - Auth: login/token, me
  - Courses: list, detail (enrollments)
  - Evaluations: list by course, detail
  - Submissions: create, list, detail
  - Grades: list by student
- Acceptance criteria:
  - Correct 403/404 responses outside the tenant or without permission.

## 7) Workstream G - Notifications, Queues, and Observability

### G1. Events and Jobs (Idempotent)
- Tasks:
  - Events: `EvaluationPublished`, `SubmissionCreated`, `GradePublished`.
  - Queue listeners that notify students/guardians.
  - Idempotency: avoid duplicates on retries (idempotency key per event/user).
- Acceptance criteria:
  - `queue:work` processes jobs and does not duplicate notifications on retries.

### G2. Correlation ID in Logs
- Tasks:
  - Middleware that assigns `X-Request-Id` if missing.
  - Inject `request_id` and `school_id` into logs (context).
  - Propagate `request_id` to jobs.
- Acceptance criteria:
  - Logs consistently include `request_id` and `school_id`.

## 8) Workstream H - Testing, Static Analysis, and CI (Gates)

### H1. Domain and Security Tests
- Mandatory:
  - Tenancy (A5)
  - Enrollment (capacity + conflict)
  - File download (Policy)
  - Teacher limited to their own sections

### H2. Style + Static Analysis
- Tasks:
  - Pint configured and executed in CI.
  - PHPStan or Psalm added and configured.
- Acceptance criteria:
  - CI fails on errors or tenancy regressions.

### H3. CI Pipeline
- Minimum jobs:
  - Composer install
  - Pint (check)
  - Tests
  - Dry-run migrations (`migrate:fresh` on sqlite)

## 9) Workstream I - Deployment and Operations

### I1. Environment Configuration
- Tasks:
  - `.env.example` aligned with disks/queues/logs.
  - Dev/staging/prod separation.

### I2. Workers and Scheduler
- Tasks:
  - Document and configure `queue:work` / supervisor.
  - Configure scheduler (`schedule:run`) for future tasks (reminders, cleanup).

### I3. Backups and DR (Disaster Recovery)
- Tasks:
  - Backup strategy for database and private storage.
  - Restore procedure.

## 10) Functional Release Definition

### Release R1 (MVP1 "Functional and Secure")
- Complete tenancy hardening (Workstream A)
- Admin + Teacher panels operational (Workstream D)
- Enrollment with rules (Workstream C1)
- Minimum Evaluations/Submissions/Grading (Workstream C3)
- Secure file download (Workstream C2)
- Minimum tests + gates (Workstream H)

### Release R2 (Web Portals)
- Student + Guardian Portal (Workstream E)
- Base notifications (Workstream G1)

### Release R3 (Mobile API + Search)
- API with Sanctum (Workstream F)
- Scout/Meilisearch (if the team decides to add and operate it in the repository)

## Execution Checklist (Recommended Order)

- [x] Execute the full Workstream A (A1-A5) before touching UI/API
- [~] Complete models and relationships (B1-B2)
- [~] Implement critical rules (C1-C3)
- [x] Harden panels (D1)
- [ ] Seed roles/permissions (D2)
- [ ] Portals (E1-E2)
- [ ] API (F1-F2)
- [ ] Observability and queues (G1-G2)
- [ ] Quality/CI (H1-H3)
- [ ] Operations/DR (I1-I3)

## Execution Status (Last Update)

- Date: 2026-05-31
- Implemented:
  - Workstream A (tenancy hardening): `TenantContext` + middleware, `TenancyScope` without untrusted input, corrected Teams resolver, tenancy tests.
  - C1 (enrollment): domain service + tests + initial Filament integration (`Create Enrollment`).
  - C2 (secure download): Policy + controller/route + Filament action + tests.
  - D1 (role-based RBAC): per-panel middleware (Admin/Teacher) applied to authenticated routes, base Resources (`AdminResource`/`DocenteResource`), resource migration, panel access tests.
- Partial / in progress:
  - C3 (evaluations/submissions/grading): Teacher panel corrected (discoverable resources + filters) + creation of Assignment-type evaluation + submission grading action + policy tests.
- In progress:
  - B2 (models): `CourseOffering`/`TeachingAssignment` completed, remaining stubs still need to be completed and casts/relationships standardized.
  - C1 (enrollment): the `capacity=0` rule still needs to be decided and enforced in UI/validations.
