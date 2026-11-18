<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceLookupController extends Controller
{
    public function buyers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $role = $request->input('role', 'customer');
        $user = $request->user();
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
        if ($keyword === '') {
            return response()->json([]);
        }

        if ($role === 'store') {
            abort_unless(in_array($user->role_user, ['admin', 'superadmin'], true), 403);
            $stores = Store::query()
                ->where('project_id', $projectId)
                ->where('store_name', 'LIKE', "%{$keyword}%")
                ->limit(15)
                ->get(['id', 'store_name', 'address']);

            return response()->json($stores->map(fn($s) => [
                'id' => $s->id,
                'role' => 'store',
                'name' => $s->store_name,
                'title' => $s->store_name,
                'level' => null,
                'points' => 0,
                'address' => $s->address,
            ]));
        }

        $customers = Customer::query()
            ->forProject($projectId)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'email', 'level', 'points', 'address']);

        return response()->json($customers->map(fn($c) => [
            'id' => $c->id,
            'role' => 'customer',
            'name' => $c->name,
            'title' => trim(($c->email ?: $c->name) . ' - ' . ($c->level ?? 'Member')),
            'level' => $c->level ?? 'Member',
            'points' => (int) $c->points,
            'address' => $c->address,
        ]));
    }

    public function products(Request $request, StockService $stockService): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);
        $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
        $isBranchStaff = in_array($user->role_user, ['adminbranch', 'cashier'], true);
        $warehouseId = $isBranchStaff ? 0 : (int) $request->input('warehouse_id', 0);
        $today = now('Asia/Jakarta')->toDateString();

        $query = Product::query()
            ->forProject($projectId)
            ->where(function ($q) use ($keyword) {
                $q->where('code', 'LIKE', "%{$keyword}%")
                    ->orWhere('name', 'LIKE', "%{$keyword}%");
            });

        if (in_array($user->role_user, ['admin', 'adminbranch'], true)) {
            $query->where('store_id', $storeId)
                ->where('archive', 'N');
        }

        $products = $query
            ->orderByRaw("CASE WHEN category = 'Florean Regular' THEN 0 WHEN category IN ('Produk Grosir Bibit', 'Grosir Bibit') THEN 1 ELSE 2 END ASC")
            ->get(['id', 'code', 'name', 'type', 'unit', 'price_sell', 'monitoring_stock']);

        return response()->json($products->map(function ($product) use ($stockService, $warehouseId, $storeId, $today) {
            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'type' => $product->type,
                'unit' => $product->unit ?? 'Pcs',
                'price_sell' => (int) $product->price_sell,
                'monitoring_stock' => (int) $product->monitoring_stock ?? 1,
                'stock' => $stockService->getEffectiveStock($product->id, $warehouseId, $storeId, $today),
            ];
        }));
    }
}
