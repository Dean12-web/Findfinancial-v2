<?php
namespace App\Services;

use App\Jobs\SendFirebaseOrderNotificationJob;
use App\Models\Accounting\Bank;
use App\Models\Accounting\CashTransaction;
use App\Models\Catalog\Product;
use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Models\Organization\User;
use App\Models\Promotion\PointTransaction;
use App\Models\Setting\OtherSetting;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\StoreOrderDetail;
use App\Models\Transaction\StoreOrderPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class InvoiceCheckoutService
{
    public function __construct(
        protected TransactionNumberService $numberService,
        protected StockService $stockService,
        protected BalanceService $balanceService,
    ) {
    }

    public function execute(array $payload, User $user): StoreOrder
    {
        $billingDate = Carbon::parse($payload['billing_date'])->toDateString();
        $billingDueDate = Carbon::parse($payload['billing_due_date'])->toDateString();
        $now = now('Asia/Jakarta');
        $updatedByFull = Str::limit((string) ($user->username ?? 'system'), 50, '');
        $cashUpdatedBy = Str::limit($updatedByFull, 10, '');

        $order = DB::transaction(function () use ($payload, $user, $billingDate, $billingDueDate, $now, $updatedByFull, $cashUpdatedBy) {
            $buyerType = $payload['buyer_type'];
            $buyerId = (int) $payload['buyer_id'];
            $isBranchStaff = in_array($user->role_user, ['adminbranch', 'cashier'], true);
            $userStoreId = (int) ($user->store_id ?? 0);
            $userProjectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
            $warehouseId = $isBranchStaff ? 0 : (int) $payload['warehouse_id'];

            // Hitung ulang Item & Diskon di Server
            $productIds = collect($payload['items'])->pluck('product_id')->unique()->all();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $grossSubTotal = 0;
            $totalLineDiscount = 0;
            $preparedItems = [];

            foreach ($payload['items'] as $row) {
                $product = $products->get((int) $row['product_id']);
                $qty = (int) $row['quantity'];
                $price = (float) $row['price'];
                $rowGross = $qty * $price;

                $rowDiscInput = (float) ($row['discount'] ?? 0);
                $rowDiscType = $row['discount_type'] ?? 'percentage';
                $rowDiscAmount = 0;

                if ($rowDiscInput > 0) {
                    $rowDiscAmount = $rowDiscType === 'percentage'
                        ? ($rowGross * $rowDiscInput) / 100
                        : ($qty * $rowDiscInput);
                }

                $rowDiscAmount = min($rowDiscAmount, $rowGross);
                $rowNet = max(0, $rowGross - $rowDiscAmount);

                $grossSubTotal += $rowGross;
                $totalLineDiscount += $rowDiscAmount;

                $preparedItems[] = [
                    'product_id' => (int) $row['product_id'],
                    'price' => (int) round($price),
                    'price_buy' => (int) ($product?->price_buy ?? 0),
                    'quantity' => $qty,
                    'discount_row' => (int) round($rowDiscInput),
                    'total' => (int) round($rowNet),
                ];
            }
            $totalItemNet = max(0, $grossSubTotal - $totalLineDiscount);

            // Hitung Master Diskon (Toko % / RP / Poin) & Poin Earned dari tb_others
            $customer = ($buyerType === 'customer' && $buyerId > 0) ? Customer::forProject($userProjectId)->lockForUpdate()->find($buyerId) : null;
            $targetStore = ($buyerType === 'store' && $buyerId > 0) ? Store::where('project_id', $userProjectId)->find($buyerId) : null;

            $pointSettings = $this->resolvePointSettings($customer);
            $discountType = $payload['discount_type'] ?? 'percentage';
            $discountInput = (float) ($payload['discount_input'] ?? 0);
            $masterDiscount = 0;

            if ($discountInput > 0) {
                if ($discountType === 'amount') {
                    $masterDiscount = $discountInput;
                } elseif ($discountType === 'percentage') {
                    $masterDiscount = ($discountInput * $totalItemNet) / 100;
                } elseif ($discountType === 'point' && $customer) {
                    $usablePoints = min($discountInput, (float) $customer->points);
                    $masterDiscount = $usablePoints * $pointSettings['used_points_unit'];
                }
            }
            $masterDiscount = min($masterDiscount, $totalItemNet);
            $totalDiscount = $totalLineDiscount + $masterDiscount;
            $shippingCost = max(0, (float) ($payload['shipping_cost'] ?? 0));
            $totalAmount = max(0, $grossSubTotal + $shippingCost - $totalDiscount);

            $paid = min((int) $payload['paid'], (int) round($totalAmount));
            $sisa = max(0, (int) round($totalAmount) - $paid);
            $status = $sisa > 0 ? "OPEN" : "PAID";

            $pointsEarned = 0;
            if ($totalLineDiscount == 0 && $masterDiscount == 0 && $pointSettings['points_unit'] > 0) {
                $pointsEarned = (int) floor($totalItemNet / $pointSettings['points_unit']);
            }

            // Genereate Nomor Transaksi
            $formatTrxId = !empty($payload['format_trx_id']) ? (int) $payload['format_trx_id'] : null;
            $orderId = $this->numberService->generateOrderNumber($formatTrxId, $userProjectId, 'tb_store_order');
            $fallbackBuyerName = $buyerType === 'store' ? ($targetStore?->store_name ?? 'Toko') : ($customer?->name ?? 'Umum');

            // Simpan Header tb_store_order agar aman terhadap NOT NULL & UNIQUE
            $orderAttributes = [
                'warehouse_id' => $warehouseId,
                'supplier_id' => 0,
                'order_id' => $orderId ?: ('TMP-' . (string) Str::ulid()),
                'customer_name' => Str::limit((string) (($payload['customer_name'] ?? null) ?: $fallbackBuyerName), 50, ''),
                'amount' => (int) round($grossSubTotal),
                'discount' => (int) round($totalDiscount),
                'total_amount' => (int) round($totalAmount),
                'billing_address' => $payload['billing_address'] ?? null,
                'billing_due_date' => $billingDueDate,
                'shipping_address' => $payload['shipping_address'] ?? null,
                'shipping_date' => !empty($payload['shipping_date']) ? $payload['shipping_date'] : null,
                'shipping_option' => $payload['shipping_option'] ?? null,
                'shipping_cost' => (int) round($shippingCost),
                'tags' => $payload['tags'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'memo' => $payload['memo'] ?? null,
                'transaction_date' => $billingDate,
                'invoice_date' => $billingDate,
                'status' => $status,
                'poin' => $pointsEarned,
                'created_date' => $now,
                'updated_date' => $now,
                'updated_by' => $updatedByFull,
            ];

            if ($isBranchStaff) {
                $orderAttributes['store_id'] = $userStoreId;
                $orderAttributes['role_store'] = 'seller';
                $orderAttributes['role_project'] = 'none';
                $orderAttributes['project_id'] = 0;
                $orderAttributes['customer_id'] = $buyerId;
            } else {
                $orderAttributes['project_id'] = $userProjectId;
                $orderAttributes['role_project'] = 'seller';

                if ($buyerType === 'store') {
                    $orderAttributes['store_id'] = $buyerId;
                    $orderAttributes['role_store'] = 'buyer';
                    $orderAttributes['customer_id'] = 0;
                } else {
                    $orderAttributes['store_id'] = 0;
                    $orderAttributes['role_store'] = 'none';
                    $orderAttributes['customer_id'] = $buyerId;
                }
            }
            $order = StoreOrder::create($orderAttributes);

            if (empty($orderId)) {
                $orderId = (string) $order->id;
                $order->update(['order_id' => $orderId]);
            }
            // Simpan Detail & Potong Stok + Duplikasi Antar Cabang(Didalam transaksi)
            foreach ($preparedItems as $item) {
                StoreOrderDetail::create([
                    'store_order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'price_buy' => $item['price_buy'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount_row'],
                    'total' => $item['total'],
                    'stock_update' => 'FALSE',
                    'created_date' => $now,
                    'updated_date' => $now,
                    'updated_by' => $updatedByFull,
                ]);

                $this->stockService->deductSaleStock($orderId, $item['product_id'], (float) $item['quantity'], $warehouseId, $userStoreId, $billingDate, $updatedByFull);

                if (!$isBranchStaff && $buyerType === 'store') {
                    $this->stockService->duplicateProductToStore($orderId, $item['product_id'], $buyerId, $userProjectId ?: 1, (float) $item['quantity'], (float) $item['price'], $warehouseId, $billingDate, $updatedByFull);
                }
            }
            // Proses Pembayaran (Single vs Split) + Buku Kas + BalanceService::syncBankBalance()
            if ($paid > 0) {
                $this->processPayments($payload, $orderId, $userProjectId, $paid, $sisa, $billingDate, $updatedByFull, $cashUpdatedBy);
            }

            // Proses Poin Customer
            if ($paid > 0 && $customer) {
                $isUsingPointDiscount = ($discountType === 'point' && $discountInput > 0);
                $poinTransId = is_numeric($orderId) ? (int) $orderId : (int) $order->id;

                if ($isUsingPointDiscount) {
                    $usedPoints = (int) min($discountInput, $customer->points);
                    if ($usedPoints > 0) {
                        PointTransaction::create([
                            'transid' => $poinTransId,
                            'memberid' => (string) $customer->id,
                            'status' => 'D',
                            'amount' => $usedPoints,
                            'updateby' => $updatedByFull,
                        ]);

                        $this->balanceService->syncCustomerPoints($customer->id);
                    }
                } elseif ($buyerType !== 'store' && $sisa === 0 && $pointsEarned > 0) {
                    PointTransaction::create([
                        'transid' => $poinTransId,
                        'memberid' => (string) $customer->id,
                        'status' => 'C',
                        'amount' => $pointsEarned,
                        'updateby' => $updatedByFull,
                    ]);

                    $this->balanceService->syncCustomerPoints($customer->id);
                }
            }
            return $order->fresh();
        });
        // Dispatch Notifikasi Firebase secara asyn setelah transaksi DB berhasil
        SendFirebaseOrderNotificationJob::dispatch(
            orderId: (string) $order->order_id,
            storeId: (int) ($user->store_id ?: $order->store_id),
            customerName: (string) $order->customer_name,
            totalAmount: (float) $order->total_amount,
            status: (string) $order->status,
        )->afterCommit();

        return $order;
    }

    protected function processPayments(
        array $payload,
        string $orderId,
        int $projectId,
        int $paid,
        int $sisa,
        string $billingDate,
        string $updatedByFull,
        string $cashUpdatedBy
    ): void {
        $isSplit = !empty($payload['is_split']);
        $memo = Str::limit((string) ($payload['memo'] ?? ''), 50, '');

        if ($isSplit && !empty($payload['split_payments'])) {
            $splits = collect($payload['split_payments'])
                ->filter(fn($sp) => (float) ($sp['amount'] ?? 0) > 0)
                ->values();

            $remainingToAllocate = $paid;

            foreach ($splits as $idx => $sp) {
                if ($remainingToAllocate <= 0) {
                    break;
                }

                $bankTarget = Bank::lockForUpdate()->findOrFail((int) $sp['id_bank']);
                $spAmount = (int) min(round((float) $sp['amount']), $remainingToAllocate);
                $remainingToAllocate -= $spAmount;

                $isLastRow = ($idx === $splits->count() - 1) || $remainingToAllocate === 0;
                $rowSisa = $isLastRow ? $sisa : 0;
                $notes = Str::limit((string) ($sp['notes'] ?? 'Split Payment'), 30, '');

                StoreOrderPayment::create([
                    'store_order_id' => $orderId,
                    // Bukti bayar memakai urutan tersendiri (null) agar tidak menggeser counter tb_format_transaction faktur
                    'order_id' => $this->numberService->generateOrderNumber(null, $projectId, 'tb_store_order_payment'),
                    'id_bank' => 0,
                    'id_bank_destination' => $bankTarget->id,
                    'amount' => $spAmount,
                    'sisa' => $rowSisa,
                    'notes' => $notes,
                    'status' => 'approved',
                    'date' => $billingDate,
                    'created_at' => $billingDate . ' 00:00:00',
                    'updated_by' => $updatedByFull,
                ]);

                CashTransaction::create([
                    'order_id' => $orderId,
                    'code' => 'Sale',
                    'debit' => 0,
                    'credit' => $spAmount,
                    'balance' => $bankTarget->balance + $spAmount,
                    'title' => Str::limit("Dibayar Lunas ({$notes}), No. Transaksi {$orderId}", 100, ''),
                    'memo' => $memo ?: null,
                    'id_bank' => $bankTarget->id,
                    'date' => $billingDate,
                    'updated_by' => $cashUpdatedBy,
                ]);

                $this->balanceService->syncBankBalance($bankTarget->id);
            }

            return;
        }

        // Single Payment
        $bankTarget = Bank::lockForUpdate()->findOrFail((int) $payload['id_bank']);
        $paymentNote = $sisa > 0 ? 'Uang Muka' : 'Dibayar Lunas';

        StoreOrderPayment::create([
            'store_order_id' => $orderId,
            'order_id' => $this->numberService->generateOrderNumber(null, $projectId, 'tb_store_order_payment'),
            'id_bank' => 0,
            'id_bank_destination' => $bankTarget->id,
            'amount' => $paid,
            'sisa' => $sisa,
            'notes' => $paymentNote,
            'status' => 'approved',
            'date' => $billingDate,
            'created_at' => $billingDate . ' 00:00:00',
            'updated_by' => $updatedByFull,
        ]);

        CashTransaction::create([
            'order_id' => $orderId,
            'code' => 'Sale',
            'debit' => 0,
            'credit' => $paid,
            'balance' => $bankTarget->balance + $paid,
            'title' => Str::limit("{$paymentNote}, No. Transaksi {$orderId}", 100, ''),
            'memo' => $memo ?: null,
            'id_bank' => $bankTarget->id,
            'date' => $billingDate,
            'updated_by' => $cashUpdatedBy,
        ]);

        $this->balanceService->syncBankBalance($bankTarget->id);
    }

    protected function resolvePointSettings(?Customer $customer): array
    {
        if (!$customer) {
            return ['points_unit' => 0, 'used_points_unit' => 0];
        }
        $isMember = strtolower((string) $customer->level) === 'member';
        $earnLabel = $isMember ? 'member_points' : 'grosir_points';
        $useLabel = $isMember ? 'used_member_points' : 'used_grosir_points';

        $settings = OtherSetting::whereIn('label', [$earnLabel, $useLabel])->pluck('value_integer', 'label');

        return [
            'points_unit' => (int) ($settings[$earnLabel] ?? 0),
            'used_points_unit' => (int) ($settings[$useLabel] ?? 0),
        ];
    }
}