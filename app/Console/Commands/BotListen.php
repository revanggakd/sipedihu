<?php

namespace App\Console\Commands;

use App\Models\Pengamatan;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BotListen extends Command
{
    protected $signature = 'sipedih:bot-listen';
    protected $description = 'Cek pesan masuk ke bot Telegram dan balas command /status';

    private array $statusMap = [
        0 => ['label' => 'AMAN',    'emoji' => '🟢'],
        1 => ['label' => 'WASPADA', 'emoji' => '🟡'],
        2 => ['label' => 'AWAS',    'emoji' => '🔴'],
    ];

    private array $kelasLabel = [
        'Tidak Hujan',
        'Hujan Ringan',
        'Hujan Sedang-Sangat Lebat',
    ];

    public function handle()
    {
        $telegram = new TelegramService();

        // Ambil offset terakhir dari cache
        $offset = (int) Cache::get('telegram_offset', 0);

        $updates = $telegram->getUpdates($offset);

        if (empty($updates)) {
            $this->info('Tidak ada pesan baru.');
            return self::SUCCESS;
        }

        foreach ($updates as $update) {
            $updateId = $update['update_id'] ?? 0;

            // Update offset supaya tidak diproses ulang
            Cache::put('telegram_offset', $updateId + 1, now()->addDays(7));

            $message = $update['message'] ?? null;
            if (!$message) continue;

            $text   = trim($message['text'] ?? '');
            $chatId = (string) ($message['chat']['id'] ?? '');

            if ($chatId === '') continue;

            // Proses command
            if ($text === '/status') {
                $telegram->kirimKe($chatId, $this->pesanStatusTerkini());
                $this->info("Balas /status ke {$chatId}");
            } elseif ($text === '/start') {
                $telegram->kirimKe($chatId, $this->pesanStart());
                $this->info("Balas /start ke {$chatId}");
            } elseif ($text === '/help') {
                $telegram->kirimKe($chatId, $this->pesanHelp());
                $this->info("Balas /help ke {$chatId}");
            }
        }

        return self::SUCCESS;
    }

    private function pesanStatusTerkini(): string
    {
        $data = Pengamatan::latest('recorded_at')->first();

        if (!$data) {
            return "Belum ada data pengamatan.";
        }

        $menitTerakhir = Carbon::parse($data->recorded_at, 'UTC')->diffInMinutes(now());

        // Cek data basi
        if ($menitTerakhir > 25) {
            return "⚠️ <b>DATA TERPUTUS</b>\n\nSistem tidak menerima data sejak {$menitTerakhir} menit lalu.\nData terakhir: " . Carbon::parse($data->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . " WIB";
        }

        $sp = (int) ($data->status_peringatan ?? 0);
        $sm = $this->statusMap[$sp] ?? $this->statusMap[0];

        $probs = [
            'Tidak Hujan'               => $data->prob_no_rain,
            'Hujan Ringan'              => $data->prob_light_rain,
            'Hujan Sedang-Sangat Lebat' => $data->prob_medium_rain,
        ];
        arsort($probs);
        $prediksi  = array_key_first($probs);
        $keyakinan = round(reset($probs) * 100);

        $waktu = Carbon::parse($data->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d M Y, H:i');

        $baris = [];
        $baris[] = "🌧️ <b>STATUS TERKINI SIPEDIH</b>";
        $baris[] = "";
        $baris[] = "{$sm['emoji']} Status : <b>{$sm['label']}</b>";
        $baris[] = "Prediksi : <b>{$prediksi}</b>";
        $baris[] = "🎯 Keyakinan : {$keyakinan}%";
        $baris[] = "";
        $baris[] = "<b>Kondisi Saat Ini:</b>";
        $baris[] = "🌡️ Suhu : {$data->temp} °C";
        $baris[] = "💧 RH : {$data->humidity} %";
        $baris[] = "🎚️ Tekanan : {$data->pressure} hPa";
        $baris[] = "🌧️ Curah Hujan : {$data->rainfall} mm";
        $baris[] = "";
        $baris[] = "🕐 Update : {$waktu} WIB";

        return implode("\n", $baris);
    }

    private function pesanStart(): string
    {
        return implode("\n", [
            "🌧️ <b>SIPEDIH</b>",
            "Sistem Peringatan Dini Hujan",
            "",
            "Bot ini mengirim peringatan dini hujan otomatis berdasarkan prediksi 1 jam ke depan.",
            "",
            "Ketik /status untuk cek kondisi terkini.",
            "Ketik /help untuk bantuan.",
        ]);
    }

    private function pesanHelp(): string
    {
        return implode("\n", [
            "<b>Bantuan SIPEDIH Bot</b>",
            "",
            "/status — Cek kondisi & status peringatan terkini",
            "/start — Info sistem",
            "/help — Tampilkan bantuan ini",
            "",
            "Bot akan otomatis mengirim peringatan saat status berubah.",
        ]);
    }
}