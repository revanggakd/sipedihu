<?php

namespace App\Console\Commands;

use App\Models\Pengamatan;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ValidasiPrediksi extends Command
{
    protected $signature = 'sipedih:validasi';
    protected $description = 'Hitung kelas aktual berdasarkan akumulasi hujan 1 jam ke depan';

    /**
     * Tentukan kelas dari akumulasi hujan (mm/jam) — 3 kelas
     * Batas: 1 / 5
     *   < 1   → kelas 0 (Tidak Hujan)
     *   1–<5  → kelas 1 (Hujan Ringan)
     *   ≥ 5   → kelas 2 (Hujan Sedang-Sangat Lebat)
     */
    private function tentukanKelas(float $mm): int
    {
        if ($mm < 1.0) return 0;
        if ($mm < 5.0) return 1;
        return 2;
    }

    public function handle()
    {
        $batasWaktu = now()->subHour();

        $belumValidasi = Pengamatan::where('sudah_validasi', false)
            ->where('recorded_at', '<=', $batasWaktu)
            ->orderBy('recorded_at', 'asc')
            ->get();

        if ($belumValidasi->isEmpty()) {
            $this->info('Tidak ada data yang perlu divalidasi.');
            return self::SUCCESS;
        }

        $jumlah = 0;

        foreach ($belumValidasi as $row) {
            $mulai   = Carbon::parse($row->recorded_at);
            $selesai = $mulai->copy()->addHour();

            $akumulasi = Pengamatan::where('recorded_at', '>=', $mulai)
                ->where('recorded_at', '<', $selesai)
                ->sum('rainfall');

            $kelasAktual = $this->tentukanKelas((float) $akumulasi);

            $row->rainfall_actual_1h = $akumulasi;
            $row->kelas_aktual       = $kelasAktual;
            $row->sudah_validasi     = true;
            $row->save();

            $jumlah++;
        }

        $this->info("Berhasil memvalidasi {$jumlah} data.");
        return self::SUCCESS;
    }
}