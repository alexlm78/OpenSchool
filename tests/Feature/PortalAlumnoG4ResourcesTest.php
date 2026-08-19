<?php

declare(strict_types=1);

use App\Filament\AlumnoResources\Enrollments\EnrollmentResource;
use App\Filament\AlumnoResources\Enrollments\Tables\EnrollmentsTable;
use App\Filament\AlumnoResources\Evaluations\EvaluationResource;
use App\Filament\AlumnoResources\Evaluations\Tables\EvaluationsTable;
use App\Filament\AlumnoResources\Grades\GradeResource;
use App\Filament\AlumnoResources\Grades\Tables\GradesTable;
use App\Filament\AlumnoResources\Submissions\SubmissionResource;
use App\Filament\AlumnoResources\Submissions\Tables\SubmissionsTable;
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
use App\Tenancy\TenantContext;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable as HasTableContract;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(
    RefreshDatabase::class,
);

beforeEach(function (): void {
    $this->school = School::query()->create([
        'name' => 'Escuela G4',
        'email' => 'g4@example.com',
    ]);
    $this->app->make(TenantContext::class)->setSchoolId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->studentRole = Role::firstOrCreate([
        'name' => 'student',
        'school_id' => $this->school->id,
        'guard_name' => 'web',
    ]);

    $pass = 'StrongPass1!';
    $this->studentA = User::factory()->createOne([
        'school_id' => $this->school->id,
        'name' => 'Ana G4',
        'email' => 'ana-g4@example.com',
        'password' => Hash::make($pass),
    ]);
    $this->studentA->assignRole($this->studentRole);
    Student::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $this->studentA->id,
        'student_id' => 'STU-G4-A',
    ]);

    $this->studentB = User::factory()->createOne([
        'school_id' => $this->school->id,
        'name' => 'Bruno G4',
        'email' => 'bruno-g4@example.com',
        'password' => Hash::make($pass),
    ]);
    $this->studentB->assignRole($this->studentRole);
    Student::query()->create([
        'school_id' => $this->school->id,
        'user_id' => $this->studentB->id,
        'student_id' => 'STU-G4-B',
    ]);

    $period = AcademicPeriod::query()->create([
        'school_id' => $this->school->id,
        'name' => '2026 G4',
        'type' => 'semester',
        'starts_at' => now()->subMonth()->toDateString(),
        'ends_at' => now()->addMonths(4)->toDateString(),
    ]);
    $tpl = CourseTemplate::query()->create([
        'school_id' => $this->school->id,
        'name' => 'Física G4',
        'code' => 'FIS-G4',
        'default_credits' => 4,
    ]);
    $this->offering = CourseOffering::query()->create([
        'school_id' => $this->school->id,
        'academic_period_id' => $period->id,
        'course_template_id' => $tpl->id,
        'section_name' => 'A',
        'capacity' => 30,
    ]);

    $this->offeringForeign = CourseOffering::query()->create([
        'school_id' => $this->school->id,
        'academic_period_id' => $period->id,
        'course_template_id' => $tpl->id,
        'section_name' => 'Z',
        'capacity' => 30,
    ]);

    $this->enrollmentA = Enrollment::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->studentA->id,
        'course_offering_id' => $this->offering->id,
        'status' => 'active',
        'enrolled_at' => now()->subDays(10),
    ]);
    $this->enrollmentB = Enrollment::query()->create([
        'school_id' => $this->school->id,
        'student_id' => $this->studentB->id,
        'course_offering_id' => $this->offering->id,
        'status' => 'active',
        'enrolled_at' => now()->subDays(9),
    ]);

    $this->evaluation = Evaluation::query()->create([
        'school_id' => $this->school->id,
        'course_offering_id' => $this->offering->id,
        'title' => 'Tarea G4 Física',
        'description' => 'Problemas MRU',
        'max_score' => 100,
        'weight' => 20,
        'due_at' => now()->addDays(4),
        'published_at' => now()->subDays(2),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => $this->offering->id,
    ]);
});

$dummyTablesComponent = function (): Component&HasTableContract {
    return new class extends Component implements HasSchemas, HasTableContract
    {
        use InteractsWithSchemas;
        use InteractsWithTable;

        /**
         * @var string|Htmlable|array<string>|null
         */
        public static string|Htmlable|array|null $translatableContentDriver = null;

        public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
        {
            return null;
        }

        public function render(): never
        {
            throw new RuntimeException('Not renderable: fixture only for table() make context.');
        }
    };
};

it('canViewAny enforces student role for 4 Alumno Resources', function (): void {
    expect(EnrollmentResource::canViewAny())->toBeFalse();
    expect(EvaluationResource::canViewAny())->toBeFalse();
    expect(GradeResource::canViewAny())->toBeFalse();
    expect(SubmissionResource::canViewAny())->toBeFalse();

    Auth::login($this->studentA);
    expect(EnrollmentResource::canViewAny())->toBeTrue();
    expect(EvaluationResource::canViewAny())->toBeTrue();
    expect(GradeResource::canViewAny())->toBeTrue();
    expect(SubmissionResource::canViewAny())->toBeTrue();
});

