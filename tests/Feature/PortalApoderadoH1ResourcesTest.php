<?php

declare(strict_types=1);

use App\Filament\Apoderado\Pages\Notifications as ApoderadoNotificationsPage;
use App\Filament\Apoderado\Widgets\ApoderadoGpaStatWidget;
use App\Filament\Apoderado\Widgets\ApoderadoGradesWidget;
use App\Filament\Apoderado\Widgets\ApoderadoLinkedStudentsWidget;
use App\Filament\Apoderado\Widgets\ApoderadoNotificationsWidget;
use App\Filament\ApoderadoResources\Enrollments\EnrollmentResource;
use App\Filament\ApoderadoResources\Evaluations\EvaluationResource;
use App\Filament\ApoderadoResources\Grades\GradeResource;
use App\Filament\ApoderadoResources\Students\StudentResource;
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
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable as HasTableContract;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
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
    $school = School::query()->create([
        'name' => 'Colegio H1 Guardian',
        'email' => 'h1-school@example.com',
        'timezone' => 'America/Bogota',
        'locale' => 'es',
    ]);
    app(TenantContext::class)->setSchoolId((int) $school->getKey());
    app(PermissionRegistrar::class)->setPermissionsTeamId((int) $school->getKey());
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $guardianRole = Role::firstOrCreate([
        'name' => 'guardian',
        'school_id' => (int) $school->getKey(),
        'guard_name' => 'web',
    ]);
    $studentRole = Role::firstOrCreate([
        'name' => 'student',
        'school_id' => (int) $school->getKey(),
        'guard_name' => 'web',
    ]);

    $guardianUser = User::query()->create([
        'name' => 'Carlos Padre H1',
        'email' => 'carlos-h1@example.com',
        'password' => Hash::make('Secret123!'),
        'school_id' => (int) $school->getKey(),
    ]);
    $guardianUser->assignRole($guardianRole);

    $foreignGuardianUser = User::query()->create([
        'name' => 'Forastero H1',
        'email' => 'forastero-h1@example.com',
        'password' => Hash::make('Secret123!'),
        'school_id' => (int) $school->getKey(),
    ]);
    $foreignGuardianUser->assignRole($guardianRole);

    $guardianProfile = Guardian::query()->create([
        'school_id' => (int) $school->getKey(),
        'user_id' => (int) $guardianUser->getKey(),
        'relationship_type' => 'father',
        'phone' => '3000000001',
    ]);
    Guardian::query()->create([
        'school_id' => (int) $school->getKey(),
        'user_id' => (int) $foreignGuardianUser->getKey(),
        'relationship_type' => 'mother',
        'phone' => '3000000002',
    ]);

    $studentA = User::query()->create([
        'name' => 'Ana Hija H1',
        'email' => 'ana-h1@example.com',
        'password' => Hash::make('Secret123!'),
        'school_id' => (int) $school->getKey(),
    ]);
    $studentA->assignRole($studentRole);
    $profileA = Student::query()->create([
        'user_id' => (int) $studentA->getKey(),
        'school_id' => (int) $school->getKey(),
        'student_id' => 'MAT-H1-ANA',
    ]);

    $studentB = User::query()->create([
        'name' => 'Ben Hijo H1',
        'email' => 'ben-h1@example.com',
        'password' => Hash::make('Secret123!'),
        'school_id' => (int) $school->getKey(),
    ]);
    $studentB->assignRole($studentRole);
    $profileB = Student::query()->create([
        'user_id' => (int) $studentB->getKey(),
        'school_id' => (int) $school->getKey(),
        'student_id' => 'MAT-H1-BEN',
    ]);

    $studentForeign = User::query()->create([
        'name' => 'Zoe Extraña H1',
        'email' => 'zoe-h1@example.com',
        'password' => Hash::make('Secret123!'),
        'school_id' => (int) $school->getKey(),
    ]);
    $studentForeign->assignRole($studentRole);
    $profileForeign = Student::query()->create([
        'user_id' => (int) $studentForeign->getKey(),
        'school_id' => (int) $school->getKey(),
        'student_id' => 'MAT-H1-ZOE',
    ]);

    $guardianProfile->students()->syncWithoutDetaching([
        (int) $profileA->getKey() => ['school_id' => (int) $school->getKey()],
        (int) $profileB->getKey() => ['school_id' => (int) $school->getKey()],
    ]);

    $course = CourseTemplate::query()->create([
        'school_id' => (int) $school->getKey(),
        'name' => 'Matemáticas H1',
        'description' => 'Curso prueba H1',
        'code' => 'MAT-H1',
    ]);
    $period = AcademicPeriod::query()->create([
        'school_id' => (int) $school->getKey(),
        'name' => 'Periodo H1',
        'type' => 'semester',
        'starts_at' => now()->subMonths(3)->toDateString(),
        'ends_at' => now()->addMonths(3)->toDateString(),
    ]);
    $offering = CourseOffering::query()->create([
        'school_id' => (int) $school->getKey(),
        'course_template_id' => (int) $course->getKey(),
        'academic_period_id' => (int) $period->getKey(),
        'section_name' => 'H1-101',
        'capacity' => 30,
    ]);

    $enrollmentA = Enrollment::query()->create([
        'school_id' => (int) $school->getKey(),
        'student_id' => (int) $profileA->getKey(),
        'course_offering_id' => (int) $offering->getKey(),
        'status' => 'active',
        'enrolled_at' => now()->subDays(60),
    ]);
    $enrollmentB = Enrollment::query()->create([
        'school_id' => (int) $school->getKey(),
        'student_id' => (int) $profileB->getKey(),
        'course_offering_id' => (int) $offering->getKey(),
        'status' => 'active',
        'enrolled_at' => now()->subDays(55),
    ]);
    Enrollment::query()->create([
        'school_id' => (int) $school->getKey(),
        'student_id' => (int) $profileForeign->getKey(),
        'course_offering_id' => (int) $offering->getKey(),
        'status' => 'active',
        'enrolled_at' => now()->subDays(50),
    ]);

    $evaluation = Evaluation::query()->create([
        'school_id' => (int) $school->getKey(),
        'course_offering_id' => (int) $offering->getKey(),
        'evaluationable_type' => CourseOffering::class,
        'evaluationable_id' => (int) $offering->getKey(),
        'title' => 'Parcial Matemáticas H1',
        'description' => 'Parcial',
        'type' => 'exam',
        'max_score' => 100,
        'weight' => 30,
        'published_at' => now()->subDays(10),
        'due_at' => now()->addDays(2),
    ]);

    Grade::query()->create([
        'school_id' => (int) $school->getKey(),
        'evaluation_id' => (int) $evaluation->getKey(),
        'student_id' => (int) $profileA->getKey(),
        'grader_id' => (int) $guardianUser->getKey(),
        'score' => 82,
        'feedback' => 'Bien Ana',
        'graded_at' => now()->subMinute(),
    ]);
    Grade::query()->create([
        'school_id' => (int) $school->getKey(),
        'evaluation_id' => (int) $evaluation->getKey(),
        'student_id' => (int) $profileForeign->getKey(),
        'grader_id' => (int) $guardianUser->getKey(),
        'score' => 44,
        'feedback' => 'Mal Zoe',
        'graded_at' => now()->subMinutes(2),
    ]);
    Submission::query()->create([
        'school_id' => (int) $school->getKey(),
        'evaluation_id' => (int) $evaluation->getKey(),
        'student_id' => (int) $profileA->getKey(),
        'status' => 'submitted',
        'late_flag' => false,
        'submitted_at' => now()->subDays(5),
    ]);

    $studentA->notify(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toDatabase(object $notifiable): array
        {
            return [
                'title' => 'Notif Ana 1',
                'summary' => 'sum ana',
                'category' => 'grade',
                'level' => 'success',
                'student_name' => 'Ana Hija H1',
                'action_url' => '/apoderado/grades',
            ];
        }
    });
    $studentA->notify(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toDatabase(object $notifiable): array
        {
            return [
                'title' => 'Notif Ana 2',
                'summary' => 'sum ana 2',
                'category' => 'evaluation',
                'level' => 'info',
                'student_name' => 'Ana Hija H1',
                'action_url' => '/apoderado/evaluations',
            ];
        }
    });
    $studentB->notify(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toDatabase(object $notifiable): array
        {
            return [
                'title' => 'Notif Ben 1',
                'summary' => 'sum ben',
                'category' => 'submission',
                'level' => 'warning',
                'student_name' => 'Ben Hijo H1',
                'action_url' => '/apoderado/submissions',
            ];
        }
    });
    $studentForeign->notify(new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toDatabase(object $notifiable): array
        {
            return [
                'title' => 'Zoe NO LINKED',
                'summary' => 'no acceder',
                'category' => 'enrollment',
                'level' => 'danger',
                'student_name' => 'Zoe Extraña H1',
                'action_url' => '/apoderado/enrollments',
            ];
        }
    });

    $this->school = $school;
    $this->guardianRole = $guardianRole;
    $this->guardianUser = $guardianUser;
    $this->foreignGuardianUser = $foreignGuardianUser;
    $this->studentA = $studentA;
    $this->studentB = $studentB;
    $this->studentForeign = $studentForeign;
    $this->profileA = $profileA;
    $this->profileB = $profileB;
    $this->profileForeign = $profileForeign;
    $this->offering = $offering;
    $this->evaluation = $evaluation;
    $this->enrollmentA = $enrollmentA;
    $this->enrollmentB = $enrollmentB;
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
            throw new RuntimeException('Not renderable fixture.');
        }
    };
};

