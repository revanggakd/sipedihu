<?php

namespace App\Http\Controllers;

use App\Models\Pengamatan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
============================================================
  WebController — SIPEDIH (3 kelas)

  PERUBAHAN:
   1. TIMEZONE: recorded_at disimpan UTC (konsisten data latih +00
      & firmware). Tampilan dikonversi ke WIB via helper wib().
      SYARAT: config/app.php 'timezone' => 'UTC'.
   2. CSV export: waktu WIB; label 'Low Confidence' dibuang.
   3. trendData: label waktu diformat WIB di server.
   4. DATA UJI (is_test): dikecualikan dari export; disembunyikan di
      riwayat (kecuali ?tampilkan_uji=1); tetap tampil di dashboard.
============================================================
*/
class WebController extends Controller
{
    // Konversi nilai waktu tersimpan (UTC) -> Carbon WIB untuk TAMPILAN.
    private function wib($dt): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($dt, 'UTC')->setTimezone('Asia/Jakarta');
    }

    public function dashboard()
    {
        // Dashboard menampilkan data terbaru apa adanya (termasuk data uji,
        // agar demo terlihat). Data uji akan tergeser oleh data nyata berikutnya.
        $data = Pengamatan::latest('recorded_at')->first();

        $satuJamLalu = now()->subHour();
        $trendRows = Pengamatan::where('recorded_at', '>=', $satuJamLalu)
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'temp', 'humidity', 'rainfall']);

        $trendData = $trendRows->map(function ($r) {
            return [
                'waktu'    => $this->wib($r->recorded_at)->format('H:i'),
                'temp'     => $r->temp,
                'humidity' => $r->humidity,
                'rainfall' => $r->rainfall,
            ];
        });

        $mulaiBerlaku = null;
        if ($data && $data->status_peringatan !== null) {
            $statusSekarang = $data->status_peringatan;
            $perubahanTerakhir = Pengamatan::where('recorded_at', '<', $data->recorded_at)
                ->where('status_peringatan', '!=', $statusSekarang)
                ->latest('recorded_at')
                ->first();

            if ($perubahanTerakhir) {
                $mulaiBerlaku = Pengamatan::where('recorded_at', '>', $perubahanTerakhir->recorded_at)
                    ->where('status_peringatan', $statusSekarang)
                    ->orderBy('recorded_at', 'asc')
                    ->first()?->recorded_at;
            } else {
                $mulaiBerlaku = Pengamatan::where('status_peringatan', $statusSekarang)
                    ->orderBy('recorded_at', 'asc')
                    ->first()?->recorded_at;
            }
        }

        $lastUpdate = $data ? $this->wib($data->recorded_at)->format('d M Y, H:i:s') . ' WIB' : null;

        // Cek apakah data kedaluwarsa (lebih dari 25 menit)
        $AMBANG_KEDALUWARSA = 25; // menit
        $dataBasi = false;
        $menitBerlalu = null;
        if ($data) {
            $menitBerlalu = \Carbon\Carbon::parse($data->recorded_at, 'UTC')->diffInMinutes(now());
            $dataBasi = $menitBerlalu > $AMBANG_KEDALUWARSA;
        }

        return view('dashboard', compact(
            'data', 'trendData', 'mulaiBerlaku', 'lastUpdate', 'dataBasi', 'menitBerlalu'
        ));
    }

    public function riwayat(Request $request)
    {
        $batasWaktu = now()->subHours(24);

        // Default sembunyikan data uji; ?tampilkan_uji=1 untuk menampilkan
        $tampilkanUji = $request->boolean('tampilkan_uji');

        $query = Pengamatan::where('recorded_at', '>=', $batasWaktu)
            ->orderBy('recorded_at', 'desc');
        if (!$tampilkanUji) {
            $query->where('is_test', false);
        }
        $riwayat = $query->paginate(20)->withQueryString();

        $terbaru = Pengamatan::latest('recorded_at')->first();
        $lastUpdate = $terbaru ? $this->wib($terbaru->recorded_at)->format('d M Y, H:i:s') . ' WIB' : null;

        return view('riwayat', compact('riwayat', 'lastUpdate', 'tampilkanUji'));
    }

    public function unduh()
    {
        $terbaru = Pengamatan::latest('recorded_at')->first();
        $lastUpdate = $terbaru ? $this->wib($terbaru->recorded_at)->format('d M Y, H:i:s') . ' WIB' : null;

        return view('unduh', compact('lastUpdate'));
    }

    public function export(Request $request)
    {
        $dari   = $request->input('dari');
        $sampai = $request->input('sampai');

        if (!$dari || !$sampai) {
            abort(400, 'Tanggal mulai dan selesai harus diisi.');
        }

        try {
            $tglMulai   = \Carbon\Carbon::parse($dari);
            $tglSelesai = \Carbon\Carbon::parse($sampai);
        } catch (\Exception $e) {
            abort(400, 'Format tanggal tidak valid.');
        }

        if ($tglMulai->gt($tglSelesai)) {
            abort(400, 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
        }

        if ($tglMulai->diffInDays($tglSelesai) > 31) {
            abort(400, 'Rentang melebihi 31 hari.');
        }

        $data = Pengamatan::whereBetween('recorded_at', [
                $dari . ' 00:00:00',
                $sampai . ' 23:59:59',
            ])
            ->where('is_test', false)   // kecualikan data uji (injeksi/demo) dari analisis
            ->orderBy('recorded_at', 'asc')
            ->get();

        $kelasLabel  = ['Tidak Hujan', 'Hujan Ringan', 'Hujan Sedang-Sangat Lebat'];
        $statusLabel = ['Aman', 'Waspada', 'Awas'];  // 3 status

        $filename = 'sipedih_' . $dari . '_' . $sampai . '.csv';
        $wib = fn ($dt) => $this->wib($dt);

        return new StreamedResponse(function () use ($data, $kelasLabel, $statusLabel, $wib) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tanggal', 'Waktu (WIB)',
                'Suhu (C)', 'Kelembapan (%)', 'Tekanan (hPa)', 'Curah Hujan (mm)',
                'Prediksi', 'Status',
                'Prob Tidak Hujan', 'Prob Hujan Ringan', 'Prob Sedang-Sangat Lebat',
                'Curah Hujan Aktual 1 Jam (mm)', 'Kelas Aktual',
                'Tegangan (V)', 'Baterai (%)',
            ]);

            foreach ($data as $row) {
                $dt = $wib($row->recorded_at);  // WIB untuk CSV
                fputcsv($handle, [
                    $dt->format('d/m/Y'),
                    $dt->format('H:i:s'),
                    $row->temp,
                    $row->humidity,
                    $row->pressure,
                    $row->rainfall,
                    $kelasLabel[$row->pred_class] ?? '-',
                    $statusLabel[$row->status] ?? '-',
                    round($row->prob_no_rain * 100) . '%',
                    round($row->prob_light_rain * 100) . '%',
                    round($row->prob_medium_rain * 100) . '%',
                    $row->rainfall_actual_1h,
                    $row->kelas_aktual !== null ? ($kelasLabel[$row->kelas_aktual] ?? '-') : '-',
                    $row->battery_voltage,
                    $row->battery_percent,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
