<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

function rp($angka, $prefix = true)
{
    if (is_null($angka) || $angka == '') {
        return '-';
    }

    if (strpos($angka, '-') !== false) {
        return $angka;
    }

    $hasil = number_format($angka, 2, ',', '.');
    return $prefix ? "Rp " . $hasil : $hasil;
}

function formatBulanTahun($input)
{
    // Pecah string menjadi tahun dan bulan
    [$tahun, $bulan] = explode('-', $input);

    // Array nama bulan dalam Bahasa Indonesia
    $namaBulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];

    // Kembalikan hasil dalam format "Mei 2025"
    return ($namaBulan[$bulan] ?? 'Bulan tidak valid') . ' ' . $tahun;
}

function formatTanggal($input)
{
    if(!isset($input)) return;
    // Pecah string menjadi tahun, bulan, dan tanggal
    [$tahun, $bulan, $tanggal] = explode('-', $input);

    // Array nama bulan dalam Bahasa Indonesia
    $namaBulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];

    // Kembalikan hasil dalam format "15 Mei 2025"
    return $tanggal . ' ' . ($namaBulan[$bulan] ?? 'Bulan tidak valid') . ' ' . $tahun;
}

function hitungHPL($hpht)
{
    $hpht = Carbon::parse($hpht);
    $hpl = $hpht->addMonths(9)->addDays(7); // 9 bulan + 7 hari
    return $hpl->format('Y-m-d');
}

// Menghitung Usia Kehamilan
function hitungUsiaKehamilanString($hpht)
{
    $hpht = Carbon::parse($hpht);  // Parsing tanggal yang benar
    $hariKehamilan = $hpht->diffInDays(Carbon::now()); // Selisih dalam hari
    $usiaKehamilanMinggu = $hariKehamilan / 7;  // Menghitung usia kehamilan dalam minggu, dengan desimal
    
    // Menghitung minggu dan hari
    $minggu = floor($usiaKehamilanMinggu); // Minggu penuh
    $hari = round(($usiaKehamilanMinggu - $minggu) * 7); // Sisa hari

    return "{$minggu} minggu {$hari} hari"; // Format hasil
}

function hitungUsiaKehamilanMinggu($hpht)
{
    $hpht = Carbon::parse($hpht);  // Parsing tanggal HPHT
    $hariKehamilan = $hpht->diffInDays(Carbon::now()); // Selisih dalam hari
    return $hariKehamilan / 7;  // Menghitung usia kehamilan dalam minggu
}

function tentukanTrimester($usiaKehamilan)
{
    if ($usiaKehamilan >= 0 && $usiaKehamilan < 13) {
        return 'trimester_1';
    } elseif ($usiaKehamilan >= 13 && $usiaKehamilan < 26) {
        return 'trimester_2';
    } elseif ($usiaKehamilan >= 26 && $usiaKehamilan < 40) {
        return 'trimester_3';
    } else {
        return 'pasca_hamil';
    }
}
