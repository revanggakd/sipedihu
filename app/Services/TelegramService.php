<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;
    private string $chatId;

    // Threshold PR (samakan dgn firmware, receiver, dashboard)
    private const THR_K1 = 0.2439;
    private const THR_K2 = 0.1691;

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

    public function kirimKe(string $chatId, string $pesan): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $pesan,
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram kirimKe gagal: ' . $e->getMessage());
        }
    }

    public function getUpdates(int $offset = 0): array
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$this->token}/getUpdates", [
                'offset'  => $offset,
                'timeout' => 0,
            ]);
            return $response->json()['result'] ?? [];
        } catch (\Exception $e) {
            Log::error('Telegram getUpdates gagal: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Kekuatan sinyal peringatan dari rasio prob/threshold.
     * Status Aman -> null (tidak ditampilkan).
     */
    private function kekuatanSinyal(int $status, float $prob): ?string
    {
        if ($status === 0) return null;
        $thr = $status === 2 ? self::THR_K2 : self::THR_K1;
        if ($thr <= 0) return null;
        $rasio = $prob / $thr;
        if ($rasio < 1.5) return 'Lemah';
        if ($rasio < 2.5) return 'Sedang';
        return 'Kuat';
    }

    /**
     * Susun pesan peringatan (status peringatan resmi 0-2)
     */
    public function pesanStatus(object $data, string $labelKelas, string $labelStatus, bool $pengingat = false): string
    {
        $status = (int) $data->status_peringatan;

        // emoji, label kelas (sesuai status), narasi, aksi
        $cfg = [
            0 => ['emoji'=>'🟢','label'=>'AMAN',    'kelas'=>'TIDAK HUJAN',                'narasi'=>'Kondisi cuaca normal. Tidak ada potensi hujan signifikan dalam 1 jam ke depan.', 'aksi'=>'Tidak ada tindakan khusus diperlukan.'],
            1 => ['emoji'=>'🟡','label'=>'WASPADA', 'kelas'=>'HUJAN RINGAN',               'narasi'=>'Potensi hujan ringan dalam 1 jam ke depan.', 'aksi'=>'Pantau perkembangan cuaca dan tetap waspada.'],
            2 => ['emoji'=>'🔴','label'=>'AWAS',    'kelas'=>'HUJAN SEDANG–SANGAT LEBAT',  'narasi'=>'Potensi hujan sedang hingga sangat lebat dalam 1 jam ke depan.', 'aksi'=>'Segera lakukan tindakan keselamatan.'],
        ];
        $c = $cfg[$status] ?? $cfg[0];

        // prob kelas aktif (sesuai status) untuk kekuatan sinyal
        $probAktif = match ($status) {
            2 => $data->prob_medium_rain,
            1 => $data->prob_light_rain,
            default => $data->prob_no_rain,
        };
        $kekuatan = $this->kekuatanSinyal($status, (float) $probAktif);

        // waktu WIB (recorded_at disimpan UTC)
        $mulai   = \Carbon\Carbon::parse($data->recorded_at, 'UTC')->setTimezone('Asia/Jakarta');
        $selesai = $mulai->copy()->addHour();

        $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                  7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        $tglUpdate = $mulai->day . ' ' . $bulan[$mulai->month] . ' ' . $mulai->year
                   . ' | ' . $mulai->format('H.i') . ' WIB';

        $judul = $pengingat
            ? "🔔 <b>PENGINGAT — PERINGATAN MASIH AKTIF</b>"
            : "🌧️ <b>SISTEM PERINGATAN DINI HUJAN</b>";

        $baris = [];
        $baris[] = $judul;
        $baris[] = "";
        $baris[] = "{$c['emoji']} Status : <b>{$c['label']}</b>";
        $baris[] = "Prediksi : <b>{$c['kelas']}</b>";
        if ($kekuatan !== null) {
            $baris[] = "📶 Kekuatan Sinyal : {$kekuatan}";
        }
        $baris[] = "🕐 Masa Berlaku : {$mulai->format('H.i')} – {$selesai->format('H.i')} WIB";
        $baris[] = "";
        $baris[] = "ℹ️ {$c['aksi']}";
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