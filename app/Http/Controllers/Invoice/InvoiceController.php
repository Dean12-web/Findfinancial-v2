<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Accounting\Bank;
use App\Models\Catalog\Tag;
use App\Models\Organization\Warehouse;
use App\Models\Setting\FormatTransaction;
use App\Models\Setting\OtherSetting;
use App\Models\Transaction\StoreOrder;
use App\Models\Transaction\ViewStoreOrder;
use App\Services\InvoiceCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $allowedColumns = [
            'created_date' => 'view_store_order.created_date',
            'transaction_date' => 'view_store_order.transaction_date',
            'order_id' => 'view_store_order.order_id',
            'billing_due_date' => 'view_store_order.billing_due_date',
            'status' => 'view_store_order.status',
            'total_amount' => 'view_store_order.total_amount',
        ];
        $sortColumn = $allowedColumns[$request->input('sort_column')] ?? 'view_store_order.created_date';
        $sortDirection = strtolower($request->input('sort_direction')) === 'asc' ? 'asc' : 'desc';

        $invoices = ViewStoreOrder::query()
            ->roleFilter($user)
            ->with([
                'customer:id,name',
                'store:id,store_name'
            ])
            ->FilterStatus($request->input('status'))
            ->Search($request->input('keyword'))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Invoice/Invoice', [
            'invoices' => $invoices,
            'filters' => [
                'keyword' => $request->input('keyword', ''),
                'status' => $request->input('status', ''),
                'sort_column' => $request->input('sort_column', 'created_date'),
                'sort_direction' => $sortDirection,
                'per_page' => (int) $request->input('per_page', 10),
            ],
            'metrics' => Inertia::defer(function () use ($user) {
                return $this->getInvoicesMetrics($user);
            }),
        ]);
    }

    protected function getInvoicesMetrics($user): array
    {
        $canAccessBank = $user->canAccess('bank');

        $query = "
            SUM(CASE WHEN status = 'OPEN' THEN (total_amount - paid) ELSE 0 END) as open_amount,
            SUM(CASE WHEN status = 'OPEN' THEN 1 ELSE 0 END) as open_count,
            SUM(CASE WHEN status = 'OVERDUE' THEN (total_amount - paid) ELSE 0 END) as overdue_amount,
            SUM(CASE WHEN status = 'OVERDUE' THEN 1 ELSE 0 END) as overdue_count
        ";

        if ($canAccessBank) {
            $query .= ",
                SUM(CASE WHEN status = 'PAID' THEN paid ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) as paid_count
            ";
        }

        $summary = ViewStoreOrder::query()
            ->roleFilter($user)
            ->selectRaw($query)
            ->first();

        return [
            'open' => [
                'amount' => (float) ($summary->open_amount ?? 0),
                'count' => (int) ($summary->open_count ?? 0),
            ],
            'overdue' => [
                'amount' => (float) ($summary->overdue_amount ?? 0),
                'count' => (int) ($summary->overdue_count ?? 0),
            ],
            'paid' => $canAccessBank ? [
                'amount' => (float) ($summary->paid_amount ?? 0),
                'count' => (int) ($summary->paid_count ?? 0),
            ] : null,
        ];
    }

    public function show(Request $request, string $order_id): Response
    {
        $user = $request->user();
        $invoice = ViewStoreOrder::query()
            ->roleFilter($user)
            ->where('order_id', $order_id)
            ->with([
                'details.product',
                'payments.bankDestination',
                'customer',
                'store'
            ])
            ->firstOrFail();

        return Inertia::render('Invoice/ShowInvoice', [
            'invoice' => $invoice
        ]);
    }

    public function print(Request $request, string $order_id): Response
    {
        $user = $request->user();
        $invoice = ViewStoreOrder::query()
            ->roleFilter($user)
            ->where('order_id', $order_id)
            ->with([
                'details.product',
                'payments.bankDestination',
                'customer',
                'store'
            ])
            ->firstOrFail();

        return Inertia::render('Invoice/PrintSalesInvoice', [
            'invoice' => $invoice
        ]);
    }

    public function create(Request $request): Response
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

        $formats = FormatTransaction::query()
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get(['id', 'param_1', 'param_2', 'start', 'status']);

        $otherSettings = OtherSetting::query()
            ->whereIn('label', ['member_points', 'use_member_points', 'grosir_points', 'used_grosir_points'])
            ->pluck('value_integer', 'label');

        $tags = Tag::query()
            ->where('project_id', $projectId)
            ->whereIn('category', ['produk', 'penjual'])
            ->get(['category', 'tag'])
            ->map(fn($t) => strtolower("{$t->category}-{$t->tag}"))
            ->values();

        $suggesCustomers = $projectId !== 1 ? StoreOrder::query()
            ->where('project_id', $projectId)
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->distinct()
            ->orderBy('customer_name')
            ->pluck('customer_name') : collect();

        // Opsional: dukung ?no_transaction=... untuk menyalin isi faktur lama
        $templateOrder = null;
        if ($request->filled('no_transaction')) {
            $templateOrder = StoreOrder::query()
                ->forUser($user)
                ->where('order_id', (string) $request->input('no_transaction'))
                ->with(['details.product:id,code,name,unit', 'customer:id,name,level,points,address', 'store:id,store_name,address'])
                ->first();
        }

        $todayJakarta = now('Asia/Jakarta');

        return Inertia::render('Invoice/Create', [
            'warehouses' => $warehouses,
            'banks' => $banks,
            'formats' => $formats,
            'tags' => $tags,
            'suggestCustomer' => $suggesCustomers,
            'templateOrder' => $templateOrder,
            'pointSettings' => [
                'member_points' => (int) ($otherSettings['member_points'] ?? 0),
                'used_member_points' => (int) ($otherSettings['used_member_points'] ?? 0),
                'grosir_points' => (int) ($otherSettings['grosir_points'] ?? 0),
                'used_grosir_points' => (int) ($otherSettings['used_grosir_points'] ?? 0),
            ],
            'defaultDates' => [
                'billing_date' => $todayJakarta->toDateString(),
                'billing_due_date' => $todayJakarta->copy()->addDays(30)->toDateString(),
            ],
            'storeId' => $storeId,
            'projectId' => $projectId,
        ]);
    }
    public function store(StoreInvoiceRequest $request, InvoiceCheckoutService $checkoutService): RedirectResponse
    {
        $order = $checkoutService->execute($request->validated(), $request->user());

        return redirect()
            ->route('invoice')
            ->with('success', "Transaksi {$order->order_id} berhasil dilakukan")
            ->with('created_order_id', $order->order_id);
    }
}
