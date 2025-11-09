<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Puskesmas;
use App\Models\GeneratedLink;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;

class ScreeningBarcodeController extends Controller
{
    /**
     * Display the barcode generator form
     */
    public function index()
    {
        $puskesmas = Puskesmas::where('id', Auth::user()->puskesmas_id)->get();
        return view('pages.admin.generate.index', compact('puskesmas'));
    }

    /**
     * Generate link and barcode for screening
     */
    public function generateLink(Request $request)
    {
        // Validasi puskesmas_id
        $request->validate([
            'puskesmas_id' => 'required|exists:puskesmas,id',
            'expires_at' => 'nullable|date|after:today'
        ]);

        try {
            // Ambil puskesmas_id dari request
            $puskesmasId = $request->input('puskesmas_id');
            $expiresAt = $request->input('expires_at');

            // Generate unique token dengan SHA1
            $timestamp = time();
            $shortCode = $this->generateUniqueShortCode();
            $token = sha1($puskesmasId . $timestamp . $shortCode . config('app.key'));

            // Buat URL skrining dengan token
            $url = route('skrining.umum', [
                'puskesmas_id' => $puskesmasId,
                'token' => $token,
                'ts' => $timestamp
            ]);

            // Generate short code menggunakan sistem internal

            // Buat short URL
            $shortUrl = route('skrining.umum', $shortCode);

            // Generate QR Code
            $qrCode = new QrCode($shortUrl);
            $writer = new PngWriter();
            $qrCodeData = $writer->write($qrCode)->getString();

            // Simpan data ke database
            $generatedLink = GeneratedLink::create([
                'puskesmas_id' => $puskesmasId,
                'original_url' => $url,
                'qr_code' => base64_encode($qrCodeData),
                'short_url' => $shortUrl,
                'short_code' => $shortCode,
                'token' => $token,
                'expires_at' => $expiresAt,
                'created_by' => auth()->id() ?? null,
                'is_active' => true
            ]);

            $puskesmas = Puskesmas::find($puskesmasId);

            // Return sebagai JSON jika request AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'url' => $url,
                        'shortUrl' => $shortUrl,
                        'shortCode' => $shortCode,
                        'qrCode' => base64_encode($qrCodeData),
                        'puskesmas_name' => $puskesmas->name,
                        'expires_at' => $expiresAt,
                        'token' => $token
                    ]
                ]);
            }

            // Mengirimkan ke tampilan (untuk non-AJAX request)
            return view('pages.admin.generate.index', [
                'url' => $url,
                'shortUrl' => $shortUrl,
                'shortCode' => $shortCode,
                'qrCode' => base64_encode($qrCodeData),
                'puskesmas' => $puskesmas,
                'expires_at' => $expiresAt
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating screening link: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat generate link'
                ], 500);
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan saat generate link');
        }
    }

    /**
     * Display screening form
     */
    public function showForm(Request $request)
    {
        $puskesmasId = $request->puskesmas_id;
        $token = $request->token;
        $timestamp = $request->ts;

        // Validasi token jika ada
        if ($token && !$this->validateToken($puskesmasId, $token, $timestamp)) {
            abort(403, 'Token tidak valid atau sudah kedaluwarsa');
        }

        $puskesmas = Puskesmas::find($puskesmasId);

        if (!$puskesmas) {
            abort(404, 'Puskesmas tidak ditemukan');
        }

        // Update statistik akses jika link dari database
        if ($token) {
            GeneratedLink::where('token', $token)->increment('access_count');
        }

        return view('pages.skrining.skrining-umum', compact('puskesmas', 'token'));
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(Request $request)
    {
        $puskesmasId = Auth::user()->puskesmas_id;

        $query = GeneratedLink::query();

        if ($puskesmasId) {
            $query->where('puskesmas_id', $puskesmasId);
        }

        $stats = [
            'recent_links' => $query->with('puskesmas')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'total_generated' => $query->count(),
            'active_links' => $query->where('is_active', true)->count(),
            'expired_links' => $query->where('expires_at', '<', now())->count(),
            'total_access' => $query->sum('access_count'),
        ];

        return response()->json($stats);
    }

    /**
     * Deactivate a generated link
     */
    public function deactivateLink($id)
    {
        try {
            $link = GeneratedLink::findOrFail($id);
            $link->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Link berhasil dinonaktifkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menonaktifkan link'
            ], 500);
        }
    }

    /**
     * Generate unique short code menggunakan beberapa metode
     */
    private function generateUniqueShortCode($method = 'random', $length = 6)
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            switch ($method) {
                case 'base62':
                    $shortCode = $this->generateBase62ShortCode();
                    break;
                case 'hash':
                    $shortCode = $this->generateHashBasedShortCode($length);
                    break;
                case 'sequential':
                    $shortCode = $this->generateSequentialShortCode();
                    break;
                case 'custom':
                    $shortCode = $this->generateCustomShortCode($length);
                    break;
                case 'random':
                default:
                    $shortCode = $this->generateRandomShortCode($length);
                    break;
            }

            $attempts++;
        } while (GeneratedLink::where('short_code', $shortCode)->exists() && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts) {
            throw new \Exception('Unable to generate unique short code after ' . $maxAttempts . ' attempts');
        }

        return $shortCode;
    }

    /**
     * Method 1: Random string (paling sederhana dan aman)
     */
    private function generateRandomShortCode($length = 6)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $shortCode = '';

        for ($i = 0; $i < $length; $i++) {
            $shortCode .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $shortCode;
    }

    /**
     * Method 2: Base62 encoding berdasarkan timestamp + random
     */
    private function generateBase62ShortCode()
    {
        $timestamp = time();
        $random = random_int(1, 999);
        $number = $timestamp + $random;

        return $this->base62Encode($number);
    }

    /**
     * Method 3: Hash-based short code
     */
    private function generateHashBasedShortCode($length = 6)
    {
        $data = time() . Str::random(16) . Auth::id();
        $hash = hash('sha256', $data);

        // Convert to base62 and take first $length characters
        $shortCode = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        for ($i = 0; $i < $length; $i++) {
            $index = hexdec(substr($hash, $i * 2, 2)) % 62;
            $shortCode .= $characters[$index];
        }

        return $shortCode;
    }

    /**
     * Method 4: Sequential dengan encoding
     */
    private function generateSequentialShortCode()
    {
        $lastLink = GeneratedLink::orderBy('id', 'desc')->first();
        $nextId = $lastLink ? $lastLink->id + 1 : 1;

        // Add some randomness to avoid predictable patterns
        $number = $nextId + random_int(1000, 9999);

        return $this->base62Encode($number);
    }

    /**
     * Method 5: Custom method dengan prefix berdasarkan Puskesmas
     */
    private function generateCustomShortCode($length = 6)
    {
        // Bisa menggunakan prefix berdasarkan puskesmas atau tanggal
        $prefix = 'p'; // p untuk puskesmas
        $remaining = $length - 1;

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomPart = '';

        for ($i = 0; $i < $remaining; $i++) {
            $randomPart .= $characters[random_int(0, 61)];
        }

        return $prefix . $randomPart;
    }

    /**
     * Base62 encoding function
     */
    private function base62Encode($number)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($chars);
        $result = '';

        if ($number == 0) {
            return '0';
        }

        while ($number > 0) {
            $result = $chars[$number % $base] . $result;
            $number = intval($number / $base);
        }

        return $result;
    }

    /**
     * Base62 decoding function (untuk keperluan analytics atau debugging)
     */
    private function base62Decode($string)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($chars);
        $result = 0;
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $result = $result * $base + strpos($chars, $string[$i]);
        }

        return $result;
    }

    /**
     * Redirect dari short URL ke URL asli
     */
    public function redirectShort($shortCode)
    {
        $link = GeneratedLink::where('short_code', $shortCode)
            ->where('is_active', true)
            ->first();

        if (!$link) {
            // Log untuk monitoring
            Log::warning('Short URL not found', [
                'short_code' => $shortCode,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            abort(404, 'Link tidak ditemukan atau sudah tidak aktif');
        }

        // Check expiration
        if ($link->expires_at && $link->expires_at < now()) {
            Log::info('Expired link accessed', [
                'short_code' => $shortCode,
                'expired_at' => $link->expires_at
            ]);

            abort(410, 'Link sudah kedaluwarsa');
        }

        // Update click statistics dengan informasi tambahan
        $link->increment('access_count');
        $link->update([
            'last_accessed_at' => now(),
            'last_access_ip' => request()->ip(),
            'last_user_agent' => request()->userAgent()
        ]);

        // Log successful redirect untuk analytics
        Log::info('Short URL redirected', [
            'short_code' => $shortCode,
            'puskesmas_id' => $link->puskesmas_id,
            'access_count' => $link->access_count + 1
        ]);

        return redirect($link->original_url);
    }

    /**
     * Get analytics untuk short URL tertentu
     */
    public function getAnalytics($shortCode)
    {
        $link = GeneratedLink::with('puskesmas')
            ->where('short_code', $shortCode)
            ->first();

        if (!$link) {
            return response()->json(['error' => 'Link not found'], 404);
        }

        $analytics = [
            'short_code' => $link->short_code,
            'original_url' => $link->original_url,
            'short_url' => $link->short_url,
            'puskesmas' => $link->puskesmas->name,
            'created_at' => $link->created_at,
            'expires_at' => $link->expires_at,
            'is_active' => $link->is_active,
            'access_count' => $link->access_count,
            'last_accessed_at' => $link->last_accessed_at,
            'created_by' => $link->created_by
        ];

        return response()->json($analytics);
    }

    /**
     * Bulk generate untuk multiple puskesmas
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'puskesmas_ids' => 'required|array',
            'puskesmas_ids.*' => 'exists:puskesmas,id',
            'expires_at' => 'nullable|date|after:today'
        ]);

        $results = [];
        $errors = [];

        foreach ($request->puskesmas_ids as $puskesmasId) {
            try {
                $timestamp = time();
                $randomString = Str::random(16);
                $token = sha1($puskesmasId . $timestamp . $randomString . config('app.key'));

                $url = route('pages.skrining-umum.index', [
                    'puskesmas_id' => $puskesmasId,
                    'token' => $token,
                    'ts' => $timestamp
                ]);

                $shortCode = $this->generateUniqueShortCode();
                $shortUrl = route('skrining.umum', $shortCode);

                $generatedLink = GeneratedLink::create([
                    'puskesmas_id' => $puskesmasId,
                    'original_url' => $url,
                    'short_url' => $shortUrl,
                    'short_code' => $shortCode,
                    'token' => $token,
                    'expires_at' => $request->expires_at,
                    'created_by' => auth()->id(),
                    'is_active' => true
                ]);

                $puskesmas = Puskesmas::find($puskesmasId);
                $results[] = [
                    'puskesmas_id' => $puskesmasId,
                    'puskesmas_name' => $puskesmas->name,
                    'short_url' => $shortUrl,
                    'short_code' => $shortCode,
                    'success' => true
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'puskesmas_id' => $puskesmasId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'results' => $results,
            'errors' => $errors,
            'total_success' => count($results),
            'total_errors' => count($errors)
        ]);
    }

    /**
     * Validate token untuk keamanan tambahan
     */
    private function validateToken($puskesmasId, $token, $timestamp)
    {
        // Check if timestamp is not too old (24 hours)
        if (time() - $timestamp > 86400) {
            return false;
        }

        // Check if link exists and active
        $link = GeneratedLink::where('puskesmas_id', $puskesmasId)
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$link) {
            return false;
        }

        // Check expiration
        if ($link->expires_at && $link->expires_at < now()) {
            return false;
        }

        return true;
    }

    /**
     * Show QR Code for specific link
     */
    public function showQRCode($id)
    {
        try {
            $link = GeneratedLink::with('puskesmas')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $link->qr_code,
                    'short_url' => $link->short_url,
                    'puskesmas_name' => $link->puskesmas->nama,
                    'created_at' => $link->created_at->format('d/m/Y'),
                    'expires_at' => $link->expires_at ? $link->expires_at->format('d/m/Y') : null,
                    'access_count' => $link->access_count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Delete link with QR Code
     */
    public function deleteLink($id)
    {
        try {
            $link = GeneratedLink::findOrFail($id);

            // Log sebelum delete untuk audit
            Log::info('Link deleted', [
                'short_code' => $link->short_code,
                'puskesmas_id' => $link->puskesmas_id,
                'deleted_by' => Auth::id()
            ]);

            // Delete akan otomatis menghapus qr_code karena ada di database
            $link->delete();

            return response()->json([
                'success' => true,
                'message' => 'Link dan QR Code berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting link: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus link'
            ], 500);
        }
    }
}
