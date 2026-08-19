<?php

declare(strict_types=1);

namespace App\Notifications\Student;

use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EvaluationDueSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Evaluation $evaluation, public int $hoursLeft = 24) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseName = (string) ($this->evaluation->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.course'));

        return (new MailMessage)
            ->subject(__('notifications.evaluation_due_soon_subject', ['evaluation' => (string) $this->evaluation->getAttributeValue('title')]))
            ->greeting(__('notifications.hello', ['name' => (string) $notifiable->getAttributeValue('name')]))
            ->line(__('notifications.evaluation_due_soon_line_1', [
                'evaluation' => (string) $this->evaluation->getAttributeValue('title'),
                'course' => $courseName,
                'hours' => $this->hoursLeft,
            ]))
            ->line(__('notifications.evaluation_due_soon_line_2', [
                'due' => $this->evaluation->getAttributeValue('due_at') ? $this->evaluation->getAttributeValue('due_at')->toDateTimeString() : '—',
            ]))
            ->action(__('notifications.view_evaluation'), route('filament.alumno.pages.view-evaluation', ['record' => $this->evaluation->getKey()]))
            ->line(__('notifications.thank_you'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'evaluation',
            'level' => 'warning',
            'title' => __('notifications.evaluation_due_soon_title'),
            'summary' => __('notifications.evaluation_due_soon_summary', [
                'evaluation' => (string) $this->evaluation->getAttributeValue('title'),
                'hours' => $this->hoursLeft,
            ]),
            'hours_left' => $this->hoursLeft,
            'due_at' => $this->evaluation->getAttributeValue('due_at')?->toIso8601String(),
            'evaluation_id' => $this->evaluation->getKey(),
            'course_offering_id' => $this->evaluation->getAttributeValue('course_offering_id'),
            'action_url' => route('filament.alumno.pages.view-evaluation', ['record' => $this->evaluation->getKey()], false),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
