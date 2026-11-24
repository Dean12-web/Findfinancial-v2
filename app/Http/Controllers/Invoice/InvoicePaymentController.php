<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Transaction\StoreOrderPayment;
use App\Services\BalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoicePaymentController extends Controller
{
    public function destroy(Request $request, int $id, BalanceService $balanceService): RedirectResponse
    {
        $user = $request->user();

        // 1. Otorisasi
        if (!$user->isSuperadmin() && (!$user->limitationAccess || !$user->limitationAccess->canAccess('delete_approve_payment'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus pembayaran ini.');
        }

        // 2. Ambil data payment beserta order untuk validasi hak akses toko/proyek
        $payment = StoreOrderPayment::with('order')->findOrFail($id);

        if ($payment->order) {
            if (in_array($user->role_user, ['adminbranch', 'cashier'], true)) {
                abort_unless((int) $payment->order->store_id === (int) $user->store_id, 403);
            } elseif (!$user->isSuperadmin()) {
                abort_unless((int) $payment->order->project_id === (int) ($user->project_id ?: ($user->store?->project_id ?? 0)), 403);
            }
        }

        // 3. Eksekusi penghapusan & rekonsiliasi saldo kas/bank
        DB::transaction(function () use ($payment, $balanceService) {
            $bankId = $payment->id_bank_destination ?: $payment->id_bank;

            $payment->delete();

            if ($bankId) {
                $balanceService->syncBankBalance((int) $bankId);
            }
        });

        return back()->with('success', 'Riwayat pembayaran berhasil dihapus.');
    }
}

