<?php

declare(strict_types=1);

namespace App\Notifications\Student;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubmissionLateReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Submission $submission) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $evalTitle = (string) ($this->submission->evaluation?->getAttributeValue('title') ?? __('general.submission'));

        return (new MailMessage)
            ->subject(__('notifications.submission_late_subject', ['evaluation' => $evalTitle]))
            ->greeting(__('notifications.hello', ['name' => (string) $notifiable->getAttributeValue('name')]))
            ->line(__('notifications.submission_late_line_1', ['evaluation' => $evalTitle, 'attempt' => (int) $this->submission->getAttributeValue('attempt')]))
            ->line(__('notifications.submission_late_line_2'))
            ->action(__('notifications.view_submission'), route('filament.alumno.resources.submissions.view', ['record' => $this->submission->getKey()]))
            ->line(__('notifications.thank_you'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'submission',
            'level' => 'warning',
            'title' => __('notifications.submission_late_title'),
            'summary' => __('notifications.submission_late_summary', [
                'evaluation' => (string) ($this->submission->evaluation?->getAttributeValue('title') ?? __('general.submission')),
            ]),
            'attempt' => (int) $this->submission->getAttributeValue('attempt'),
            'submission_id' => $this->submission->getKey(),
            'evaluation_id' => $this->submission->getAttributeValue('evaluation_id'),
            'action_url' => route('filament.alumno.resources.submissions.view', ['record' => $this->submission->getKey()], false),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