it('canViewAny enforces guardian role for 4 Apoderado Resources and Notifications page', function (): void {
    expect(StudentResource::canViewAny())->toBeFalse();
    expect(EnrollmentResource::canViewAny())->toBeFalse();
    expect(EvaluationResource::canViewAny())->toBeFalse();
    expect(GradeResource::canViewAny())->toBeFalse();
    expect(ApoderadoNotificationsPage::canAccess())->toBeFalse();

    Auth::login($this->guardianUser);

    expect(StudentResource::canViewAny())->toBeTrue();
    expect(EnrollmentResource::canViewAny())->toBeTrue();
    expect(EvaluationResource::canViewAny())->toBeTrue();
    expect(GradeResource::canViewAny())->toBeTrue();
    expect(ApoderadoNotificationsPage::canAccess())->toBeTrue();
});

it('Apoderado Notifications navigation badge aggregates only linked students unread (sum 3, no leak Zoe)', function (): void {
    $this->guardianUser->unsetRelation('guardianProfile');
    Auth::login($this->guardianUser);

    $badge = ApoderadoNotificationsPage::getNavigationBadge();
    echo 'DEBUG badge first read: '.var_export($badge, true)."\n";
    $dbStudentA = User::query()->find((int) $this->studentA->getKey());
    $dbStudentB = User::query()->find((int) $this->studentB->getKey());
    $dbStudentForeign = User::query()->find((int) $this->studentForeign->getKey());
    echo 'DEBUG studentA unread (User notifiable): '.$dbStudentA?->unreadNotifications()->count()."\n";
    echo 'DEBUG studentB unread (User notifiable): '.$dbStudentB?->unreadNotifications()->count()."\n";
    echo 'DEBUG studentForeign unread (User notifiable): '.$dbStudentForeign?->unreadNotifications()->count()."\n";

    $gp = Auth::user()?->guardianProfile;
    if ($gp instanceof Guardian) {
        $sids = $gp->students()->pluck('students.id')->all();
        echo 'DEBUG linked profile ids via guardianProfile.students: '.implode(',', $sids)."\n";
        $c = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $sids)
            ->whereNull('read_at')
            ->count();
        echo "DEBUG notif count IN profile ids notifiable_type User: {$c}\n";
    }

    expect(ApoderadoNotificationsPage::getNavigationBadge())->toBe('3');

    $this->studentB->unreadNotifications()->get()->each->markAsRead();
    expect(ApoderadoNotificationsPage::getNavigationBadge())->toBe('2');

    $this->studentA->unreadNotifications()->get()->each->markAsRead();
    expect(ApoderadoNotificationsPage::getNavigationBadge())->toBeNull();

    expect(
        DatabaseNotification::query()
            ->where('notifiable_id', (int) $this->studentForeign->getKey())
            ->whereNull('read_at')
            ->count(),
    )->toBe(1);
});

