<?php

namespace App\Support;

use Carbon\Carbon;

class Kehamilan
{
    /**
     * Hitung usia kehamilan (minggu) dari HPHT sampai hari ini (dibatasi 0..42).
     */
    public static function hitungUsiaMinggu(string $hphtDate): int
    {
        $hpht = Carbon::parse($hphtDate)->startOfDay();
        $now  = Carbon::now()->startOfDay();
        $weeks = (int) floor($hpht->diffInDays($now) / 7);
        return max(0, min($weeks, 42));
    }

    /**
     * Keterangan usia: "X minggu Y hari" dari HPHT.
     */
    public static function hitungUsiaString(string $hphtDate): string
    {
        $hpht = Carbon::parse($hphtDate)->startOfDay();
        $now  = Carbon::now()->startOfDay();
        $days = $hpht->diffInDays($now);
        $weeks = intdiv($days, 7);
        $rdays = $days % 7;
        return sprintf('%d minggu %d hari', $weeks, $rdays);
    }

    /**
     * Tentukan trimester berdasarkan usia minggu.
     * Jika sudah melewati HPL, kembalikan 'pasca_hamil'.
     */
    public static function tentukanTrimester(int $usiaMinggu, ?string $hplDate = null): string
    {
        if ($hplDate && Carbon::now()->greaterThan(Carbon::parse($hplDate))) {
            return 'pasca_hamil';
        }
        if ($usiaMinggu < 14)   return 'trimester_1';
        if ($usiaMinggu < 28)   return 'trimester_2';
        return 'trimester_3';
    }
}
