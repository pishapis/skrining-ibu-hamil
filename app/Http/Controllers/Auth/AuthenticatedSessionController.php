<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DataDiri;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    public function login_admin(): View
    {
        return view('auth.login-admin');
    }

    /**
     * Handle user (ibu) login with NIK only.
     */
    public function storeUser(Request $request): RedirectResponse
    {
        $request->validate([
            'nik' => ['required', 'string', 'size:16', 'regex:/^[0-9]{16}$/'],
        ], [
            'nik.required' => 'NIK harus diisi.',
            'nik.size' => 'NIK harus 16 digit.',
            'nik.regex' => 'NIK harus berupa angka 16 digit.',
        ]);

        // Cari data diri berdasarkan NIK
        $dataDiri = DataDiri::where('nik', $request->nik)
            ->where(function ($query) {
                $query->whereNotNull('faskes_rujukan_id')
                    ->orWhereNotNull('puskesmas_id');
            })
            ->first();


        if (!$dataDiri) {
            throw ValidationException::withMessages([
                'nik' => 'NIK tidak terdaftar dalam sistem. Silakan daftar terlebih dahulu.',
            ]);
        }

        // Cek apakah user terkait ada dan role-nya ibu (role_id = 1)
        $user = $dataDiri->user;
        
        if (!$user || $user->role_id != 1) {
            throw ValidationException::withMessages([
                'nik' => 'NIK tidak valid untuk login ibu.',
            ]);
        }

        // Login user
        Auth::login($user, true); // true = remember me
        
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle admin/superadmin login with email/username & password.
     */
    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        // Authenticate menggunakan LoginRequest yang sudah ada
        $request->authenticate();

        // Validasi role: hanya admin (role_id 2) dan superadmin (role_id 3)
        $user = Auth::user();
        
        if ($user->role_id == 1) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'Gunakan login ibu dengan NIK untuk akun ini.',
            ]);
        }

        if (!in_array($user->role_id, [2, 3])) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => 'Anda tidak memiliki akses sebagai administrator.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $roleId = optional(Auth::user())->role_id;
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if (in_array($roleId, [2, 3], true)) {
            return redirect()->route('login.admin.view');
        }

        return redirect('/');
    }
}