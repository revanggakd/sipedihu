<?php

namespace App\Console\Commands;

use App\Models\Pengamatan;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class KirimPengingat extends Command
{
    protected $signature = 'sipedih:pengingat';
    protected $description = 'Kirim pengingat Telegram berkala saat status Waspada/Awas masih aktif';

    private array $kelasLabel = [
        'Tidak Hujan',
        'Hujan Ringan',
        'Hujan Sedang-Sangat Lebat',
    ];

    private array $statusLabel = [
        'Aman',
        'Waspada',
        'Awas',
        'Evaluasi',
    ];

    // Interval pengingat per status (menit)
    private const INTERVAL_WASPADA = 60; // status 1: 1 jam
    private const INTERVAL_AWAS    = 30; // status 2: 30 menit

    public function handle()
    {
        // Ambil data terakhir
        $data = Pengamatan::latest('recorded_at')->first();

        if (!$data || $data->status_peringatan === null) {
            $this->info('Tidak ada data / status.');
            return self::SUCCESS;
        }

        $status = (int) $data->status_peringatan;

        // Hanya kirim pengingat untuk Waspada (1) & Awas (2)
        if ($status < 1) {
            $this->info('Status Aman, tidak ada pengingat.');
            return self::SUCCESS;
        }

        // Interval sesuai status
        $intervalMenit = $status === 2 ? self::INTERVAL_AWAS : self::INTERVAL_WASPADA;

        // Cek kapan pengingat terakhir dikirim
        $terakhir = Cache::get('pengingat_terakhir');

        if ($terakhir) {
            $menitBerlalu = Carbon::parse($terakhir)->diffInMinutes(now());
            if ($menitBerlalu < $intervalMenit) {
                $this->info("Belum waktunya ({$menitBerlalu}/{$intervalMenit} menit).");
                return self::SUCCESS;
            }
        }

        // Kirim pengingat
        $telegram = new TelegramService();
        $pesan = $telegram->pesanStatus(
            $data,
            $this->kelasLabel[$data->pred_class] ?? '-',
            $this->statusLabel[$status] ?? '-',
            true // mode pengingat
        );
        $telegram->kirim($pesan);

        // Update waktu pengingat terakhir
        Cache::put('pengingat_terakhir', now()->toDateTimeString(), now()->addDay());

        $this->info("Pengingat {$this->statusLabel[$status]} terkirim.");
        return self::SUCCESS;
    }
}