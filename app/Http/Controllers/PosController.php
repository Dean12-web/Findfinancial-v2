<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Accounting\Bank;
use App\Models\Catalog\Tag;
use App\Models\Organization\Warehouse;
use App\Models\Setting\OtherSetting;
use App\Models\Transaction\ViewStoreOrder;
use App\Services\InvoiceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));

        $warehouses = Warehouse::query()
            ->where('project_id', $projectId)
            ->get(['id', 'name']);

        $banks = Bank::query()
            ->activeAccounts()
            ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId))
            ->when($storeId === 0, fn($q) => $q->where('project_id', $projectId))
            ->get(['id', 'name', 'balance']);

        $otherSettings = OtherSetting::query()
            ->whereIn('label', ['member_points', 'used_member_points', 'grosir_points', 'used_grosir_points'])
            ->pluck('value_integer', 'label');

        $tags = Tag::query()
            ->where('project_id', $projectId)
            ->whereIn('category', ['produk', 'penjual'])
            ->get(['category', 'tag'])
            ->map(fn($t) => strtolower("{$t->category}-{$t->tag}"))
            ->values();

        return Inertia::render('Pos/Pos', [
            'warehouses' => $warehouses,
            'banks' => $banks,
            'tags' => $tags,
            'pointSettings' => [
                'member_points' => (int) ($otherSettings['member_points'] ?? 0),
                'used_member_points' => (int) ($otherSettings['used_member_points'] ?? 0),
                'grosir_points' => (int) ($otherSettings['grosir_points'] ?? 0),
                'used_grosir_points' => (int) ($otherSettings['used_grosir_points'] ?? 0),
            ],
            'defaultDates' => [
                'billing_date' => now('Asia/Jakarta')->toDateString(),
                'billing_due_date' => now('Asia/Jakarta')->addDays(30)->toDateString(),
            ],
        ]);
    }

    public function checkout(StoreInvoiceRequest $request, InvoiceCheckoutService $checkoutService): RedirectResponse
    {
        $order = $checkoutService->execute($request->validated(), $request->user());

        $loadedOrder = $order->load(['details.product', 'payments.bankDestination']);

        return back()->with([
            'success' => "Transaksi {$order->order_id} sukses!",
            'completeOrder' => $loadedOrder,
            'completedOrder' => $loadedOrder,
            'created_order_id' => $order->order_id,
        ]);
    }

    public function printThermal(Request $request)
    {
        $orderId = (string) ($request->query('no_transaction') ?? $request->query('not_transaction') ?? $request->query('order_id') ?? '');
        $order = ViewStoreOrder::query()
            ->roleFilter($request->user())
            ->where('order_id', $orderId)
            ->with(['details.product', 'payments.bankDestination', 'customer', 'store'])
            ->firstOrFail();

        return Inertia::render('Pos/PrintThermal', ['order' => $order]);
    }
}
