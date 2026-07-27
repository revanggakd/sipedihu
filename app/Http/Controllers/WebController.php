<?php

namespace App\Http\Controllers;

use App\Models\Pengamatan;
use Illuminate\Http\Request;

/*
============================================================
  WebController — SIPEDIHU (3 kelas)

  PERUBAHAN:
   1. TIMEZONE: recorded_at disimpan UTC. Tampilan & CSV
      dikonversi ke WIB via helper wib().
      SYARAT: config/app.php 'timezone' => 'UTC'.
   2. CSV export: pakai php://temp (bukan StreamedResponse)
      agar Content-Length bisa dikirim → fix Chrome/Edge.
   3. Query export: input tanggal WIB dikonversi ke UTC
      sebelum whereBetween agar data tidak terpotong 7 jam.
   4. DATA UJI (is_test): dikecualikan dari export;
      disembunyikan di riwayat (kecuali ?tampilkan_uji=1);
      tetap tampil di dashboard.
============================================================
*/
class WebController extends Controller
{
    /**
     * Konversi nilai waktu tersimpan (UTC) → Carbon WIB untuk tampilan.
     */
    private function wib($dt): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($dt, 'UTC')->setTimezone('Asia/Jakarta');
    }

    // =========================================================
    //  DASHBOARD
    // =========================================================
    public function dashboard()
    {
        // Dashboard menampilkan data terbaru apa adanya (termasuk data uji
        // agar demo terlihat). Data uji akan tergeser oleh data nyata berikutnya.
        $data = Pengamatan::latest('recorded_at')->first();

        $satuJamLalu = now()->subHour();
        $trendRows   = Pengamatan::where('recorded_at', '>=', $satuJamLalu)
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
            $statusSekarang      = $data->status_peringatan;
            $perubahanTerakhir   = Pengamatan::where('recorded_at', '<', $data->recorded_at)
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

        $lastUpdate = $data
            ? $this->wib($data->recorded_at)->format('d M Y, H:i:s') . ' WIB'
            : null;

        // Cek apakah data kedaluwarsa (lebih dari 25 menit)
        $AMBANG_KEDALUWARSA = 25; // menit
        $dataBasi           = false;
        $menitBerlalu       = null;
        if ($data) {
            $menitBerlalu = \Carbon\Carbon::parse($data->recorded_at, 'UTC')
                ->diffInMinutes(now());
            $dataBasi = $menitBerlalu > $AMBANG_KEDALUWARSA;
        }

        return view('dashboard', compact(
            'data', 'trendData', 'mulaiBerlaku', 'lastUpdate', 'dataBasi', 'menitBerlalu'
        ));
    }

    // =========================================================
    //  RIWAYAT
    // =========================================================
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

        $terbaru    = Pengamatan::latest('recorded_at')->first();
        $lastUpdate = $terbaru
            ? $this->wib($terbaru->recorded_at)->format('d M Y, H:i:s') . ' WIB'
            : null;

        return view('riwayat', compact('riwayat', 'lastUpdate', 'tampilkanUji'));
    }

    // =========================================================
    //  HALAMAN UNDUH
    // =========================================================
    public function unduh()
    {
        $terbaru    = Pengamatan::latest('recorded_at')->first();
        $lastUpdate = $terbaru
            ? $this->wib($terbaru->recorded_at)->format('d M Y, H:i:s') . ' WIB'
            : null;

        return view('unduh', compact('lastUpdate'));
    }

    // =========================================================
    //  EXPORT CSV
    // =========================================================
    public function export(Request $request)
    {
        // --- Validasi input ---
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

        // --- Query: konversi input WIB → UTC sebelum whereBetween ---
        // Input dari view adalah tanggal lokal (WIB). Kalau langsung
        // digabung string '00:00:00' / '23:59:59' tanpa timezone,
        // Laravel menganggapnya UTC dan data 7 jam pertama/terakhir terpotong.
        $utcMulai   = \Carbon\Carbon::createFromFormat(
                          'Y-m-d H:i:s',
                          $dari . ' 00:00:00',
                          'Asia/Jakarta'
                      )->utc();

        $utcSelesai = \Carbon\Carbon::createFromFormat(
                          'Y-m-d H:i:s',
                          $sampai . ' 23:59:59',
                          'Asia/Jakarta'
                      )->utc();

        $data = Pengamatan::whereBetween('recorded_at', [$utcMulai, $utcSelesai])
            ->where('is_test', false)   // kecualikan data uji/demo dari analisis
            ->orderBy('recorded_at', 'asc')
            ->get();

        // --- Build CSV di memori (php://temp) ---
        // Menggunakan php://temp + response() biasa (bukan StreamedResponse)
        // agar Content-Length bisa dihitung dan dikirim → fix Chrome/Edge.
        $kelasLabel  = ['Tidak Hujan', 'Hujan Ringan', 'Hujan Sedang-Sangat Lebat'];
        $statusLabel = ['Aman', 'Waspada', 'Awas'];

        $buffer = fopen('php://temp', 'r+');

        fputcsv($buffer, [
            'Tanggal', 'Waktu (WIB)',
            'Suhu (C)', 'Kelembapan (%)', 'Tekanan (hPa)', 'Curah Hujan (mm)',
            'Prediksi', 'Status',
            'Prob Tidak Hujan', 'Prob Hujan Ringan', 'Prob Sedang-Sangat Lebat',
            'Curah Hujan Aktual 1 Jam (mm)', 'Kelas Aktual',
            'Tegangan (V)', 'Baterai (%)',
        ]);

        foreach ($data as $row) {
            $dt = $this->wib($row->recorded_at);
            fputcsv($buffer, [
                $dt->format('d/m/Y'),
                $dt->format('H:i:s'),
                $row->temp,
                $row->humidity,
                $row->pressure,
                $row->rainfall,
                $kelasLabel[$row->pred_class]  ?? '-',
                $statusLabel[$row->status]     ?? '-',
                round($row->prob_no_rain     * 100) . '%',
                round($row->prob_light_rain  * 100) . '%',
                round($row->prob_medium_rain * 100) . '%',
                $row->rainfall_actual_1h,
                $row->kelas_aktual !== null ? ($kelasLabel[$row->kelas_aktual] ?? '-') : '-',
                $row->battery_voltage,
                $row->battery_percent,
            ]);
        }

        rewind($buffer);
        $csvContent = stream_get_contents($buffer);
        fclose($buffer);

        $filename = 'sipedihu_' . $dari . '_' . $sampai . '.csv';

        return response($csvContent, 200, [
            'Content-Type'           => 'text/csv; charset=UTF-8',
            'Content-Disposition'    => 'attachment; filename="' . $filename . '"',
            'Content-Length'         => strlen($csvContent),
            'Cache-Control'          => 'no-cache, no-store, must-revalidate',
            'Pragma'                 => 'no-cache',
            'Expires'                => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}