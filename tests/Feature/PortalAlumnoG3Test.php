<?php

declare(strict_types=1);

use App\Events\EvaluationPublished;
use App\Events\GradePublished;
use App\Filament\Alumno\Pages\Notifications;
use App\Filament\Widgets\ActiveEnrollmentsWidget;
use App\Filament\Widgets\GradeAverageWidget;
use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\Student\EnrollmentStatusChanged;
use App\Notifications\Student\NewEvaluationPublished;
use App\Notifications\Student\SubmissionLateReceived;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(
    RefreshDatabase::class,
);

beforeEach(function (): void {
    $this->school = School::query()->create([
        'name' => 'Escuela G3',
        'email' => 'g3@example.com',
    ]);
    $this->app->make(TenantContext::class)->setSchoolId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->studentRole = Role::firstOrCreate([
        'name' => 'student',
        'school_id' => $this->school->id,
        'guard_name' => 'web',
    ]);

    $this->studentPassword = 'StrongPass1!';
    $this->studentUser1 = User::factory()->createOne([
        'school_id' => $this->school->id,
        'name' => 'Camila G3',
        'email' => 'camila-g3@example.com',
        'password' => Hash::make($this->studentPassword),
    ]);
    $this->studentUser1->assignRole($this->studentRole);
    $this->studentProfile1 = Student::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $this->studentUser1->id,
        'student_id' => 'STU-G3-001',
    ]);

    $this->studentUser2 = User::factory()->createOne([
        'school_id' => $this->school->id,
        'name' => 'Pedro G3',
        'email' => 'pedro-g3@example.com',
        'password' => Hash::make($this->studentPassword),
    ]);
    $this->studentUser2->assignRole($this->studentRole);
    $this->studentProfile2 = Student::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $this->studentUser2->id,
        'student_id' => 'STU-G3-002',
    ]);

    $period = AcademicPeriod::query()->create([
        'school_id' => $this->school->id,
        'name' => '2026 G3',
        'type' => 'semester',
        'starts_at' => now()->subMonth()->toDateString(),
        'ends_at' => now()->addMonths(4)->toDateString(),
    ]);
    $math = CourseTemplate::query()->create([
        'school_id' => $this->school->id,
        'name' => 'Matemáticas G3',
        'code' => 'MAT-G3',
        'default_credits' => 4,
    ]);
    $this->offering = CourseOffering::query()->create([
        'school_id' => $this->school->id,
        'academic_period_id' => $period->id,
        'course_template_id' => $math->id,
        'section_name' => 'A',
        'capacity' => 30,
    ]);

    $this->enrollment1 = Enrollment::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->studentUser1->id,
        'course_offering_id' => $this->offering->id,
        'status' => 'active',
        'enrolled_at' => now(),
    ]);

    $this->evaluation = Evaluation::query()->create([
        'school_id' => $this->school->id,
        'course_offering_id' => $this->offering->id,
        'title' => 'Tarea G3 Evaluación',
        'description' => 'Resolver problemas',
        'max_score' => 100,
        'weight' => 20,
        'due_at' => now()->addDays(3),
        'published_at' => now()->subDay(),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => $this->offering->id,
    ]);
});

it('sends GradePublished + NewEvaluationPublished notifications correctly to student', function (): void {
    Notification::fake();

    $grade = Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentUser1->id,
        'score' => 92,
        'feedback' => 'Excelente trabajo',
    ]);
    event(new GradePublished($grade));

    Notification::assertSentTo(
        $this->studentUser1,
        App\Notifications\Student\GradePublished::class,
    );
    Notification::assertNotSentTo($this->studentUser2, App\Notifications\Student\GradePublished::class);

    event(new EvaluationPublished($this->evaluation));
    Notification::assertSentTo($this->studentUser1, NewEvaluationPublished::class);
});

