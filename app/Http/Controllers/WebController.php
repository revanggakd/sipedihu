<?php

namespace App\Http\Controllers;

use App\Models\Pengamatan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebController extends Controller
{
    public function dashboard()
    {
        $data = Pengamatan::latest('recorded_at')->first();

        $satuJamLalu = now()->subHour();
        $trendData = Pengamatan::where('recorded_at', '>=', $satuJamLalu)
            ->orderBy('recorded_at', 'asc')
            ->get(['recorded_at', 'temp', 'humidity', 'rainfall']);

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

        $lastUpdate = $data ? \Carbon\Carbon::parse($data->recorded_at)->format('d M Y, H:i:s') . ' WIB' : null;

        // Cek apakah data kedaluwarsa (lebih dari 25 menit)
        $AMBANG_KEDALUWARSA = 25; // menit
        $dataBasi = false;
        $menitBerlalu = null;
        if ($data) {
            $menitBerlalu = \Carbon\Carbon::parse($data->recorded_at)->diffInMinutes(now());
            $dataBasi = $menitBerlalu > $AMBANG_KEDALUWARSA;
        }

        return view('dashboard', compact(
            'data', 'trendData', 'mulaiBerlaku', 'lastUpdate', 'dataBasi', 'menitBerlalu'
        ));
    }

    public function riwayat()
    {
        $batasWaktu = now()->subHours(24);
        $riwayat = Pengamatan::where('recorded_at', '>=', $batasWaktu)
            ->orderBy('recorded_at', 'desc')
            ->paginate(20);

        $terbaru = Pengamatan::latest('recorded_at')->first();
        $lastUpdate = $terbaru ? \Carbon\Carbon::parse($terbaru->recorded_at)->format('d M Y, H:i:s') . ' WIB' : null;

        return view('riwayat', compact('riwayat', 'lastUpdate'));
    }

    public function unduh()
    {
        $terbaru = Pengamatan::latest('recorded_at')->first();
        $lastUpdate = $terbaru ? \Carbon\Carbon::parse($terbaru->recorded_at)->format('d M Y, H:i:s') . ' WIB' : null;

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
            ->orderBy('recorded_at', 'asc')
            ->get();

        $kelasLabel  = ['Tidak Hujan', 'Hujan Ringan', 'Hujan Sedang-Sangat Lebat'];
        $statusLabel = ['Aman', 'Waspada', 'Awas', 'Low Confidence'];

        $filename = 'sipedih_' . $dari . '_' . $sampai . '.csv';

        return new StreamedResponse(function () use ($data, $kelasLabel, $statusLabel) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tanggal', 'Waktu',
                'Suhu (C)', 'Kelembapan (%)', 'Tekanan (hPa)', 'Curah Hujan (mm)',
                'Prediksi', 'Status',
                'Prob Tidak Hujan', 'Prob Hujan Ringan', 'Prob Sedang-Sangat Lebat',
                'Curah Hujan Aktual 1 Jam (mm)', 'Kelas Aktual',
                'Tegangan (V)', 'Baterai (%)',
            ]);

            foreach ($data as $row) {
                $dt = \Carbon\Carbon::parse($row->recorded_at);
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
        ]);
    }
}