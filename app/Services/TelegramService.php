<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $chatId;

    public function __construct()
    {
        $this->token  = config('services.telegram.token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function kirim(string $pesan): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $pesan,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram gagal: ' . $e->getMessage());
        }
    }

    /**
     * Susun pesan peringatan berdasarkan status peringatan resmi (0-2)
     *
     * @param bool $pengingat true = pesan pengingat berkala (status masih aktif)
     */
    public function pesanStatus(object $data, string $labelKelas, string $labelStatus, bool $pengingat = false): string
    {
        $status = (int) $data->status_peringatan;

        $statusConfig = [
            0 => ['emoji' => '🟢', 'label' => 'AMAN',    'aksi' => 'Tidak ada tindakan khusus diperlukan.'],
            1 => ['emoji' => '🟡', 'label' => 'WASPADA', 'aksi' => 'Pantau perkembangan cuaca dan tetap waspada.'],
            2 => ['emoji' => '🔴', 'label' => 'AWAS',    'aksi' => 'Segera lakukan tindakan keselamatan.'],
        ];
        $sc = $statusConfig[$status] ?? $statusConfig[0];

        $probs = [
            'TIDAK HUJAN'                => $data->prob_no_rain,
            'HUJAN RINGAN'               => $data->prob_light_rain,
            'HUJAN SEDANG–SANGAT LEBAT'  => $data->prob_medium_rain,
        ];
        arsort($probs);
        $prediksi  = array_key_first($probs);
        $keyakinan = round(reset($probs) * 100);

        $mulai   = \Carbon\Carbon::parse($data->recorded_at);
        $selesai = $mulai->copy()->addHour();

        $bulan = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
        ];
        $tglUpdate = $mulai->day . ' ' . $bulan[$mulai->month] . ' ' . $mulai->year
                   . ' | ' . $mulai->format('H.i') . ' WIB';

        // Judul: pengingat atau peringatan biasa
        $judul = $pengingat
            ? "🔔 <b>PENGINGAT — PERINGATAN MASIH AKTIF</b>"
            : "🌧️ <b>SISTEM PERINGATAN DINI HUJAN</b>";

        $baris = [];
        $baris[] = $judul;
        $baris[] = "";
        $baris[] = "{$sc['emoji']} Status : <b>{$sc['label']}</b>";
        $baris[] = "Prediksi : <b>{$prediksi}</b>";
        $baris[] = "🎯 Keyakinan : {$keyakinan} %";
        $baris[] = "🕐 Masa Berlaku : {$mulai->format('H.i')} – {$selesai->format('H.i')} WIB";
        $baris[] = "";
        $baris[] = "ℹ️ {$sc['aksi']}";
        $baris[] = "";
        $baris[] = "<b>Kondisi Saat Ini:</b>";
        $baris[] = "🌡️ Suhu : {$data->temp} °C";
        $baris[] = "💧 RH : {$data->humidity} %";
        $baris[] = "🎚️ Tekanan : {$data->pressure} hPa";
        $baris[] = "🌧️ Curah Hujan : {$data->rainfall} mm";
        $baris[] = "";
        $baris[] = "🕐 Update : {$tglUpdate}";

        return implode("\n", $baris);
    }

    /**
     * Pesan saat data terputus (tidak ada data masuk)
     */
    public function pesanDataPutus(int $menitTerakhir, ?string $waktuTerakhir): string
    {
        $baris = [];
        $baris[] = "⚠️ <b>SISTEM PERINGATAN DINI HUJAN</b>";
        $baris[] = "";
        $baris[] = "🔌 <b>DATA TERPUTUS</b>";
        $baris[] = "";
        $baris[] = "Sistem tidak menerima data dari node pengamatan sejak {$menitTerakhir} menit lalu.";
        if ($waktuTerakhir) {
            $baris[] = "Data terakhir: {$waktuTerakhir} WIB";
        }
        $baris[] = "";
        $baris[] = "ℹ️ Periksa kondisi perangkat (daya, jaringan, atau sensor).";

        return implode("\n", $baris);
    }

    /**
     * Pesan saat data kembali normal setelah terputus
     */
    public function pesanDataNormal(?string $waktuKembali): string
    {
        $baris = [];
        $baris[] = "✅ <b>SISTEM PERINGATAN DINI HUJAN</b>";
        $baris[] = "";
        $baris[] = "🔗 <b>DATA NORMAL KEMBALI</b>";
        $baris[] = "";
        $baris[] = "Sistem kembali menerima data dari node pengamatan.";
        if ($waktuKembali) {
            $baris[] = "Pukul: {$waktuKembali} WIB";
        }

        return implode("\n", $baris);
    }
}