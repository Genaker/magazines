<?php

namespace App\Notifications;

use App\Models\CategoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CategoryRequestReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CategoryRequest $categoryRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Category request '.$this->categoryRequest->status->value.': '.$this->categoryRequest->name)
            ->line('Your category request has been '.$this->categoryRequest->status->value.'.');

        if ($this->categoryRequest->admin_note) {
            $message->line('Note: '.$this->categoryRequest->admin_note);
        }

        return $message->action('View my requests', url('/me/category-requests'));
    }
}
