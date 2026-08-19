<?php

namespace App\Providers;

use App\Events\EvaluationPublished;
use App\Events\GradePublished;
use App\Listeners\LogEvaluationPublishedNotification;
use App\Listeners\LogGradePublishedNotification;
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
        ],
        GradePublished::class => [
            LogGradePublishedNotification::class,
        ],
    ];
}
