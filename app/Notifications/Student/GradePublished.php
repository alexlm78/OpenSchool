<?php

declare(strict_types=1);

namespace App\Notifications\Student;

use App\Models\Grade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class GradePublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Grade $grade) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseName = (string) ($this->grade->evaluation?->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.course'));
        $score = $this->grade->getAttributeValue('score');
        $max = $this->grade->evaluation?->getAttributeValue('max_score');
        $evaluationTitle = (string) ($this->grade->evaluation?->getAttributeValue('title') ?? __('general.evaluation'));

        return (new MailMessage)
            ->subject(__('notifications.grade_published_subject', ['course' => $courseName]))
            ->greeting(__('notifications.hello', ['name' => (string) $notifiable->getAttributeValue('name')]))
            ->line(__('notifications.grade_published_line_1', ['evaluation' => $evaluationTitle, 'course' => $courseName]))
            ->line(__('notifications.grade_published_line_2', [
                'score' => $score,
                'max' => $max ?? '—',
            ]))
            ->action(__('notifications.view_grade'), route('filament.alumno.resources.evaluations.view', ['record' => $this->grade->getAttributeValue('evaluation_id')]))
            ->line(__('notifications.thank_you'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $courseTemplate = $this->grade->evaluation?->courseOffering?->courseTemplate;

        return [
            'category' => 'grade',
            'level' => match (true) {
                $this->grade->evaluation !== null
                    && $this->grade->evaluation->getAttributeValue('max_score') !== null
                    && (float) $this->grade->getAttributeValue('score') / (float) $this->grade->evaluation->getAttributeValue('max_score') >= 0.7 => 'success',
                $this->grade->evaluation !== null
                    && $this->grade->evaluation->getAttributeValue('max_score') !== null
                    && (float) $this->grade->getAttributeValue('score') / (float) $this->grade->evaluation->getAttributeValue('max_score') >= 0.5 => 'warning',
                default => 'danger',
            },
            'title' => __('notifications.grade_published_title'),
            'summary' => __('notifications.grade_published_summary', [
                'evaluation' => (string) ($this->grade->evaluation?->getAttributeValue('title') ?? __('general.evaluation')),
                'course' => (string) ($courseTemplate?->getAttributeValue('name') ?? __('general.course')),
            ]),
            'score' => $this->grade->getAttributeValue('score'),
            'max_score' => $this->grade->evaluation?->getAttributeValue('max_score'),
            'grade_id' => $this->grade->getKey(),
            'evaluation_id' => $this->grade->getAttributeValue('evaluation_id'),
            'course_offering_id' => $this->grade->evaluation?->getAttributeValue('course_offering_id'),
            'action_url' => route('filament.alumno.resources.evaluations.view', ['record' => $this->grade->getAttributeValue('evaluation_id')], false),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
