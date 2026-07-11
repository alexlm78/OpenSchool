# OpenSchool Architectural and Functional Design Document

## Overview

OpenSchool is a comprehensive educational management platform built on Laravel 13.x, designed to handle multiple educational institutions (multitenancy) with differentiated roles (administrators, teachers, students, guardians) and features covering everything from enrollment to academic evaluation and tracking.

## Technical Architecture

### Technology Stack
- **Backend**: Laravel 13.x (PHP framework)
- **Admin Panels**: Filament v5.6
- **Reactive Components**: Livewire v4.3 (dynamic user interface)
- **Permission Management**: Spatie Laravel Permissions
- **Authentication**: Laravel Sanctum (for mobile API)
- **Search**: Laravel Scout with Meilisearch driver
- **Queues and Jobs**: Redis (queue driver)
- **File Storage**: private disk (local/S3 configurable)
- **Logging**: Laravel Pail/Pao with correlation ID
- **Asset Bundling**: Vite
- **Testing**: Pest/PHPUnit, Laravel Pint (code style)
- **Database**: SQLite (development), configurable to MySQL/PostgreSQL

### Multitenancy Pattern
- Implemented via a **global scope** on all relevant models
- Every main table includes a `school_id` column (except system tables like users, password_resets, etc.)
- Spatie Permissions policies use `team_id` = `school_id` to isolate roles and permissions per institution
- Tenancy middleware sets the current `school_id` based on the subdomain or API tokens

### Module Structure

#### 1. Tenancy and Users Module
- **School**: represents an educational institution
- **User**: centralized authentication system (but isolated per school_id via scope)
- **Role/Permission**: managed by Spatie, isolated per school
- Related models: Student, Teacher, Guardian, GuardianStudent (pivot)

#### 2. Academic Model
- **AcademicPeriod**: academic terms (quarters, semesters, etc.)
- **CourseTemplate**: abstract definition of a course (name, code, credits)
- **CourseOffering**: specific instance of a CourseTemplate in an AcademicPeriod with capacity and schedule
- **TimeSlot**: time blocks (Monday 8-10am, etc.)
- **OfferingTimeSlot**: schedule assignment for a CourseOffering
- **TeachingAssignment**: assignment of a Teacher to a CourseOffering (a section)
- **Enrollment**: a Student's enrollment in a CourseOffering (with schedule conflict and capacity validation)

#### 3. Evaluations and Submissions
- **Evaluation**: polymorphic evaluation (can be an assignment, exam, project, etc.)
  - Types via relationships: AssignmentDetails, ExamDetails, ProjectDetails
- **Submission**: a student's submission for an Evaluation
- **SubmissionFile**: files attached to a Submission (private storage)
- **Grade**: grade and feedback associated with a Submission
- **Observers/Events**: generate notifications (queued) when evaluations, submissions, and grades are created/updated

#### 4. User Interfaces
- **Admin Panel** (Filament): full management of schools, users, roles, periods, course templates, offerings, assignments, enrollments
- **Teacher Panel** (Filament): limited view of sections assigned to the teacher; allows creating evaluations, viewing submissions, grading
- **Student Portal** (Livewire):
  - View enrolled courses and their schedule
  - View pending and graded evaluations
  - Upload submissions and view feedback
  - Academic calendar
- **Guardian Portal** (Livewire):
  - Linking to one or more students
  - Consolidated calendar of their children's evaluations
  - View grades and progress per period
  - Receive notifications
- **Mobile API** (Sanctum):
  - Authenticated endpoints for courses, evaluations, submissions, grades
  - Same policy and tenancy layer as the web interfaces

## Workflow by User Type

### Administrator
1. Logs in at /admin
2. Manages schools (create/edit/deactivate)
3. Creates users and assigns roles (admin, teacher, etc.) within their school
4. Defines academic periods
5. Manages the course template catalog
6. Approves course offerings (created by coordinators or directly)
7. Oversees enrollments and reports

