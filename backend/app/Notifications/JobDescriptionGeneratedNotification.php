<?php

namespace App\Notifications;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobDescriptionGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Job $job
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Job Description Generated Successfully')
            ->greeting('Hello!')
            ->line("The AI-generated job description for '{$this->job->title}' has been completed.")
            ->line("The job description has been automatically updated in your job posting.")
            ->action('View Job', url("/jobs/{$this->job->id}"))
            ->line('Thank you for using our AI Job Portal!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'job_title' => $this->job->title,
            'message' => "Job description for '{$this->job->title}' has been generated successfully.",
            'generated_at' => now()->toIso8601String(),
        ];
    }
}

