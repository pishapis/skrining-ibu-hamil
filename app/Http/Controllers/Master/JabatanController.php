<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JabatanController extends Controller
{
    /**
     * Display the registration view.
     */

    public function index()
    {
        $title      = "Manajemen Jabatan";
        $jabatans   = Jabatan::paginate(10, ['*'], 'page_jabatan');
;

        return view('pages.master.jabatan.index', [
            'title' => $title,
            'jabatans' => $jabatans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string'
        ]);

        Jabatan::create([
            'nama' => $validated['nama'],
        ]);

        return back()->with('success', 'Data Jabatan Berhasil Dibuat');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string',
            'id' => 'required'
        ]);

        $jabatan = Jabatan::findOrFail($validated['id']);
        $jabatan->update([
            'nama' => $validated['nama']
        ]);

        return back()->with('success', 'Data Jabatan Berhasil Dibuat');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $jabatan = Jabatan::findOrFail($id);
        $jabatan->delete();
        return back()->with('success', 'Jabatan Berhasil Dihapus');
    }
}