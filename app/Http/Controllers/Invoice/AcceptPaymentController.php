<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcceptPaymentRequest;
use App\Models\Accounting\Bank;
use App\Models\Accounting\CashTransaction;
use App\Models\Contact\Customer;
use App\Models\Contact\DepositTransaction;
use App\Models\Promotion\PointTransaction;
use App\Models\Setting\FormatTransaction;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\StoreOrderPayment;
use App\Models\Transaction\ViewStoreOrder;
use App\Services\BalanceService;
use App\Services\TransactionNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcceptPaymentController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();
        $noTransaction = (string) $request->query('no_transaction', '');
        $userProjectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));

        $order = ViewStoreOrder::query()
            ->roleFilter($user)
            ->with([
                'customer:id,name,deposit',
                'store:id,store_name',
            ])
            ->where('order_id', $noTransaction)
            ->firstOrFail();

        $banks = Bank::query()
            ->activeAccounts()
            ->when(
                $user->store_id !== null,
                fn($q) => $q->where('store_id', $user->store_id),
                fn($q) => $q->where('project_id', $userProjectId)
            )
            ->select(['id', 'name', 'balance'])
            ->get();

        $formatTransactions = FormatTransaction::query()
            ->where('project_id', $userProjectId)
            ->where('status', 1)
            ->select(['id', 'param_1', 'param_2', 'start'])
            ->get();

        return Inertia::render('AcceptPayment/AcceptPayment', [
            'invoice' => [
                'order_id' => $order->order_id,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer?->name ?? $order->customer_name,
                'customer_deposit' => (int) ($order->customer?->deposit ?? 0),
                'store_name' => $order->store?->store_name,
                'total_amount' => (int) $order->total_amount,
                'paid' => (float) $order->paid,
                'billing_due_date' => $order->billing_due_date?->format('Y-m-d'),
                'transaction_date' => $order->transaction_date?->format('Y-m-d'),
                'tags' => $order->tags,
                'status' => $order->status,

            ],
            'banks' => $banks,
            'formatTransactions' => $formatTransactions
        ]);
    }

    public function store(StoreAcceptPaymentRequest $request, TransactionNumberService $numberService, BalanceService $balanceService): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $userProjectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
        $updatedBy = Str::limit((string) ($user->username ?? 'system'), 50, '');

        $orderId = $validated['order_id'];
        $debitDeposit = (bool) $validated['debit_deposit'];
        $paid = (int) $validated['paid'];
        $billingDate = $validated['billing_date'];
        $memo = $validated['memo'] ?? '';
        $tags = $validated['tags'] ?? '';
        $formatTrxId = !empty($validated['format_transaction_id']) ? (int) $validated['format_transaction_id'] : null;

        DB::transaction(function () use ($user, $userProjectId, $updatedBy, $orderId, $debitDeposit, $paid, $billingDate, $memo, $tags, $formatTrxId, $validated, $numberService, $balanceService) {
            $viewOrder = ViewStoreOrder::query()
                ->roleFilter($user)
                ->where('order_id', $orderId)
                ->firstOrFail();

            $storeOrder = StoreOrder::query()
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyPaid = (int) StoreOrderPayment::query()
                ->where('store_order_id', $orderId)
                ->where('status', 'approved')
                ->sum('amount');
            $totalAmount = (int) $storeOrder->total_amount;
            $sisa = max(0, $totalAmount - $alreadyPaid);

            if ($sisa <= 0 || $storeOrder->status === 'PAID') {
                throw ValidationException::withMessages([
                    'paid' => 'Faktur ini sudah berstatus lunas.'
                ]);
            }

            $clearPaid = $paid > $sisa ? $sisa : $paid;
            $sisaSetelahBayar = max(0, $sisa - $clearPaid);
            $totalPaid = $alreadyPaid + $clearPaid;
            $timesPayment = StoreOrderPayment::query()
                ->where('store_order_id', $orderId)
                ->count();

            $paymentNotes = $sisaSetelahBayar > 0 ? 'Cicilan ke-' . ($timesPayment + 1) : 'Dibayar Lunas';

            $bankDestiny = null;

            if ($debitDeposit) {
                if ((int) $storeOrder->customer_id <= 0) {
                    throw ValidationException::withMessages([
                        'debit_deposit' => 'Pembayaran via deposit hanya berlaku untuk pelanggan terdaftar.'
                    ]);
                }

                $customer = Customer::query()
                    ->lockForUpdate()
                    ->findOrFail($storeOrder->customer_id);

                if ((int) $customer->deposit < $clearPaid) {
                    throw ValidationException::withMessages([
                        'debit_deposit' => 'Transaksi gagal dilakukan, deposit pelanggan tidak mencukupi.'
                    ]);
                }

                DepositTransaction::create([
                    'order_id' => $orderId,
                    'project_id' => $userProjectId,
                    'debit' => $clearPaid,
                    'balance' => (int) $customer->deposit - $clearPaid,
                    'description' => "Penggunaan Deposit Saldo Sebesar {$clearPaid}, No. Transaksi {$orderId}",
                    'id_bank' => 0,
                    'date' => $billingDate,
                    'memo' => $memo,
                    'tags' => $tags,
                    'role_user' => 'customer',
                    'id_user' => $customer->id,
                    'updated_by' => $updatedBy,
                ]);

                $balanceService->syncCustomerDeposit($customer->id);
            } else {
                $bankDestiny = Bank::query()
                    ->activeAccounts()
                    ->when(
                        $user->store_id !== null,
                        fn($q) => $q->where('store_id', $user->store_id),
                        fn($q) => $q->where('project_id', $userProjectId)
                    )
                    ->lockForUpdate()
                    ->find($validated['id_bank_destination']);

                if (!$bankDestiny) {
                    throw ValidationException::withMessages([
                        'id_bank_destination' => 'Transaksi gagal dilakukan, pastikan kas tujuan sudah ditentukan.'
                    ]);
                }
            }

            $paymentNumber = $numberService->generateOrderNumber($formatTrxId, $userProjectId, 'tb_store_order_payment');

            StoreOrderPayment::create([
                'store_order_id' => $orderId,
                'order_id' => $paymentNumber,
                'amount' => $clearPaid,
                'sisa' => $sisaSetelahBayar,
                'notes' => Str::limit($paymentNotes, 30, ''),
                'id_bank_destination' => $bankDestiny?->id ?? 0,
                'id_bank' => $bankDestiny?->id ?? 0,
                'status' => 'approved',
                'date' => $billingDate,
                'updated_by' => $updatedBy,
            ]);

            if (!$debitDeposit && $bankDestiny) {
                CashTransaction::create([
                    'order_id' => $orderId,
                    'code' => 'Sale',
                    'debit' => 0,
                    'credit' => $clearPaid,
                    'balance' => (int) $bankDestiny->balance + $clearPaid,
                    'title' => Str::limit("{$paymentNotes}, No. Transaksi {$orderId}", 100, ''),
                    'memo' => Str::limit($memo, 50, ''),
                    'id_bank' => $bankDestiny->id,
                    'date' => $billingDate,
                    'updated_by' => Str::limit($updatedBy, 10, ''),
                ]);

                $balanceService->syncBankBalance($bankDestiny->id);
            }

            if ($totalPaid >= $totalAmount) {
                $storeOrder->update(['status' => 'PAID']);

                if ((int) $storeOrder->customer_id > 0 && (int) $storeOrder->poin > 0) {
                    PointTransaction::create([
                        'transid' => $orderId,
                        'memberid' => $storeOrder->customer_id,
                        'status' => 'C',
                        'amount' => (int) $storeOrder->poin,
                    ]);

                    $balanceService->syncCustomerPoints((int) $storeOrder->customer_id);
                }
            }
        });

        return redirect()->route('invoice.show', $orderId)->with('success', 'Transaksi pembayaran berhasil dilakukan.');
    }
}
