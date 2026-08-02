<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $products
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $names = $this->products->take(3)->pluck('name')->join(', ');

        return [
            'title' => 'Low stock alert',
            'message' => $this->products->count() . ' of your products are low on stock' . ($names ? " ({$names})" : '') . '.',
            'type' => 'low_stock',
            'product_names' => $this->products->pluck('name')->all(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $names = $this->products->pluck('name')->join(', ');

        return (new MailMessage)
            ->subject('Low Stock Alert')
            ->greeting('Hello ' . $notifiable->name)
            ->line($this->products->count() . ' of your products are low on stock: ' . $names)
            ->action('Manage Inventory', url('/seller/products'))
            ->line('Restock these items soon to avoid missing sales.');
    }
}
