<?php
namespace App\Services;

use App\Models\Setting\FormatTransaction;
use Illuminate\Support\Facades\DB;
class TransactionNumberService
{
    public function generateOrderNumber(
        ?int $formatTrxId,
        int $projectId,
        string $targetTable = 'tb_store_order'
    ): ?string {
        if (!empty($formatTrxId) && $formatTrxId > 0) {
            $format = FormatTransaction::query()
                ->where('id', $formatTrxId)
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->first();

            if ($format) {
                $nextSequence = (int) $format->start + 1;
                $format->update(['start' => $nextSequence]);

                return trim("{$format->param_1}/{$nextSequence}/{$format->param_2}", '/');
            }
        }
        if ($targetTable === 'tb_store_order') {
            return null;
        }
        $query = DB::table($targetTable);
        if (DB::connection()->getDriverName() === 'sqlite') {
            $lastNumeric = $query
                ->where('order_id' . '!=', '')
                ->whereRaw("order_id NOT GLOB '*[^0-9]*'")
                ->orderByRaw('CAST(order_id AS INTEGER) DESC')
                ->value('order_id');
        } else {
            $lastNumeric = $query
                ->whereRaw("order_id REGEXP '^[0-9]+$'")
                ->orderByRaw('CAST(order_id AS SIGNED) DESC')
                ->lockForUpdate()
                ->value('order_id');
        }
        return (string) ($lastNumeric ? ((int) $lastNumeric + 1) : 1);
    }
}
