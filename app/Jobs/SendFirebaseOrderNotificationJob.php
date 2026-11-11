<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;
class SendFirebaseOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public function __construct(
        public readonly string $orderId,
        public readonly int $storeId,
        public readonly string $customerName,
        public readonly float $totalAmount,
        public readonly string $status,
    ) {
    }

    public function handle(): void
    {
        $creadentialsPath = config('services.firebase.credentials');
        $databaseUri = config('services.firebase.database_uri');

        if (empty($creadentialsPath) || empty($databaseUri) || !file_exists($creadentialsPath) || !class_exists(Factory::class)) {
            return;
        }

        $storeNode = match (true) {
            $this->storeId === 2 => 'mansyur',
            $this->storeId === 4 => 'mansyur',
            $this->storeId > 0 => 'store_' . $this->storeId,
            default => 'general',
        };

        try {
            $httpOptions = HttpClientOptions::default()
                ->withConnectTimeout(1.0)
                ->withTimeout(2.0);

            $database = (new Factory)
                ->withServiceAccount($creadentialsPath)
                ->withDatabaseUri($databaseUri)
                ->withHttpClientOptions($httpOptions)
                ->createDatabase();

            $database
                ->getReference("findfinancial_notifications/{$storeNode}/{$this->orderId}")
                ->set([
                    'order_id' => $this->orderId,
                    'store_id' => $this->storeId,
                    'store_node' => $storeNode,
                    'customer_name' => $this->customerName,
                    'total_amount' => $this->totalAmount,
                    'status' => $this->status,
                    'message' => 'Order baru toko ' . ucfirst($storeNode) . ' dari ' . $this->customerName,
                    'timestamp' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'created_at' => (int) round(microtime(true) * 1000),
                ]);
        } catch (\Throwable $e) {
            Log::error('Firebase Checkout Notification Error: ' . $e->getMessage(), [
                'order_id' => $this->orderId,
            ]);
        }
    }
}