<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankAccountController extends Controller
{
    private function checkSuperAdmin()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang bisa mengelola rekening bank.');
        }
    }

    public function index()
    {
        $this->checkSuperAdmin();
        $banks = BankAccount::orderByDefault()->paginate(25);
        return view('bank-accounts.index', compact('banks'));
    }

    public function store(Request $request)
    {
        $this->checkSuperAdmin();
        $validated = $request->validate([
            'nama_bank' => 'required|string|max:100',
            'atas_nama' => 'required|string|max:200',
            'no_rekening' => 'required|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('bank-logos', 'public');
        }

        BankAccount::create($validated);
        AuditLogService::log('bank_account', 'create', "Menambahkan rekening: {$validated['nama_bank']} - {$validated['atas_nama']}");

        return back()->with('success', 'Rekening bank berhasil ditambahkan!');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $this->checkSuperAdmin();
        $validated = $request->validate([
            'nama_bank' => 'required|string|max:100',
            'atas_nama' => 'required|string|max:200',
            'no_rekening' => 'required|string|max:50',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'aktif' => 'boolean',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($bankAccount->logo) {
                Storage::disk('public')->delete($bankAccount->logo);
            }
            $validated['logo'] = $request->file('logo')->store('bank-logos', 'public');
        }

        $bankAccount->update($validated);
        AuditLogService::log('bank_account', 'update', "Mengupdate rekening: {$validated['nama_bank']} - {$validated['atas_nama']}");

        return back()->with('success', 'Rekening bank berhasil diupdate!');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $this->checkSuperAdmin();
        if ($bankAccount->logo) {
            Storage::disk('public')->delete($bankAccount->logo);
        }
        AuditLogService::log('bank_account', 'delete', "Menghapus rekening: {$bankAccount->nama_bank} - {$bankAccount->atas_nama}");
        $bankAccount->delete();
        return back()->with('success', 'Rekening bank berhasil dihapus!');
    }
}
