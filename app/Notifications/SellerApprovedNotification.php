<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Seller account approved',
            'message' => 'Congratulations! Your seller account has been approved. You can now add products and start selling.',
            'type' => 'seller_approved',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Seller Account Has Been Approved')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Congratulations! Your seller account has been approved.')
            ->line('You can now add products and start selling on ShopSphere.')
            ->action('Go to Your Dashboard', url('/seller/dashboard'))
            ->line('Thank you for selling on ShopSphere!');
    }
}
