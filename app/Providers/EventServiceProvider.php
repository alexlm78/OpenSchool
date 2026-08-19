<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\EvaluationPublished;
use App\Events\GradePublished;
use App\Listeners\LogEvaluationPublishedNotification;
use App\Listeners\LogGradePublishedNotification;
use App\Listeners\SendStudentEvaluationPublishedNotification;
use App\Listeners\SendStudentGradePublishedNotification;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Observers\EnrollmentObserver;
use App\Observers\SubmissionObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as LaravelEventServiceProvider;

final class EventServiceProvider extends LaravelEventServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        EvaluationPublished::class => [
            LogEvaluationPublishedNotification::class,
            SendStudentEvaluationPublishedNotification::class,
        ],
        GradePublished::class => [
            LogGradePublishedNotification::class,
            SendStudentGradePublishedNotification::class,
        ],
    ];

    public function boot(): void
    {
        Enrollment::observe(EnrollmentObserver::class);
        Submission::observe(SubmissionObserver::class);
    }
}
