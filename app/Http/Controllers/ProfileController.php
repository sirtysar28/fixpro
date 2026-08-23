<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\SerialNumber;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Redeem Serial Number via web
     */
    public function redeemSerial(Request $request): RedirectResponse
    {
        $request->validate([
            'serial_code' => 'required|string',
        ]);

        $serial = SerialNumber::where('serial_code', $request->serial_code)->first();

        if (!$serial) {
            return Redirect::route('profile.edit')
                ->with('serial_error', 'Serial Number tidak valid.')
                ->withInput();
        }

        if ($serial->is_used) {
            return Redirect::route('profile.edit')
                ->with('serial_error', 'Serial Number sudah pernah digunakan.')
                ->withInput();
        }

        $user = $request->user();

        if ($serial->email !== $user->email) {
            return Redirect::route('profile.edit')
                ->with('serial_error', 'Serial Number ini bukan untuk akun Anda. Serial ini untuk email: ' . $serial->email)
                ->withInput();
        }

        $success = $serial->redeem($user);

        if (!$success) {
            return Redirect::route('profile.edit')
                ->with('serial_error', 'Gagal redeem Serial Number. Silakan hubungi admin.')
                ->withInput();
        }

        AuditLogService::custom('serial_number', 'redeem', "Redeem Serial Number: {$request->serial_code} — akun {$user->email} jadi permanen");

        return Redirect::route('profile.edit')
            ->with('serial_success', 'Serial Number berhasil diredeem! Akun Anda sekarang aktif selamanya.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
