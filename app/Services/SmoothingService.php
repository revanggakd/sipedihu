<?php

namespace App\Services;

use App\Models\Pengamatan;

class SmoothingService
{
    private const KONFIRMASI_TURUN = 2;

    /**
     * Hitung status peringatan resmi (0-2).
     * - NAIK: instan
     * - TURUN: setelah KONFIRMASI_TURUN kali berturut lebih rendah
     */
    public function hitungStatusPeringatan(int $statusMentah): int
    {
        $terakhir = Pengamatan::whereNotNull('status_peringatan')
            ->latest('recorded_at')
            ->first();

        if (!$terakhir) {
            return $statusMentah;
        }

        $peringatanLama = (int) $terakhir->status_peringatan;

        // NAIK → instan
        if ($statusMentah > $peringatanLama) {
            return $statusMentah;
        }

        // SAMA → tetap
        if ($statusMentah === $peringatanLama) {
            return $peringatanLama;
        }

        // TURUN → perlu konfirmasi
        $sebelumnya = Pengamatan::latest('recorded_at')
            ->limit(self::KONFIRMASI_TURUN - 1)
            ->pluck('status')
            ->toArray();

        $semuaRendah = true;
        foreach ($sebelumnya as $s) {
            if ((int) $s >= $peringatanLama) {
                $semuaRendah = false;
                break;
            }
        }

        if ($semuaRendah && count($sebelumnya) >= (self::KONFIRMASI_TURUN - 1)) {
            return $statusMentah;
        }

        return $peringatanLama;
    }
}