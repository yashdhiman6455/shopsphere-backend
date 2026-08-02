<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Seller account rejected',
            'message' => 'We are sorry, but your seller account application has been rejected. Please contact support for more information.',
            'type' => 'seller_rejected',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on Your Seller Account')
            ->greeting('Hello ' . $notifiable->name)
            ->line('We are sorry, but your seller account application has been rejected.')
            ->line('Please contact support if you believe this is a mistake.')
            ->line('Thank you for your interest in ShopSphere!');
    }
}
