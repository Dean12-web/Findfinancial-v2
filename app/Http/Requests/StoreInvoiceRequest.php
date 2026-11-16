<?php

namespace App\Http\Requests;

use App\Models\Accounting\Bank;
use App\Models\Catalog\Product;
use App\Models\Contact\Customer;
use App\Models\Organization\Store;
use App\Models\Organization\Warehouse;
use App\Models\Setting\FormatTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user || !$user->canAccess('invoice')) {
            return false;
        }

        if ($this->input('buyer_type') === 'store') {
            return in_array($user->role_user, ['superadmin', 'admin'], true);
        }
        return true;
    }

    protected function prepareForValidation(): void
    {
        $paidRaw = preg_replace('/[^0-9]/', '', (string) $this->input('paid', 0));

        $this->merge([
            'buyer_id' => (int) $this->input('buyer_id', 0),
            'buyer_type' => $this->input('buyer_type', 'customer'),
            'warehouse_id' => (int) $this->input('warehouse_id', 0),
            'format_trx_id' => (int) $this->input('format_trx_id', 0),
            'shipping_cost' => max(0, (float) $this->input('shipping_cost', 0)),
            'discount_input' => max(0, (float) $this->input('discount_input', 0)),
            'paid' => (int) ($paidRaw === '' ? 0 : $paidRaw),
            'id_bank' => ((int) $this->input('id_bank', 0)) > 0 ? (int) $this->input('id_bank') : null,
            'is_split' => filter_var($this->input('is_split', false), FILTER_VALIDATE_BOOLEAN),
            'is_pos' => filter_var($this->input('is_pos', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'buyer_type' => ['required', 'in:customer,store'],
            'buyer_id' => ['required', 'integer', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['required', 'integer', 'min:0'],
            'format_trx_id' => ['nullable', 'integer', 'min:0'],
            'billing_date' => ['required', 'date'],
            'billing_due_date' => ['required', 'date'],
            'billing_address' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'string', 'max:100'],
            'shipping_date' => ['nullable', 'date'],
            'shipping_option' => ['nullable', 'string', 'max:50'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:150'],
            'memo' => ['nullable', 'string', 'max:150'],

            // Discount Master & Pembayaran
            'discount_type' => ['required', 'in:percentage,amount,point'],
            'discount_input' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['required', 'integer', 'min:0'],
            'id_bank' => ['nullable', 'integer', 'exists:tb_bank,id'],
            'is_split' => ['boolean'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],

            // split payment
            'split_payments' => ['required_if:is_split,true', 'array'],
            'split_payments.*.id_bank' => ['required_with:split_payments', 'integer', 'exists:tb_bank,id'],
            'split_payments.*.amount' => ['required_with:split_payments', 'numeric', 'min:0'],
            'split_payments.*.notes' => ['nullable', 'string', 'max:30'],

            // Detail Keranjang Produk
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:tb_product,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:percentage,nominal'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();
                if (!$user) {
                    return;
                }

                $projectId = (int) ($user->project_id ?: ($user->store?->project_id ?? 0));
                $storeId = (int) ($user->store_id ?? 0);
                $isBranchStaff = in_array($user->role_user, ['adminbranch', 'cashier'], true);

                // 1.Validasi tanggal backdate terhadap tb_user.can_bypass_date maupun limitationAccess
                $billingDate = Carbon::parse($this->input('billing_date'))->startOfDay();
                $today = now('Asia/Jakarta')->startOfDay();
                $canBypassDate = (bool) $user->can_bypass_date || $user->canAccess('bypass_date');

                if ($billingDate->lt($today) && !$canBypassDate) {
                    $validator->errors()->add('billing_date', 'Tanggal transaksi tidak sesuai dengan hari ini, hubungi SPV.');
                }

                // 2.Validasi gudang wajib dipilih dan milik tenant untuk non-cabang
                $warehouseId = (int) $this->input('warehouse_id');
                if (!$isBranchStaff) {
                    if ($warehouseId <= 0) {
                        $validator->errors()->add('warehouse_id', 'Gudang harus dipilih.');
                    } elseif (!Warehouse::where('id', $warehouseId)->where('project_id', $projectId)->exists()) {
                        $validator->errors()->add('warehouse_id', 'Gudang tidak valid untuk project Anda.');
                    }
                }

                // 3.Validasi format nomor transaksi milik tenant jika dipilih
                $formatTrxId = (int) $this->input('format_trx_id');
                if ($formatTrxId > 0 && !FormatTransaction::where('id', $formatTrxId)->where('project_id', $projectId)->exists()) {
                    $validator->errors()->add('format_trx_id', 'Format nomor transaksi tidak valid.');
                }

                // 4.Validasi pembeli (Store atau Customer) milik tenan (anti-IDOR)
                $buyerId = (int) $this->input('buyer_id');
                if ($this->input('buyer_type') === 'store') {
                    if ($buyerId <= 0 || !Store::where('id', $buyerId)->where('project_id', $projectId)->exists()) {
                        $validator->errors()->add('buyer_id', 'Toko pembeli tidak valid atau belum dipilih.');
                    }
                } elseif ($buyerId > 0) {
                    $customer = Customer::where('id', $buyerId)->forProject($projectId)->first();
                    if (!$customer) {
                        $validator->errors()->add('buyer_id', 'Pelanggan tidak ditemukan.');
                    } elseif ($this->input('discount_type') === 'point' && (int) $this->input('discount_input') > (int) $customer->points) {
                        $validator->errors()->add('discount_input', 'Point pelanggan tidak mencukupi.');
                    }
                } elseif ($this->input('discount_type') === 'point' && (int) $this->input('discount_input') > 0) {
                    $validator->errors()->add('discount_input', 'Diskon poin hanya berlaku untuk pelanggan terdaftar.');
                }

                // 5.Validasi kepemilikan produk cabang
                $productIds = collect($this->input('items', []))->pluck('product_id')->filter()->unique()->all();
                if (!empty($productIds)) {
                    $validCount = Product::query()
                        ->whereIn('id', $productIds)
                        ->where('project_id', $projectId)
                        ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId))->count();

                    if ($validCount !== count($productIds)) {
                        $validator->errors()->add('items', 'Terdapat produk yang tidak valid atau bukan milik cabang anda');
                    }
                }

                //6.validasi rekening utama
                $bankScopeQuery = fn(int $bankId) => Bank::query()
                    ->activeAccounts()
                    ->where('id', $bankId)
                    ->when($storeId > 0, fn($q) => $q->where('store_id', $storeId))
                    ->when($storeId === 0, fn($q) => $q->where('project_id', $projectId));


                $paidAmount = (int) $this->input('paid', 0);
                $primaryBankId = (int) $this->input('id_bank', 0);

                if ($paidAmount > 0 && !$this->boolean('is_split')) {
                    if ($primaryBankId <= 0 || !$bankScopeQuery($primaryBankId)->exists()) {
                        $validator->errors()->add('id_bank', 'Tujauan dana belum dipilih atau tidak valid untuk cabang ini.');
                    }
                } elseif ($primaryBankId > 0 && !$bankScopeQuery($primaryBankId)->exists()) {
                    $validator->errors()->add('id_bank', 'Tujuan dana tidak valid untuk cabang ini.');
                }

                if ($this->boolean('is_split')) {
                    $splits = collect($this->input('split_payments', []))->filter(fn($sp) => (float) ($sp['amount'] ?? 0) > 0);

                    if ($splits->isEmpty()) {
                        $validator->errors()->add('split_payments', 'Nominal pembayaran split tidak boleh kosong.');
                    }

                    $splitTotal = (int) round($splits->sum(fn($sp) => (float) ($sp['amount'] ?? 0)));
                    if ($splits->isNotEmpty() && $splitTotal !== (int) $this->input('paid')) {
                        $validator->errors()->add('split_payments', 'Total Nominal Split payment harus sama dengan jumlah dibayar.');
                    }

                    $uniqueBanks = $splits->pluck('id_bank')->unique();
                    if ($splits->count() > 1 && $uniqueBanks->count() < $splits->count()) {
                        $validator->errors()->add('split_payments', 'Akun bank pada Split Payment Tidak boleh sama.');
                    }
                    foreach ($uniqueBanks as $spBankId) {
                        if (!$bankScopeQuery((int) $spBankId)->exists()) {
                            $validator->errors()->add('split_payments', 'Salah satu akun bank split Payment tidak valid');
                            break;
                        }
                    }

                }


            }
        ];
    }
}