it('Student Resource modifyQueryUsing returns only Ana and Ben (Zoe excluded)', function () use ($dummyTablesComponent): void {
    Auth::login($this->guardianUser);

    $table = StudentResource::table(Table::make($dummyTablesComponent()));
    $base = Student::query();
    $table->applyQueryScopes($base);
    $matriculas = $base->pluck('student_id')->all();

    expect($matriculas)->toContain('MAT-H1-ANA');
    expect($matriculas)->toContain('MAT-H1-BEN');
    expect($matriculas)->not()->toContain('MAT-H1-ZOE');
});

it('Enrollment Resource modifyQueryUsing scopes Ana/Ben (no Zoe)', function () use ($dummyTablesComponent): void {
    Auth::login($this->guardianUser);

    $table = EnrollmentResource::table(Table::make($dummyTablesComponent()));
    $base = Enrollment::query();
    $table->applyQueryScopes($base);
    $ids = $base->pluck('id')->all();

    expect($ids)->toContain((int) $this->enrollmentA->getKey());
    expect($ids)->toContain((int) $this->enrollmentB->getKey());
    expect(count($ids))->toBe(2);
});

it('Grade Resource returns only Ana grade and Policy denies Zoe grade', function () use ($dummyTablesComponent): void {
    Auth::login($this->guardianUser);

    $table = GradeResource::table(Table::make($dummyTablesComponent()));
    $base = Grade::query();
    $table->applyQueryScopes($base);
    $ids = $base->pluck('id')->all();

    $zoeGrade = Grade::query()
        ->where('student_id', (int) $this->profileForeign->getKey())
        ->firstOrFail();

    expect($ids)->not()->toContain((int) $zoeGrade->getKey());
    expect(count($ids))->toBe(1);
    expect(Gate::forUser($this->guardianUser)->denies('view', $zoeGrade))->toBeTrue();
});

