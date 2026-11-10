<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Transaction\ViewStoreOrder;
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

    public function create(): Response
    {
        return Inertia::render('Invoice/Create');
    }
}
