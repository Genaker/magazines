<?php

namespace App\Notifications;

use App\Models\CategoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CategoryRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CategoryRequest $categoryRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New category request: '.$this->categoryRequest->name)
            ->line($this->categoryRequest->user->name.' requested a new category.')
            ->line('Category: '.$this->categoryRequest->name)
            ->line('Reason: '.$this->categoryRequest->reason)
            ->action('Review requests', route('admin.category-requests.index'));
    }
}
