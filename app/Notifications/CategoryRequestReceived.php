<?php

namespace App\Notifications;

use App\Models\CategoryRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CategoryRequestReceived extends Notification implements ShouldQueue
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
            ->subject(__('notification.category_request_received_subject', ['name' => $this->categoryRequest->name]))
            ->line(__('notification.category_request_received_line', ['name' => $this->categoryRequest->name]))
            ->action(__('notification.view_my_requests'), route('category-requests.mine'));
    }
}
