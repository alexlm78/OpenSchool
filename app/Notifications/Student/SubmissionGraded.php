<?php

declare(strict_types=1);

namespace App\Notifications\Student;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubmissionGraded extends Notification implements ShouldQueue
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
        $courseName = (string) ($this->submission->evaluation?->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.course'));
        $latestGrade = $this->submission->grades?->last();
        $score = $latestGrade?->getAttributeValue('score') ?? '—';
        $max = $this->submission->evaluation?->getAttributeValue('max_score') ?? '—';

        return (new MailMessage)
            ->subject(__('notifications.submission_graded_subject', ['evaluation' => $evalTitle]))
            ->greeting(__('notifications.hello', ['name' => (string) $notifiable->getAttributeValue('name')]))
            ->line(__('notifications.submission_graded_line_1', ['evaluation' => $evalTitle, 'course' => $courseName]))
            ->line(__('notifications.submission_graded_line_2', ['score' => $score, 'max' => $max]))
            ->action(__('notifications.view_submission'), route('filament.alumno.resources.submissions.view', ['record' => $this->submission->getKey()]))
            ->line(__('notifications.thank_you'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $latestGrade = $this->submission->grades?->last();

        return [
            'category' => 'submission',
            'level' => 'info',
            'title' => __('notifications.submission_graded_title'),
            'summary' => __('notifications.submission_graded_summary', [
                'evaluation' => (string) ($this->submission->evaluation?->getAttributeValue('title') ?? __('general.submission')),
            ]),
            'attempt' => $this->submission->getAttributeValue('attempt'),
            'score' => $latestGrade?->getAttributeValue('score'),
            'max_score' => $this->submission->evaluation?->getAttributeValue('max_score'),
            'submission_id' => $this->submission->getKey(),
            'evaluation_id' => $this->submission->getAttributeValue('evaluation_id'),
            'action_url' => route('filament.alumno.resources.submissions.view', ['record' => $this->submission->getKey()], false),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
