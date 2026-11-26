<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcceptPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $rawPaid = (string) $this->input('paid', '0');
        $cleanPaid = (int) preg_replace('/[^0-9]/', '', $rawPaid);

        $this->merge([
            'paid' => $cleanPaid,
            'debit_deposit' => $this->boolean('debit_deposit'),
            'format_transaction_id' => $this->filled('format_transaction_id')
                ? (int) $this->input('format_transaction_id')
                : null,
        ]);
    }

    public function rules(): array
    {
        $user = $this->user();
        $userProjectId = (int) ($user?->project_id ?: ($user?->store?->project_id ?? 0));

        return [
            'order_id' => ['required', 'string', 'max:100'],
            'debit_deposit' => ['required', 'boolean'],
            'format_transaction_id' => [
                'nullable',
                'integer',
                Rule::exists('tb_format_transaction', 'id')->where('project_id', $userProjectId),
            ],
            'id_bank_destination' => [
                'required_if:debit_deposit,false',
                'nullable',
                'integer',
                Rule::exists('tb_bank', 'id')->where(function ($query) use ($user, $userProjectId) {
                    $query->where('category', 'Kas & Bank');
                    if ($user?->store_id !== null) {
                        $query->where('store_id', $user->store_id);
                    } else {
                        $query->where('project_id', $userProjectId);
                    }
                }),
            ],
            'billing_date' => ['required', 'date'],
            'tags' => ['nullable', 'string', 'max:255'],
            'paid' => ['required', 'integer', 'min:1'],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_bank_destination.required_if' => 'Pastikan kas/bank tujuan sudah ditentukan.',
            'id_bank_destination.exists' => 'Rekening kas/bank tujuan tidak valid atau bukan milik cabang/proyek Anda.',
            'format_transaction_id.exists' => 'Format nomor transaksi tidak valid atau bukan milik proyek Anda.',
            'paid.min' => 'Nominal pembayaran harus lebih dari Rp0.',
            'billing_date.required' => 'Tanggal transaksi wajib diisi.',
        ];
    }
}
