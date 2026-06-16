<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengamatan;
use App\Services\TelegramService;
use App\Services\SmoothingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        'Low Confidence',
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
            'status'           => 'required|integer|min:0|max:3',
            'battery_voltage'  => 'nullable|numeric',
            'battery_percent'  => 'nullable|numeric',
        ]);

        $smoothing = new SmoothingService();
        $statusPeringatanBaru = $smoothing->hitungStatusPeringatan((int) $validated['status']);

        $peringatanLama = Pengamatan::whereNotNull('status_peringatan')
            ->latest('recorded_at')
            ->first()?->status_peringatan;

        $validated['status_peringatan'] = $statusPeringatanBaru;
        $data = Pengamatan::create($validated);

        // Telegram jika status peringatan berubah
        if ($peringatanLama !== $statusPeringatanBaru) {
            $telegram = new TelegramService();
            $pesan = $telegram->pesanStatus(
                $data,
                $this->kelasLabel[$data->pred_class],
                $this->statusLabel[$statusPeringatanBaru],
            );
            $telegram->kirim($pesan);

            // Reset timer pengingat — mulai hitung dari sekarang
            Cache::put('pengingat_terakhir', now()->toDateTimeString(), now()->addDay());
        }

        return response()->json([
            'message'           => 'Data berhasil disimpan',
            'status_mentah'     => $data->status,
            'status_peringatan' => $statusPeringatanBaru,
            'data'              => $data,
        ], 201);
    }

    public function latest()
    {
        $data = Pengamatan::latest('recorded_at')->first();
        return response()->json(['data' => $data]);
    }
}