it('enrollment modifyQueryUsing scopes strictly to logged student', function () use ($dummyTablesComponent): void {
    Auth::login($this->studentA);

    $resource = EnrollmentResource::class;
    $baseQuery = Enrollment::query();
    $table = $resource::table(Table::make($dummyTablesComponent()));
    $table->applyQueryScopes($baseQuery);
    $ids = $baseQuery->pluck('id')->all();

    expect($ids)->toBe([(int) $this->enrollmentA->getKey()]);
});

it('evaluation modifyQueryUsing scopes via enrolled active offerings', function () use ($dummyTablesComponent): void {
    Auth::login($this->studentA);

    $evaluationForeign = Evaluation::query()->create([
        'school_id' => $this->school->id,
        'course_offering_id' => $this->offeringForeign->id,
        'title' => 'Tarea sección Z no inscrita',
        'description' => '',
        'max_score' => 100,
        'weight' => 10,
        'due_at' => now()->addDay(),
        'published_at' => now(),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => $this->offeringForeign->id,
    ]);

    $resource = EvaluationResource::class;
    $baseQuery = Evaluation::query();
    $table = $resource::table(Table::make($dummyTablesComponent()));
    $table->applyQueryScopes($baseQuery);
    $titles = $baseQuery->pluck('title')->all();

    expect($titles)->toContain('Tarea G4 Física');
    expect($titles)->not()->toContain($evaluationForeign->getAttributeValue('title'));
});

it('grades list modifyQueryUsing returns only student A grades and scope leak denied via Policy', function () use ($dummyTablesComponent): void {
    $gradeA = Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentA->id,
        'score' => 95,
        'feedback' => 'Muy bien',
    ]);
    $gradeB = Grade::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentB->id,
        'score' => 78,
        'feedback' => 'Bien',
    ]);

    Auth::login($this->studentA);
    $resource = GradeResource::class;
    $baseQuery = Grade::query();
    $table = $resource::table(Table::make($dummyTablesComponent()));
    $table->applyQueryScopes($baseQuery);
    $gradeIds = $baseQuery->pluck('id')->all();

    expect($gradeIds)->toBe([(int) $gradeA->getKey()]);
    expect(Gate::forUser($this->studentA)->allows('view', $gradeA))->toBeTrue();
    expect(Gate::forUser($this->studentA)->denies('view', $gradeB))->toBeTrue();
});

it('submission list scoped per student + pagination options are 10/25/50', function () use ($dummyTablesComponent): void {
    $submissionA = Submission::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentA->id,
        'status' => 'submitted',
        'attempt' => 1,
        'late_flag' => true,
        'submitted_at' => now()->subMinute(),
    ]);
    Submission::query()->create([
        'school_id' => $this->school->id,
        'evaluation_id' => $this->evaluation->id,
        'student_id' => $this->studentB->id,
        'status' => 'submitted',
        'attempt' => 1,
        'late_flag' => false,
        'submitted_at' => now()->subMinute(),
    ]);

    Auth::login($this->studentA);
    $resource = SubmissionResource::class;
    $baseQuery = Submission::query();
    $table = $resource::table(Table::make($dummyTablesComponent()));
    $table->applyQueryScopes($baseQuery);
    $submissionIds = $baseQuery->pluck('id')->all();
    expect($submissionIds)->toBe([(int) $submissionA->getKey()]);

    expect($table->getPaginationPageOptions())->toBe([10, 25, 50]);
});

it('4 Table classes expose paginationPageOptions [10, 25, 50]', function () use ($dummyTablesComponent): void {
    $lw = $dummyTablesComponent();

    $enrollmentsTable = EnrollmentsTable::configure(Table::make($lw));
    $submissionsTable = SubmissionsTable::configure(Table::make($lw));
    $evaluationsTable = EvaluationsTable::configure(Table::make($lw));
    $gradesTable = GradesTable::configure(Table::make($lw));

    expect($enrollmentsTable->getPaginationPageOptions())->toBe([10, 25, 50]);
    expect($submissionsTable->getPaginationPageOptions())->toBe([10, 25, 50]);
    expect($evaluationsTable->getPaginationPageOptions())->toBe([10, 25, 50]);
    expect($gradesTable->getPaginationPageOptions())->toBe([10, 25, 50]);
});

it('all 4 resource canCreate/canEdit/canDelete return false for students', function (): void {
    Auth::login($this->studentA);

    $resources = [
        EnrollmentResource::class,
        EvaluationResource::class,
        GradeResource::class,
    ];
    foreach ($resources as $resource) {
        expect($resource::canCreate())->toBeFalse("{$resource} canCreate");
        expect($resource::canEdit(null))->toBeFalse("{$resource} canEdit");
        expect($resource::canDelete(null))->toBeFalse("{$resource} canDelete");
    }

    expect(SubmissionResource::canCreate())->toBeTrue();
    expect(SubmissionResource::canEdit(null))->toBeFalse();
    expect(SubmissionResource::canDelete(null))->toBeFalse();
});
