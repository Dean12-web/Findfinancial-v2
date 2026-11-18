<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Setting\FormatTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormatTransactionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'param_1' => ['required', 'string', 'max:20'],
            'param_2' => ['nullabel', 'string', 'max:20'],
            'start' => ['required', 'integer', 'min:0'],
        ]);

        $projectId = (int) ($request->user()->project_id ?: ($request->user()->store?->project_id ?? 0));

        FormatTransaction::create([
            'project_id' => $projectId,
            'param_1' => $validated['param_1'],
            'param_2' => $validated['param_2'],
            'start' => (int) $validated['start'],
            'status' => 1,
            'updated_by' => (string) $request->user()->username,
        ]);

        return back()->with('success', 'Berhasil menyimpan format nomor transaksi.');
    }

    public function destroy(Request $request, FormatTransaction $formatTransaction): RedirectResponse
    {
        $projectId = (int) ($request->user()->project_id ?: ($request->user()->store?->project_id ?? 0));
        abort_unless((int) $formatTransaction->project_id === $projectId, 403);

        $formatTransaction->delete();

        return back()->with('success', 'Format nomor transaksi berhasil dihapus.');
    }
}
