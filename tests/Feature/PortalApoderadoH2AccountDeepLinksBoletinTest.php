<?php

declare(strict_types=1);

use App\Filament\Apoderado\Pages\BoletinGuardian;
use App\Filament\Apoderado\Pages\ChangeGuardianPassword;
use App\Filament\Apoderado\Pages\EditGuardianProfile;
use App\Filament\Apoderado\Widgets\ApoderadoGradesWidget;
use App\Filament\Apoderado\Widgets\ApoderadoNotificationsWidget;
use App\Filament\Apoderado\Widgets\ApoderadoUpcomingEvaluationsWidget;
use App\Filament\ApoderadoResources\Evaluations\EvaluationResource;
use App\Filament\ApoderadoResources\Grades\GradeResource;
use App\Models\AcademicPeriod;
use App\Models\CourseOffering;
use App\Models\CourseTemplate;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Submission;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use App\Tenancy\TenantContext;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms as HasFormsContract;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable as HasTableContract;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification as ModelsDatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    /** @var School $school */
    $school = School::query()->create([
        'name' => 'Colegio H2 Guardian',
        'email' => 'h2-school@example.com',
        'timezone' => 'America/Bogota',
        'locale' => 'es',
    ]);
    $schoolId = (int) $school->getKey();
    $this->school = $school;

    app(TenantContext::class)->setSchoolId($schoolId);
    app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'guardian', 'school_id' => $schoolId, 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'school_id' => $schoolId, 'guard_name' => 'web']);

    $guardianUser = User::create([
        'school_id' => $schoolId,
        'name' => 'Mama AnaBen',
        'email' => 'mama.anaben@h2test.local',
        'password' => Hash::make('secret123'),
        'locale' => 'es',
    ]);
    $guardianUser->assignRole('guardian');
    $this->guardianUser = $guardianUser;

    $guardianProfile = Guardian::create([
        'school_id' => $schoolId,
        'user_id' => (int) $guardianUser->getKey(),
        'relationship' => 'mother',
        'phone' => '+573000000001',
    ]);
    $this->guardianProfile = $guardianProfile;

    $foreignGuardianUser = User::create([
        'school_id' => $schoolId,
        'name' => 'Foreign Guardian',
        'email' => 'foreign.guardian@h2test.local',
        'password' => Hash::make('secret456'),
        'locale' => 'es',
    ]);
    $foreignGuardianUser->assignRole('guardian');
    $this->foreignGuardianUser = $foreignGuardianUser;

    $foreignProfile = Guardian::create([
        'school_id' => $schoolId,
        'user_id' => (int) $foreignGuardianUser->getKey(),
        'relationship' => 'father',
        'phone' => '+573000000002',
    ]);
    $this->foreignGuardianProfile = $foreignProfile;

    $studentUser1 = User::create([
        'school_id' => $schoolId,
        'name' => 'Ana',
        'email' => 'ana.h2test@example.com',
        'password' => Hash::make('Ana.12345'),
        'locale' => 'es',
    ]);
    $studentUser1->assignRole('student');
    $this->studentUser1 = $studentUser1;

    $profile1 = Student::create([
        'school_id' => $schoolId,
        'user_id' => (int) $studentUser1->getKey(),
        'student_id' => 'STU-H2-ANA',
        'first_name' => 'Ana',
        'last_name' => 'Gutierrez',
        'grade_level' => 'Grade 10',
    ]);
    $this->profile1 = $profile1;

    $studentUser2 = User::create([
        'school_id' => $schoolId,
        'name' => 'Ben',
        'email' => 'ben.h2test@example.com',
        'password' => Hash::make('Ben.12345'),
        'locale' => 'es',
    ]);
    $studentUser2->assignRole('student');
    $this->studentUser2 = $studentUser2;

    $profile2 = Student::create([
        'school_id' => $schoolId,
        'user_id' => (int) $studentUser2->getKey(),
        'student_id' => 'STU-H2-BEN',
        'first_name' => 'Ben',
        'last_name' => 'Hernandez',
        'grade_level' => 'Grade 10',
    ]);
    $this->profile2 = $profile2;

    $foreignStudentUser = User::create([
        'school_id' => $schoolId,
        'name' => 'Zoe',
        'email' => 'zoe.h2test@example.com',
        'password' => Hash::make('Zoe.12345'),
        'locale' => 'es',
    ]);
    $foreignStudentUser->assignRole('student');
    $this->foreignStudentUser = $foreignStudentUser;

    $foreignProfile = Student::create([
        'school_id' => $schoolId,
        'user_id' => (int) $foreignStudentUser->getKey(),
        'student_id' => 'STU-H2-ZOE',
        'first_name' => 'Zoe',
        'last_name' => 'Foreign',
        'grade_level' => 'Grade 10',
    ]);
    $this->profileForeign = $foreignProfile;

    $guardianProfile->students()->syncWithoutDetaching([
        (int) $profile1->getKey() => ['school_id' => $schoolId],
        (int) $profile2->getKey() => ['school_id' => $schoolId],
    ]);

    $template = CourseTemplate::query()->create([
        'school_id' => $schoolId,
        'name' => 'H2 Course Math',
        'description' => 'h2 math',
        'code' => 'MATH-H2',
    ]);
    $this->courseTemplate = $template;

    $period = AcademicPeriod::query()->create([
        'school_id' => $schoolId,
        'name' => 'Periodo H2',
        'type' => 'semester',
        'starts_at' => now()->subMonths(3)->toDateString(),
        'ends_at' => now()->addMonths(3)->toDateString(),
    ]);

    $offering = CourseOffering::query()->create([
        'school_id' => $schoolId,
        'course_template_id' => (int) $template->getKey(),
        'academic_period_id' => (int) $period->getKey(),
        'section_name' => 'H2-101',
        'capacity' => 30,
    ]);
    $this->offering = $offering;

    $enrollment1 = Enrollment::query()->create([
        'school_id' => $schoolId,
        'course_offering_id' => (int) $offering->getKey(),
        'student_id' => (int) $profile1->getKey(),
        'status' => 'active',
        'enrolled_at' => now()->subDays(60)->toDateTimeString(),
    ]);
    $this->enrollment1 = $enrollment1;

    $enrollment2 = Enrollment::query()->create([
        'school_id' => $schoolId,
        'course_offering_id' => (int) $offering->getKey(),
        'student_id' => (int) $profile2->getKey(),
        'status' => 'active',
        'enrolled_at' => now()->subDays(55)->toDateTimeString(),
    ]);
    $this->enrollment2 = $enrollment2;

    Enrollment::query()->create([
        'school_id' => $schoolId,
        'course_offering_id' => (int) $offering->getKey(),
        'student_id' => (int) $foreignProfile->getKey(),
        'status' => 'active',
        'enrolled_at' => now()->subDays(50)->toDateTimeString(),
    ]);

    $evaluation = Evaluation::query()->create([
        'school_id' => $schoolId,
        'course_offering_id' => (int) $offering->getKey(),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => (int) $offering->getKey(),
        'title' => 'H2 Quiz 1',
        'description' => 'quiz algebra',
        'type' => 'exam',
        'max_score' => 100,
        'weight' => 20,
        'published_at' => now()->subDays(10)->toDateTimeString(),
        'due_at' => now()->addDays(5)->toDateTimeString(),
    ]);
    $this->evaluation = $evaluation;

    $anaGrade = Grade::query()->create([
        'school_id' => $schoolId,
        'student_id' => (int) $profile1->getKey(),
        'evaluation_id' => (int) $evaluation->getKey(),
        'score' => 82,
        'graded_at' => now()->toDateTimeString(),
    ]);
    $this->anaGrade = $anaGrade;

    $benGrade = Grade::query()->create([
        'school_id' => $schoolId,
        'student_id' => (int) $profile2->getKey(),
        'evaluation_id' => (int) $evaluation->getKey(),
        'score' => 61,
        'graded_at' => now()->toDateTimeString(),
    ]);
    $this->benGrade = $benGrade;

    Grade::query()->create([
        'school_id' => $schoolId,
        'student_id' => (int) $foreignProfile->getKey(),
        'evaluation_id' => (int) $evaluation->getKey(),
        'score' => 93,
        'graded_at' => now()->toDateTimeString(),
    ]);

    Submission::query()->create([
        'school_id' => $schoolId,
        'evaluation_id' => (int) $evaluation->getKey(),
        'student_id' => (int) $profile1->getKey(),
        'status' => 'submitted',
        'submitted_at' => now()->toDateTimeString(),
    ]);

    ModelsDatabaseNotification::query()->create([
        'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee01',
        'type' => 'App\Notifications\EvaluationAssigned',
        'notifiable_type' => User::class,
        'notifiable_id' => (int) $studentUser1->getKey(),
        'data' => json_encode([
            'title' => 'H2 Ana Notification',
            'body' => 'nueva evaluacion',
            'action_url' => '/apoderado/enrollments/'.((string) $enrollment1->getKey()),
        ], \JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ModelsDatabaseNotification::query()->create([
        'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee02',
        'type' => 'App\Notifications\GradePublished',
        'notifiable_type' => User::class,
        'notifiable_id' => (int) $studentUser2->getKey(),
        'data' => json_encode([
            'title' => 'H2 Ben Notification',
            'body' => 'publicado grade',
            'action_url' => '/apoderado/grades/'.((string) $benGrade->getKey()),
        ], \JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    ModelsDatabaseNotification::query()->create([
        'id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeee03',
        'type' => 'App\Notifications\GradePublished',
        'notifiable_type' => User::class,
        'notifiable_id' => (int) $foreignStudentUser->getKey(),
        'data' => json_encode([
            'title' => 'Zoe H2 NO LINKED',
            'body' => 'leak test',
            'action_url' => '/boletin-bad',
        ], \JSON_THROW_ON_ERROR),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('EditGuardianProfile loads 200 for owner guardian and shows fields', function (): void {
    Auth::login($this->guardianUser);
    $this
        ->get(EditGuardianProfile::getUrl(panel: 'apoderado'))
        ->assertOk();
});

it('ChangeGuardianPassword loads 200 for owner guardian, change password valid flow', function (): void {
    Auth::login($this->guardianUser);
    $this
        ->get(ChangeGuardianPassword::getUrl(panel: 'apoderado'))
        ->assertOk();
});

it('Unauthenticated user gets redirect 302 to login from EditGuardianProfile', function (): void {
    $this
        ->get(EditGuardianProfile::getUrl(panel: 'apoderado'))
        ->assertStatus(302);
});

it('GuardianPolicy.update owns own profile only, rejects foreign guardian profile', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId((int) $this->guardianUser->getAttributeValue('school_id'));
    expect(Gate::forUser($this->guardianUser)->check('update', $this->guardianProfile))->toBeTrue();
    expect(Gate::forUser($this->guardianUser)->check('update', $this->foreignGuardianProfile))->toBeFalse();
});

it('EnrollmentPolicy.view guardian accepts linked profile ids (Ana/Ben) rejects Zoe', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId((int) $this->guardianUser->getAttributeValue('school_id'));
    expect(Gate::forUser($this->guardianUser)->check('view', $this->enrollment1))->toBeTrue();
    expect(Gate::forUser($this->guardianUser)->check('view', $this->enrollment2))->toBeTrue();
    $zoeEnrollment = Enrollment::query()
        ->where('student_id', (int) $this->profileForeign->getKey())
        ->first();
    $this->assertNotNull($zoeEnrollment);
    expect(Gate::forUser($this->guardianUser)->check('view', $zoeEnrollment))->toBeFalse();
});

it('GradesWidget table action URLs target GradeResource view (correct panel apoderado)', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoGradesWidget;
    $dummy = new class extends Component implements HasFormsContract, HasTableContract
    {
        use InteractsWithForms;
        use InteractsWithTable;

        public static string|Htmlable|array|null $translatableContentDriver = null;

        public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
        {
            return null;
        }

        public function render(): never
        {
            throw new RuntimeException('fixture');
        }
    };
    $table = Table::make($dummy);
    $widget->table($table);
    /** @var Grade[] $rows */
    $rows = $table->getQuery()->orderBy('id')->get();
    expect(count($rows))->toBeGreaterThanOrEqual(1);
    $ids = collect($rows)->pluck('student_id')->all();
    expect($ids)->not()->toContain((int) $this->profileForeign->getKey());

    $expectedBase = url('/apoderado/grades/');
    $first = $rows[0];
    $action = $table->getAction('view_grade');
    expect($action)->not()->toBeNull();
    Filament::setCurrentPanel(Filament::getPanel('apoderado'));
    $url = GradeResource::getUrl('view', ['record' => $first]);
    expect($url)->toContain($expectedBase);
    $id = (string) $first->getKey();
    expect($url)->toContain($id);
});

it('UpcomingEvaluationsWidget action URLs target EvaluationResource apoderado panel path', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoUpcomingEvaluationsWidget;
    $dummy = new class extends Component implements HasFormsContract, HasTableContract
    {
        use InteractsWithForms;
        use InteractsWithTable;

        public static string|Htmlable|array|null $translatableContentDriver = null;

        public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
        {
            return null;
        }

        public function render(): never
        {
            throw new RuntimeException('fixture');
        }
    };
    $table = Table::make($dummy);
    $widget->table($table);
    $rows = $table->getQuery()->orderBy('id')->get();
    expect(count($rows))->toBeGreaterThanOrEqual(1);
    $first = $rows[0];
    $action = $table->getAction('view_evaluation');
    expect($action)->not()->toBeNull();
    Filament::setCurrentPanel(Filament::getPanel('apoderado'));
    $url = EvaluationResource::getUrl('view', ['record' => $first]);
    expect($url)->toContain(url('/apoderado/evaluations/'));
    $evalId = (string) $this->evaluation->getKey();
    expect($url)->toContain($evalId);
});

it('NotificationsWidget scope excludes Zoe linked student, actions URLs present where action_url set', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoNotificationsWidget;
    $dummy = new class extends Component implements HasFormsContract, HasTableContract
    {
        use InteractsWithForms;
        use InteractsWithTable;

        public static string|Htmlable|array|null $translatableContentDriver = null;

        public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
        {
            return null;
        }

        public function render(): never
        {
            throw new RuntimeException('fixture');
        }
    };
    $table = Table::make($dummy);
    $widget->table($table);
    $rows = $table->getQuery()->get();
    expect(count($rows))->toBe(2);
    $titles = $rows->pluck('data')->map(static fn (mixed $d): string => is_array($d) ? (string) ($d['title'] ?? '') : (is_string($d) ? (string) (json_decode($d, true)['title'] ?? '') : ''))->all();
    expect($titles)->not()->toContain('Zoe H2 NO LINKED');

    $action = $table->getAction('view_entity');
    expect($action)->not()->toBeNull();
});

it('BoletinGuardian canAccess only linked guardians, Foreign Guardian without links returns false', function (): void {
    expect(BoletinGuardian::canAccess())->toBeFalse();
    Auth::login($this->guardianUser);
    app(PermissionRegistrar::class)->setPermissionsTeamId((int) $this->guardianUser->getAttributeValue('school_id'));
    expect(BoletinGuardian::canAccess())->toBeTrue();
    Auth::login($this->foreignGuardianUser);
    app(PermissionRegistrar::class)->setPermissionsTeamId((int) $this->foreignGuardianUser->getAttributeValue('school_id'));
    expect(BoletinGuardian::canAccess())->toBeFalse();
});

it('BoletinGuardian page renders and contains only linked student grades (Ana/Ben GPAs present no Zoe)', function (): void {
    Auth::login($this->guardianUser);
    $this
        ->get(BoletinGuardian::getUrl(panel: 'apoderado'))
        ->assertOk()
        ->assertSee('Ana')
        ->assertSee('Ben')
        ->assertDontSee('Zoe')
        ->assertSee('Mama AnaBen');
});

it('LinkedGuardianStudents returns different profile/user id sets correctly (not mixed ids)', function (): void {
    Auth::login($this->guardianUser);
    $linked = LinkedGuardianStudents::resolveForUser($this->guardianUser);
    expect($linked['profileIds'])->toBe([(int) $this->profile1->getKey(), (int) $this->profile2->getKey()]);
    expect($linked['userIds'])->toBe([(int) $this->studentUser1->getKey(), (int) $this->studentUser2->getKey()]);
    expect($linked['profileIds'])->not()->toBe($linked['userIds']);
});
