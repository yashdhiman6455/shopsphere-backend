<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New order received',
            'message' => "A new order ({$this->order->order_number}) for $" . number_format((float) $this->order->total, 2) . ' has been placed.',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'type' => 'new_order',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Order Received - ' . $this->order->order_number)
            ->greeting('Hello ' . $notifiable->name)
            ->line("You have received a new order ({$this->order->order_number}).")
            ->line('Order total: $' . number_format((float) $this->order->total, 2))
            ->action('View Order', url('/seller/orders'))
            ->line('Thank you for selling on ShopSphere!');
    }
}