it('scopes notifications strictly per student (no cross-student leak)', function (): void {
    $gradeForS1 = Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentUser1->id,
        'score' => 90,
    ]);
    $this->studentUser1->notify(new App\Notifications\Student\GradePublished($gradeForS1));

    $evaluationForS2 = Evaluation::query()->create([
        'school_id' => $this->school->id,
        'course_offering_id' => $this->offering->id,
        'title' => 'Tarea S2',
        'description' => 'descr',
        'max_score' => 100,
        'weight' => 10,
        'due_at' => now()->addDays(5),
        'published_at' => now()->subDay(),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => $this->offering->id,
    ]);
    $gradeForS2 = Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $evaluationForS2->id,
        'student_id' => $this->studentUser2->id,
        'score' => 85,
    ]);
    $this->studentUser2->notify(new App\Notifications\Student\GradePublished($gradeForS2));

    expect($this->studentUser1->notifications()->count())->toBe(1);
    expect($this->studentUser2->notifications()->count())->toBe(1);

    $notifForS2 = $this->studentUser2->notifications()->first();
    Gate::forUser($this->studentUser1)->allows('view', $notifForS2);
    expect(Gate::forUser($this->studentUser1)->denies('view', $notifForS2))->toBeTrue();
    expect(Gate::forUser($this->studentUser2)->allows('view', $notifForS2))->toBeTrue();
});

it('unread notifications navigation badge and markAllRead works', function (): void {
    for ($i = 0; $i < 3; $i++) {
        $evalN = Evaluation::query()->create([
            'school_id' => $this->school->id,
            'course_offering_id' => $this->offering->id,
            'title' => 'Tarea badge '.$i,
            'description' => 'descr',
            'max_score' => 100,
            'weight' => 5,
            'due_at' => now()->addDays(2 + $i),
            'published_at' => now()->subDay(),
            'evaluationable_type' => CourseOffering::class,
            'evaluationable_id' => $this->offering->id,
        ]);
        $g = Grade::query()->create([
            'school_id' => $this->school->id,
            'evaluation_id' => $evalN->id,
            'student_id' => $this->studentUser1->id,
            'score' => 80 + $i,
        ]);
        $this->studentUser1->notify(new App\Notifications\Student\GradePublished($g));
    }

    Auth::login($this->studentUser1);

    expect($this->studentUser1->unreadNotifications()->count())->toBe(3);
    $badge = Notifications::getNavigationBadge();
    expect($badge)->toBe('3');
    expect(Notifications::getNavigationBadgeColor())->toBe('danger');

    $this->studentUser1->unreadNotifications()->update(['read_at' => now()]);
    $this->studentUser1->refresh();

    expect($this->studentUser1->unreadNotifications()->count())->toBe(0);
    expect(Notifications::getNavigationBadge())->toBeNull();
});

it('dashboard GPA and active enrollment widget returns correct values', function (): void {
    Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentUser1->id,
        'score' => 70,
    ]);

    $evalGpa2 = Evaluation::query()->create([
        'school_id' => $this->school->id,
        'course_offering_id' => $this->offering->id,
        'title' => 'GPA evaluation 2',
        'description' => 'second',
        'max_score' => 100,
        'weight' => 20,
        'due_at' => now()->addDays(4),
        'published_at' => now()->subDays(2),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => $this->offering->id,
    ]);
    Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $evalGpa2->id,
        'student_id' => $this->studentUser1->id,
        'score' => 90,
    ]);

    Auth::login($this->studentUser1);

    $gradeWidget = app(GradeAverageWidget::class);
    $stats = $gradeWidget->getStats();
    expect($stats)->toBeArray();
    expect($stats)->toHaveCount(1);
    expect($stats[0]->getLabel())->toBe(__('widgets.gpa_title'));
    $value = $stats[0]->getValue();
    expect($value)->toContain('%');

    $activeEnrollWidget = app(ActiveEnrollmentsWidget::class);
    $stats2 = $activeEnrollWidget->getStats();
    expect($stats2[0]->getValue())->toBe('1');
});

it('sends EnrollmentStatusChanged on enrollment status update via observer', function (): void {
    Notification::fake();

    $this->enrollment1->update(['status' => 'completed']);

    Notification::assertSentTo(
        $this->studentUser1,
        EnrollmentStatusChanged::class,
    );
});

it('sends SubmissionLateReceived when student submits after deadline', function (): void {
    Notification::fake();
    $this->evaluation->update(['due_at' => now()->subDays(2)]);

    Submission::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentUser1->id,
        'status' => 'submitted',
        'attempt' => 1,
        'late_flag' => true,
        'submitted_at' => now(),
    ]);

    Notification::assertSentTo($this->studentUser1, SubmissionLateReceived::class);
});

it('guest cannot view widgets and notifications page', function (): void {
    expect(GradeAverageWidget::canView())->toBeFalse();
    expect(Notifications::canAccess())->toBeFalse();

    Auth::login($this->studentUser1);
    expect(GradeAverageWidget::canView())->toBeTrue();
    expect(Notifications::canAccess())->toBeTrue();
});
