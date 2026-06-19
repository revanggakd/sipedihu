<?php

namespace App\Console\Commands;

use App\Models\Pengamatan;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CekKoneksi extends Command
{
    protected $signature = 'sipedih:cek-koneksi';
    protected $description = 'Cek apakah data dari node terputus, kirim notif Telegram';

    // Ambang batas data dianggap terputus (menit)
    private const AMBANG_PUTUS = 25;

    public function handle()
    {
        $data = Pengamatan::latest('recorded_at')->first();

        if (!$data) {
            $this->info('Belum ada data sama sekali.');
            return self::SUCCESS;
        }

        $menitTerakhir = Carbon::parse($data->recorded_at, 'UTC')->diffInMinutes(now());
        $sedangPutus   = Cache::get('koneksi_putus', false);

        // ── DATA TERPUTUS ──
        if ($menitTerakhir > self::AMBANG_PUTUS) {
            if (!$sedangPutus) {
                // Baru terputus → kirim notif sekali
                $telegram = new TelegramService();
                $waktuTerakhir = Carbon::parse($data->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i');
                $telegram->kirim(
                    $telegram->pesanDataPutus((int) $menitTerakhir, $waktuTerakhir)
                );

                Cache::put('koneksi_putus', true, now()->addDays(7));
                $this->info("Notif DATA TERPUTUS terkirim ({$menitTerakhir} menit).");
            } else {
                $this->info("Masih terputus ({$menitTerakhir} menit), notif sudah dikirim sebelumnya.");
            }
            return self::SUCCESS;
        }

        // ── DATA NORMAL ──
        if ($sedangPutus) {
            // Sebelumnya putus, sekarang normal → kirim notif pemulihan
            $telegram = new TelegramService();
            $waktuKembali = Carbon::parse($data->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i');
            $telegram->kirim(
                $telegram->pesanDataNormal($waktuKembali)
            );

            Cache::forget('koneksi_putus');
            $this->info('Notif DATA NORMAL KEMBALI terkirim.');
        } else {
            $this->info("Data normal ({$menitTerakhir} menit lalu).");
        }

        return self::SUCCESS;
    }
}