### Teacher
1. Logs in at /docente
2. Views their panel with assigned sections (CourseOfferings where they are teacher)
3. For each section:
   - Views list of enrolled students
   - Creates evaluations (assignments, exams, projects) with dates and description
   - Publishes evaluations (visible to students)
   - Reviews submissions uploaded by students
   - Grades submissions and provides feedback
   - Publishes grades
4. Can view reports for their section

### Student
1. Logs in at /alumno
2. Views their dashboard with:
   - Enrolled courses for the current period
   - Upcoming evaluations
   - Academic calendar
3. Accesses a specific course to:
   - View evaluation details
   - Upload submissions (with attached files)
   - View grades and feedback for already-graded submissions
   - Participate in activities (depending on configuration)

### Guardian
1. Logs in at /apoderado
2. Links their children (if not already linked) via student code or admin-approved request
3. Views a summary of all their linked children:
   - Upcoming evaluations for each child
   - Combined calendar
   - Averages and progress per period
4. Accesses a child's detail view to see their performance course by course

## Considerations for User Testing

### Key Test Scenarios

#### Enrollment and Schedule Flow
1. Admin creates an academic period
2. Admin creates a course template (e.g., "Mathematics 101")
3. Admin creates an offering of that course in the period (capacity: 30)
4. Admin creates time blocks (TimeSlots) and assigns them to the offering
5. Admin creates a teacher and assigns them the offering (TeachingAssignment)
6. Admin creates a student
7. Student enrolls in the offering (Enrollment) - must validate capacity and conflicts
8. Student attempts to enroll in another offering with a conflicting schedule -> should show an error
9. Student views their schedule in the student portal

#### Evaluation and Grading Flow
1. Teacher creates an evaluation (assignment type) in their assigned section
2. Teacher sets a due date and attaches a rubric (optional)
3. Student sees the evaluation in their portal
4. Student uploads a submission before the deadline
5. Teacher receives a notification (queued) and reviews the submission
6. Teacher grades the submission and leaves feedback
7. Student sees the grade and feedback in their portal
8. Linked guardian sees the grade in their consolidated portal

#### Notifications Flow
1. When an evaluation is created, an event fires that sends a notification to enrolled students (queued)
2. When a submission is graded, a notification is sent to the student and guardian
3. Verify that notifications arrive correctly (can be inspected in the notifications table or logs)

#### Multitenancy Tests
1. Create two different schools (School A and School B)
2. Create users, courses, evaluations in each school
3. Log in as a School A user and verify they don't see School B data
4. Repeat for School B
5. Verify that roles and permissions are isolated (a teacher from A cannot manage sections from B)

#### Mobile API Tests
1. Authenticate via Sanctum (token)
2. Access course, evaluation, submission endpoints
3. Verify that responses respect tenancy and policies
4. Attempt to access resources from another school -> should return 403/404

### Recommended Test Data
- **Schools**: 2-3 institutions with different configurations
- **Users per school**:
  - 1 admin
  - 2-3 teachers
  - 5-10 students
  - 2-3 guardians (linked to students)
- **Academic periods**: 1-2 active, some historical
- **Courses**: 5-10 templates, 2-3 offerings per period
- **Evaluations**: 2-3 per course/offering
- **Submissions**: at least one per student per evaluation (with variations: on time, late, not submitted)

### Testing Tools
- **Browsers**: Chrome/Firefox to test web interfaces
- **API Tools**: Postman or Insomnia to test API endpoints
- **Database**: inspect directly to validate isolation and integrity
- **Queues**: use `php artisan queue:work` in development to verify job dispatching
- **Logs**: review `storage/logs/` for debugging

## Next Development Steps (Per PLAN.md)

After completing migrations and models (phases 1-4), the next step is to build the Filament panels (phase 5):
1. Admin Panel: manage schools, users, roles, periods, templates, offerings, enrollments
2. Teacher Panel: manage assigned sections, create evaluations, grade submissions

Afterwards:
- Phase 6: Livewire portals for student and guardian
- Phase 7: Mobile API with Sanctum
- Phase 8: Infrastructure (Redis, Scout, private disks, logging)
- Phase 9: Testing and quality
- Phase 10: Deployment and operations

This document serves as a reference for conducting valid user tests covering the system's critical flows.
</content>
