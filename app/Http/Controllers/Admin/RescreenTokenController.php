<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RescreenToken;
use App\Models\DataDiri;
use App\Models\HasilEpds;
use App\Models\UsiaHamil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RescreenTokenController extends Controller
{
    public function index(Request $req)
    {
        $this->authorize('manage-rescreen-tokens');

        $q = RescreenToken::query()
            ->with(['ibu:id,nama,nik', 'issuedBy:id,name'])
            ->when($req->filled('jenis'), fn($qq) => $qq->where('jenis', $req->jenis))
            ->when($req->filled('status'), fn($qq) => $qq->where('status', $req->status))
            ->when($req->filled('trimester'), fn($qq) => $qq->where('trimester', $req->trimester))
            ->when($req->filled('ibu_id'), fn($qq) => $qq->where('ibu_id', $req->ibu_id))
            ->orderByDesc('created_at');

        $tokens = $q->paginate(20);

        // Dropdown Ibu untuk form - hanya yang punya data kehamilan
        $ibus = DataDiri::Ibu()
            ->where('puskesmas_id', Auth::user()->puskesmas_id)
            ->whereHas('usiaHamil', function($q) {
                $q->whereNotNull('hpht')->whereNotNull('hpl');
            })
            ->orderBy('nama')
            ->select('id', 'nama', 'nik')
            ->get();

        return view('pages.admin.rescreens.index', compact('tokens', 'ibus'));
    }

    public function store(Request $req)
    {
        $this->authorize('manage-rescreen-tokens');

        $data = $req->validate([
            'ibu_id'      => 'required|exists:data_diri,id',
            'jenis'       => 'required|in:epds,dass',
            'trimester'   => 'required|in:trimester_1,trimester_2,trimester_3,pasca_hamil',
            'max_uses'    => 'nullable|integer|min:1|max:10',
            'expires_at'  => 'nullable|date|after:now',
            'reason'      => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // Ambil riwayat kehamilan terbaru
            $riwayat = UsiaHamil::where('ibu_id', $data['ibu_id'])
                ->whereNotNull('hpht')
                ->whereNotNull('hpl')
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (!$riwayat) {
                DB::rollBack();
                return back()->withErrors(['ibu_id' => 'Ibu belum memiliki data kehamilan (HPHT/HPL).']);
            }

            // Cek apakah sudah ada token aktif untuk kombinasi yang sama
            $existingActive = RescreenToken::active()
                ->where('ibu_id', $data['ibu_id'])
                ->where('jenis', $data['jenis'])
                ->where('trimester', $data['trimester'])
                ->first();

            if ($existingActive) {
                DB::rollBack();
                return back()->withErrors([
                    'trimester' => 'Sudah ada token aktif untuk kombinasi ini. Cabut token lama terlebih dahulu.'
                ]);
            }

            // Buat token baru
            $token = RescreenToken::create([
                'ibu_id'         => $data['ibu_id'],
                'usia_hamil_id'  => $riwayat->id,
                'jenis'          => $data['jenis'],
                'trimester'      => $data['trimester'],
                'mode'           => 'kehamilan', // selalu kehamilan untuk token trimester
                'periode'        => null,
                'issued_by'      => Auth::id(),
                'reason'         => $data['reason'] ?? null,
                'expires_at'     => $data['expires_at'] ?? null,
                'max_uses'       => $data['max_uses'] ?? 1,
                'used_count'     => 0,
                'status'         => 'active',
            ]);

            if($data['jenis'] === "epds"){
                HasilEpds::where('trimester', $data['trimester'])
                ->where('ibu_id', $data['ibu_id'])
                ->where('mode', 'kehamilan')
                ->where('usia_hamil_id', $riwayat->id)
                ->where('status', 'submitted')
                ->update([
                    'rescreen_token_id' => $token->id
                ]);
            }

            DB::commit();

            return redirect()->route('rescreen.index')
                ->with('success', "Token skrining ulang berhasil diterbitkan (ID: {$token->id}).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create rescreen token: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Gagal membuat token. Silakan coba lagi.']);
        }
    }

    public function revoke(RescreenToken $rescreen)
    {
        $this->authorize('manage-rescreen-tokens');
        
        if ($rescreen->status === 'active') {
            $rescreen->status = 'revoked';
            $rescreen->save();
            return back()->with('success', 'Token berhasil dicabut.');
        }
        
        return back()->with('info', 'Token sudah tidak aktif.');
    }

    public function reactivate(RescreenToken $rescreen)
    {
        $this->authorize('manage-rescreen-tokens');
        
        if (in_array($rescreen->status, ['revoked', 'used'])) {
            $rescreen->status = 'active';
            $rescreen->used_count = 0; // reset counter saat reaktivasi
            $rescreen->save();
            return back()->with('success', 'Token berhasil diaktifkan kembali.');
        }
        
        return back()->with('info', 'Token sudah aktif.');
    }
}