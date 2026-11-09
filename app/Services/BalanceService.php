<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;



class BalanceService
{
    //Sinkron Saldo Rekening / Kasir Fisik
    public function syncBankBalance(int $bankId): void
    {
        DB::statement('CALL FixBankBalance(?)', [$bankId]);
    }

    //Sinkron Poin Reward Pelanggan
    public function syncCustomerPoints(int $customerId): void
    {
        DB::statement('CALL FixPointsCustomer(?)', [$customerId]);
    }

    //Sinkron Saldo Deposit Pelanggan
    public function syncCustomerDeposit(int $customerId): void
    {
        DB::statement('CALL FixDepositCustomer(?)', [$customerId]);
    }

    //Sinkron Saldo Deposi Supplier / Vendor
    public function syncSupplierDeposit(int $supplierId): void
    {
        DB::statement('CALL FixDepositSupplier(?)', [$supplierId]);
    }

    /** Rekonsiliasi Menyeluruh untuk Transaksi Faktor / Pos
     * Menjalankan sinkronisasi kas, poin, dan deposi dalam satu eksekusi 
     */
    public function syncAllForOrder(int $bankId, ?int $customerId = null, ?int $supplierId = null): void
    {
        $this->syncBankBalance($bankId);
        if ($customerId) {
            $this->syncCustomerPoints($customerId);
            $this->syncCustomerDeposit($customerId);
        }

        if ($supplierId) {
            $this->syncSupplierDeposit($supplierId);
        }
    }
}