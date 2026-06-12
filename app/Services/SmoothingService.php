<?php

namespace App\Services;

use App\Models\Pengamatan;

class SmoothingService
{
    // Berapa inferensi rendah berturut-turut sebelum status turun
    private const KONFIRMASI_TURUN = 2;

    // Status low confidence (evaluasi)
    private const STATUS_LOW_CONF = 3;

    /**
     * Hitung status peringatan resmi (0-2).
     * - NAIK: instan
     * - TURUN: setelah KONFIRMASI_TURUN kali berturut lebih rendah
     * - LOW CONF (3): pertahankan status lama
     */
    public function hitungStatusPeringatan(int $statusMentah): int
    {
        $terakhir = Pengamatan::whereNotNull('status_peringatan')
            ->latest('recorded_at')
            ->first();

        // Belum ada riwayat → set langsung (low conf dianggap aman)
        if (!$terakhir) {
            return $statusMentah === self::STATUS_LOW_CONF ? 0 : $statusMentah;
        }

        $peringatanLama = (int) $terakhir->status_peringatan;

        // LOW CONF → pertahankan
        if ($statusMentah === self::STATUS_LOW_CONF) {
            return $peringatanLama;
        }

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
            if ((int) $s === self::STATUS_LOW_CONF || (int) $s >= $peringatanLama) {
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