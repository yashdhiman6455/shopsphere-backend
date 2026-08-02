<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateSalesReport implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $start = now()->subDay()->startOfDay();
        $end = now()->subDay()->endOfDay();

        $orders = Order::whereBetween('created_at', [$start, $end])->get();
        $revenue = $orders->where('status', '!=', 'cancelled')->sum('total');
        $count = $orders->count();

        $line = sprintf(
            '[%s] Sales report: %d orders | Revenue: $%s | Completed: %d',
            now()->toDateString(),
            $count,
            number_format((float) $revenue, 2),
            $orders->where('status', '!=', 'cancelled')->count()
        );

        Log::channel('daily')->info($line);
        Storage::disk('local')->append('sales-reports/' . now()->format('Y-m-d') . '.log', $line);
    }
}
