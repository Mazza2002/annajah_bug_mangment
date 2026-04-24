<?php

namespace App\Notifications;

use App\Models\Bug;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BugUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bug;
    protected $type;
    protected $oldStatus;
    protected $newStatus;

    public function __construct(Bug $bug, string $type, $oldStatus = null, $newStatus = null)
    {
        $this->bug = $bug;
        $this->type = $type;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Annajah Bug Update: ' . $this->bug->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('There is an update on bug #' . $this->bug->id . ': ' . $this->bug->title);

        if ($this->type === 'status_changed') {
            $message->line('Status changed from ' . $this->oldStatus . ' to ' . $this->newStatus);
        } elseif ($this->type === 'assigned') {
            $message->line('This bug has been assigned to you.');
        }

        return $message
            ->action('View Bug Details', url('/bugs/' . $this->bug->id))
            ->line('Thank you for using Annajah!');
    }

    public function toArray($notifiable): array
    {
        return [
            'bug_id' => $this->bug->id,
            'bug_title' => $this->bug->title,
            'type' => $this->type,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => $this->getNotificationMessage(),
        ];
    }

    protected function getNotificationMessage()
    {
        if ($this->type === 'status_changed') {
            return "Bug #{$this->bug->id} status changed to {$this->newStatus}";
        }
        if ($this->type === 'assigned') {
            return "You have been assigned to bug #{$this->bug->id}";
        }
        return "Bug #{$this->bug->id} has been updated";
    }
}