it('4 Apoderado Resources Tables pagination pageOptions are [10, 25, 50]', function () use ($dummyTablesComponent): void {
    Auth::login($this->guardianUser);

    expect(StudentResource::table(Table::make($dummyTablesComponent()))->getPaginationPageOptions())->toBe([10, 25, 50]);
    expect(EnrollmentResource::table(Table::make($dummyTablesComponent()))->getPaginationPageOptions())->toBe([10, 25, 50]);
    expect(EvaluationResource::table(Table::make($dummyTablesComponent()))->getPaginationPageOptions())->toBe([10, 25, 50]);
    expect(GradeResource::table(Table::make($dummyTablesComponent()))->getPaginationPageOptions())->toBe([10, 25, 50]);
});

it('ApoderadoLinkedStudents Stat shows 2 linked (1+ active enrollments >= 2)', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoLinkedStudentsWidget;
    $stats = $widget->getStats();
    expect($stats)->toHaveCount(1);
    $rp = new ReflectionProperty($stats[0], 'value');
    $rp->setAccessible(true);
    $value = (string) ($rp->getValue($stats[0]) ?? '');
    expect($value)->toBe('2');
});

it('ApoderadoGPAStat uses only linked (Ana 82) weight = 82.0%', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoGpaStatWidget;
    $stats = $widget->getStats();
    expect($stats)->toHaveCount(1);
    $rp = new ReflectionProperty($stats[0], 'value');
    $rp->setAccessible(true);
    $value = (string) ($rp->getValue($stats[0]) ?? '');
    expect($value)->toBe('82%');
});

it('ApoderadoGradesWidget RecentGrades returns only Ana grade, not Zoe', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoGradesWidget;
    $dummy = new class extends Component implements HasSchemas, HasTableContract
    {
        use InteractsWithSchemas;
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
    $ids = $table->getQuery()->pluck('id')->all();
    expect($ids)->not()->toContain(
        (int) Grade::query()->where('student_id', (int) $this->profileForeign->getKey())->value('id'),
    );
    expect(count($ids))->toBe(1);
});

it('ApoderadoNotificationsWidget recent list uses only linked (Zoe excluded)', function (): void {
    Auth::login($this->guardianUser);
    $widget = new ApoderadoNotificationsWidget;
    $dummy = new class extends Component implements HasSchemas, HasTableContract
    {
        use InteractsWithSchemas;
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
    $titles = $table->getQuery()->pluck('data')->map(static function (mixed $d): string {
        if (is_array($d)) {
            return (string) ($d['title'] ?? '');
        }
        if (is_string($d)) {
            $decoded = json_decode($d, true);

            return is_array($decoded) ? (string) ($decoded['title'] ?? '') : '';
        }

        return '';
    })->all();
    expect($titles)->not()->toContain('Zoe NO LINKED');
    expect(count($titles))->toBe(3);
});

it('Foreign Guardian with NO linked students sees empty scopes (leak test)', function () use ($dummyTablesComponent): void {
    Auth::login($this->foreignGuardianUser);
    $linked = LinkedGuardianStudents::resolveForUser($this->foreignGuardianUser);
    expect($linked['profileIds'])->toBe([]);
    expect($linked['userIds'])->toBe([]);
    foreach ([
        StudentResource::class => Student::query(),
        EnrollmentResource::class => Enrollment::query(),
        GradeResource::class => Grade::query(),
    ] as $resource => $base) {
        $table = $resource::table(Table::make($dummyTablesComponent()));
        $clone = (clone $base);
        $table->applyQueryScopes($clone);
        expect($clone->count())->toBe(0);
    }
});
