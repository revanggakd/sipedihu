<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengamatan;
use App\Services\TelegramService;
use App\Services\SmoothingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/*
============================================================
  PengamatanController (API) — SIPEDIH 3 kelas

   - is_test: data injeksi/demo (disimpan, difilter di tampilan/export).
   - is_warmup: model TX belum siap. Paksa status_peringatan = 0,
     LEWATI smoothing & Telegram. TIDAK disimpan ke kolom DB
     (dideteksi di tampilan via prob serba-nol).
============================================================
*/
class PengamatanController extends Controller
{
    private array $kelasLabel = [
        'Tidak Hujan',
        'Hujan Ringan',
        'Hujan Sedang-Sangat Lebat',
    ];

    private array $statusLabel = [
        'Aman',
        'Waspada',
        'Awas',
    ];

    public function store(Request $request)
    {
        if ($request->header('X-API-Key') !== env('ESP32_API_KEY')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'recorded_at'      => 'required|date',
            'temp'             => 'required|numeric',
            'humidity'         => 'required|numeric',
            'pressure'         => 'required|numeric',
            'rainfall'         => 'required|numeric',
            'prob_no_rain'     => 'required|numeric|min:0|max:1',
            'prob_light_rain'  => 'required|numeric|min:0|max:1',
            'prob_medium_rain' => 'required|numeric|min:0|max:1',
            'pred_class'       => 'required|integer|min:0|max:2',
            'status'           => 'required|integer|min:0|max:2',
            'battery_voltage'  => 'nullable|numeric',
            'battery_percent'  => 'nullable|numeric',
            'is_test'          => 'nullable|boolean',
            'is_warmup'        => 'nullable|boolean',
        ]);

        // Dibaca terpisah; TIDAK disimpan ke kolom DB (kolom tidak ada).
        $isWarmup = $request->boolean('is_warmup');

        $peringatanLama = Pengamatan::whereNotNull('status_peringatan')
            ->latest('recorded_at')
            ->first()?->status_peringatan;

        if ($isWarmup) {
            // Model TX belum siap: prediksi serba-nol BUKAN "Aman" yang sah.
            // Paksa 0 (memutus hysteresis yang bisa menahan alert lama),
            // dan lewati smoothing sepenuhnya.
            $statusPeringatanBaru = 0;
        } else {
            $smoothing = new SmoothingService();
            $statusPeringatanBaru = $smoothing->hitungStatusPeringatan((int) $validated['status']);
        }

        $validated['status_peringatan'] = $statusPeringatanBaru;
        $validated['is_test'] = (bool) ($validated['is_test'] ?? false);

        // Jangan simpan is_warmup ke kolom DB (kolom sudah dihapus).
        unset($validated['is_warmup']);

        $data = Pengamatan::create($validated);

        // Telegram jika status peringatan berubah.
        // TIDAK dikirim saat warmup (hindari notifikasi "turun ke Aman"
        // yang dipicu pembersihan alarm hantu, bukan perubahan cuaca nyata).
        if (!$isWarmup && $peringatanLama !== $statusPeringatanBaru) {
            $telegram = new TelegramService();
            $pesan = $telegram->pesanStatus(
                $data,
                $this->kelasLabel[$data->pred_class],
                $this->statusLabel[$statusPeringatanBaru],
            );
            $telegram->kirim($pesan);

            Cache::put('pengingat_terakhir', now()->toDateTimeString(), now()->addDay());
        }

        return response()->json([
            'message'           => 'Data berhasil disimpan',
            'status_mentah'     => $data->status,
            'status_peringatan' => $statusPeringatanBaru,
            'is_test'           => $data->is_test,
            'is_warmup'         => $isWarmup,
            'data'              => $data,
        ], 201);
    }

    public function latest()
    {
        $data = Pengamatan::latest('recorded_at')->first();
        return response()->json(['data' => $data]);
    }
}