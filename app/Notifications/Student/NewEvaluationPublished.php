<?php

declare(strict_types=1);

namespace App\Notifications\Student;

use App\Models\Evaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewEvaluationPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Evaluation $evaluation) {}

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
        $evalTitle = (string) $this->evaluation->getAttributeValue('title');
        $dueAt = $this->evaluation->getAttributeValue('due_at');

        return (new MailMessage)
            ->subject(__('notifications.new_evaluation_subject', ['course' => $courseName]))
            ->greeting(__('notifications.hello', ['name' => (string) $notifiable->getAttributeValue('name')]))
            ->line(__('notifications.new_evaluation_line_1', [
                'evaluation' => $evalTitle,
                'course' => $courseName,
            ]))
            ->when($dueAt !== null, static function (MailMessage $mail) use ($dueAt): void {
                $mail->line(__('notifications.new_evaluation_line_2', [
                    'due' => $dueAt->toDateTimeString(),
                ]));
            })
            ->action(__('notifications.view_evaluation'), route('filament.alumno.resources.evaluations.view', ['record' => $this->evaluation->getKey()]))
            ->line(__('notifications.thank_you'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $courseName = (string) ($this->evaluation->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.course'));

        return [
            'category' => 'evaluation',
            'level' => 'info',
            'title' => __('notifications.new_evaluation_title'),
            'summary' => __('notifications.new_evaluation_summary', [
                'evaluation' => (string) $this->evaluation->getAttributeValue('title'),
                'course' => $courseName,
            ]),
            'weight' => $this->evaluation->getAttributeValue('weight'),
            'max_score' => $this->evaluation->getAttributeValue('max_score'),
            'due_at' => $this->evaluation->getAttributeValue('due_at')?->toIso8601String(),
            'evaluation_id' => $this->evaluation->getKey(),
            'course_offering_id' => $this->evaluation->getAttributeValue('course_offering_id'),
            'action_url' => route('filament.alumno.resources.evaluations.view', ['record' => $this->evaluation->getKey()], false),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
