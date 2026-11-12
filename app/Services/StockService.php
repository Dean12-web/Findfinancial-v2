<?php
namespace App\Services;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductDetail;
use App\Models\Catalog\ProductStock;

class StockService
{
    public function getRecordedStock(string $date, int $productId, int $warehouseId, int $storeId = 0): float
    {
        // Spesifik warehouse_id + store_id
        if ($storeId > 0) {
            $last = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->where('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance');

            if ($last !== null) {
                return (float) $last;
            }
        }

        // Fallback fisik gudang bersama (warehouse_id > 0)
        if ($warehouseId > 0) {
            $lastWh = ProductStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance');

            if ($lastWh !== null) {
                return (float) $lastWh;
            }
        }

        // Fallback toko murni tanpa gudang fisik (warehouse_id = 0)
        if ($warehouseId === 0 && $storeId > 0) {
            $lasStore = ProductStock::query()
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->where('date', '<=', $date)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->value('balance');

            if ($lasStore !== null) {
                return (float) $lasStore;
            }
        }

        return 0.0;
    }

    public function getBundleLeafComponents(int $productId, float $multiplier = 1.0, array $visited = []): array
    {
        if ($productId <= 0 || $multiplier <= 0 || in_array($productId, $visited, true)) {
            return [];
        }
        $visited[] = $productId;
        $components = ProductDetail::query()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('state', 'bundle')
                    ->orWhereNull('state')
                    ->orWhere('state', '');
            })
            ->get();
        $leafs = [];
        foreach ($components as $comp) {
            $itemId = (int) $comp->item_id;
            $qtyNeeded = (float) $comp->quantity * $multiplier;
            if ($qtyNeeded <= 0) {
                continue;
            }
            $compProduct = Product::select(['id', 'type'])->find($itemId);
            if (!$compProduct) {
                continue;
            }
            if ($compProduct->isBundle()) {
                $subLeafs = $this->getBundleLeafComponents($itemId, $qtyNeeded, $visited);
                foreach ($subLeafs as $subId => $subItem) {
                    if (isset($leafs[$subId])) {
                        $leafs[$subId]['quantity'] += $subItem['quantity'];
                    } else {
                        $leafs[$subId] = $subItem;
                    }
                }
            } else {
                if (isset($leafs[$itemId])) {
                    $leafs[$itemId]['quantity'] += $qtyNeeded;
                } else {
                    $leafs[$itemId] = [
                        'item_id' => $itemId,
                        'quantity' => $qtyNeeded,
                    ];
                }
            }
        }
        return $leafs;
    }
    public function getEffectiveStock(int $productId, int $warehouseId, int $storeId = 0, ?string $date = null): float
    {
        $date = $date ?? now('Asia/Jakarta')->toDateString();
        $product = Product::select(['id', 'type'])->find($productId);

        if (!$product) {
            return 0.0;
        }
        if ($product->isBundle()) {
            $physicalStock = $this->getRecordedStock($date, $productId, $warehouseId, $storeId);
            $unassembledStock = $this->getBundleStock($productId, $warehouseId, $storeId, $date);

            return max(0.0, $physicalStock) + (float) $unassembledStock;
        }
        return $this->getRecordedStock($date, $productId, $warehouseId, $storeId);
    }

    public function getBundleStock(int $productId, int $warehouseId, int $storeId = 0, ?string $date = null, array $visited = []): int
    {
        $date = $date ?? now('Asia/Jakarta')->toDateString();
        if ($productId <= 0 || in_array($productId, $visited, true)) {
            return 0;
        }
        $visited[] = $productId;

        $components = ProductDetail::query()
            ->where('product_id', $productId)
            ->where(function ($q) {
                $q->where('state', 'bundle')
                    ->orWhereNull('state')
                    ->orWhere('state', '');
            })
            ->get();
        if ($components->isEmpty()) {
            return 0;
        }
        $capacities = [];
        foreach ($components as $comp) {
            $itemId = (int) $comp->item_id;
            $qtyNeeded = (float) $comp->quantity;
            if ($qtyNeeded <= 0) {
                continue;
            }

            $compProduct = Product::select(['id', 'type'])->find($itemId);
            if (!$compProduct) {
                $capacities[] = 0;
                continue;
            }
            $available = $compProduct->isBundle()
                ? $this->getBundleStock($itemId, $warehouseId, $storeId, $date, $visited) : $this->getRecordedStock($date, $itemId, $warehouseId, $storeId);

            $capacities[] = (int) floor($available / $qtyNeeded);
        }
        return !empty($capacities) ? max(0, min($capacities)) : 0;
    }

    public function deductSaleStock(string $orderId, int $productId, float $soldQty, int $warehouseId, int $storeId, string $billingDate, string $updatedBy): void
    {
        $leafs = $this->getBundleLeafComponents($productId, $soldQty);

        if (!empty($leafs)) {
            $physicalBundleStock = $this->getRecordedStock($billingDate, $productId, $warehouseId, $storeId);
            if ($physicalBundleStock >= $soldQty) {
                $this->recordSaleMutation($orderId, $productId, $warehouseId, $storeId, $soldQty, $physicalBundleStock - $soldQty, $billingDate, $updatedBy);
                return;
            }
            $qtyFromLeafs = $soldQty;
            if ($physicalBundleStock > 0) {
                $this->recordSaleMutation(
                    $orderId,
                    $productId,
                    $warehouseId,
                    $storeId,
                    $physicalBundleStock,
                    0,
                    $billingDate,
                    $updatedBy
                );
                $qtyFromLeafs = $soldQty - $physicalBundleStock;
            }
            $remainingLeafs = $this->getBundleLeafComponents($productId, $qtyFromLeafs);
            foreach ($remainingLeafs as $leaf) {
                $leafQty = (float) $leaf['quantity'];
                $currentLeafStock = $this->getRecordedStock($billingDate, $leaf['item_id'], $warehouseId, $storeId);
                $this->recordSaleMutation(
                    $orderId,
                    $leaf['item_id'],
                    $warehouseId,
                    $storeId,
                    $leafQty,
                    $currentLeafStock - $leafQty,
                    $billingDate,
                    $updatedBy
                );
            }
            return;
        }
        $recordedStock = $this->getRecordedStock($billingDate, $productId, $warehouseId, $storeId);
        $this->recordSaleMutation($orderId, $productId, $warehouseId, $storeId, $soldQty, $recordedStock - $soldQty, $billingDate, $updatedBy);
    }

    public function duplicateProductToStore(string $orderId, int $sourceProductId, int $targetStoreId, int $projectId, float $qty, float $priceBuy, int $warehouseId, string $billingDate, string $updateBy): void
    {
        $sourceProduct = Product::find($sourceProductId);
        if (!$sourceProduct) {
            return;
        }
        $targetProduct = Product::query()
            ->where('code', $sourceProduct->code)
            ->where('store_id', $targetStoreId)
            ->first();

        $attributes = $sourceProduct->only($sourceProduct->getFillable());
        unset($attributes['created_date'], $attributes['updated_date']);
        $attributes['project_id'] = $projectId;
        $attributes['store_id'] = $targetStoreId;
        $attributes['apps'] = 'N';
        $attributes['apps_display'] = 'none';
        $attributes['price_buy'] = (int) round($priceBuy);
        $attributes['price_sell'] = 0;
        $attributes['updated_by'] = $updateBy;

        $now = now('Asia/Jakarta');

        if ($targetProduct) {
            $recorded = $this->getRecordedStock($billingDate, $targetProduct->id, 0, $targetStoreId);
            $attributes['updated_date'] = $now;
            $targetProduct->update($attributes);

            ProductStock::create([
                'order_id' => $orderId,
                'product_id' => $targetProduct->id,
                'warehouse_id' => $warehouseId,
                'store_id' => $targetStoreId,
                'credit' => (int) $qty,
                'debit' => 0,
                'balance' => (int) ($recorded + $qty),
                'date' => $billingDate,
                'calculated' => 'Y',
                'state' => 'purchase',
                'created_date' => $now,
                'updated_date' => $now,
                'updated_by' => $updateBy,
            ]);
        } else {
            $attributes['created_date'] = $now;
            $attributes['updated_date'] = $now;
            $newProduct = Product::create($attributes);

            ProductStock::create([
                'order_id' => $orderId,
                'product_id' => $newProduct->id,
                'warehouse_id' => $warehouseId,
                'store_id' => $targetStoreId,
                'credit' => (int) $qty,
                'debit' => 0,
                'balance' => (int) $qty,
                'date' => $billingDate,
                'calculated' => 'Y',
                'state' => 'purchase',
                'created_date' => $now,
                'updated_date' => $now,
                'updated_by' => $updateBy,
            ]);
        }
    }

    protected function recordSaleMutation(string $orderId, int $productId, int $warehouseId, int $storeId, float $debit, float $balance, string $date, string $updateBy): void
    {
        $now = now('Asia/Jakarta');
        ProductStock::create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'store_id' => $storeId,
            'warehouse_id' => $warehouseId,
            'debit' => (int) round($debit),
            'credit' => 0,
            'balance' => (int) round($balance),
            'date' => $date,
            'state' => 'sale',
            'calculated' => 'Y',
            'created_date' => $now,
            'updated_date' => $now,
            'updated_by' => $updateBy,
        ]);
    }
}