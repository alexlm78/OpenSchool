<?php

declare(strict_types=1);

namespace App\Notifications\Student;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EnrollmentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Enrollment $enrollment, public string $previousStatus) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = (string) $this->enrollment->getAttributeValue('status');
        $courseName = (string) ($this->enrollment->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.course'));

        return (new MailMessage)
            ->subject(__('notifications.enrollment_changed_subject', ['course' => $courseName]))
            ->greeting(__('notifications.hello', ['name' => (string) $notifiable->getAttributeValue('name')]))
            ->line(__('notifications.enrollment_changed_line_1', [
                'course' => $courseName,
                'previous' => $this->previousStatus,
                'new' => $status,
            ]))
            ->action(__('notifications.view_enrollment'), route('filament.alumno.resources.enrollments.view', ['record' => $this->enrollment->getKey()]))
            ->line(__('notifications.thank_you'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $status = (string) $this->enrollment->getAttributeValue('status');
        $courseName = (string) ($this->enrollment->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.course'));

        return [
            'category' => 'enrollment',
            'level' => match ($status) {
                'active' => 'success',
                'completed' => 'info',
                'dropped' => 'danger',
                default => 'default',
            },
            'title' => __('notifications.enrollment_changed_title'),
            'summary' => __('notifications.enrollment_changed_summary', [
                'course' => $courseName,
                'new' => $status,
            ]),
            'previous_status' => $this->previousStatus,
            'new_status' => $status,
            'enrollment_id' => $this->enrollment->getKey(),
            'course_offering_id' => $this->enrollment->getAttributeValue('course_offering_id'),
            'action_url' => route('filament.alumno.resources.enrollments.view', ['record' => $this->enrollment->getKey()], false),
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
