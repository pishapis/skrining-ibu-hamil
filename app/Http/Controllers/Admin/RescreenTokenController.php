<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RescreenToken;
use App\Models\DataDiri;
use App\Models\UsiaHamil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RescreenTokenController extends Controller
{
    public function index(Request $req)
    {
        $this->authorize('manage-rescreen-tokens');

        $q = RescreenToken::query()
            ->with([''])
            ->when($req->filled('jenis'), fn($qq) => $qq->where('jenis', $req->jenis))
            ->when($req->filled('status'), fn($qq) => $qq->where('status', $req->status))
            ->when($req->filled('trimester'), fn($qq) => $qq->where('trimester', $req->trimester))
            ->when($req->filled('ibu_id'), fn($qq) => $qq->where('ibu_id', $req->ibu_id))
            ->orderByDesc('created_at');

        $tokens = $q->paginate(20);

        // sederhana: dropdown Ibu untuk form
        $ibus = DataDiri::orderBy('nama_lengkap')->select('id', 'nama_lengkap')->get();

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
            'expires_at'  => 'nullable|date',
            'reason'      => 'nullable|string|max:2000',
        ]);

        $riwayat = UsiaHamil::where('ibu_id', $data['ibu_id'])->latest('created_at')->first();

        $data['usia_hamil_id'] = $riwayat?->id;
        $data['issued_by'] = Auth::id();
        $data['max_uses'] = $data['max_uses'] ?? 1;

        $token = RescreenToken::create($data);

        return redirect()->route('pages.admin.rescreens.index')
            ->with('success', "Token skrining ulang dibuat (#{$token->id}).");
    }

    public function revoke(RescreenToken $rescreen)
    {
        $this->authorize('manage-rescreen-tokens');
        if ($rescreen->status === 'active') {
            $rescreen->status = 'revoked';
            $rescreen->save();
        }
        return back()->with('success', 'Token dicabut.');
    }

    public function reactivate(RescreenToken $rescreen)
    {
        $this->authorize('manage-rescreen-tokens');
        if (in_array($rescreen->status, ['revoked', 'used'])) {
            $rescreen->status = 'active';
            $rescreen->save();
        }
        return back()->with('success', 'Token diaktifkan kembali.');
    }
}
