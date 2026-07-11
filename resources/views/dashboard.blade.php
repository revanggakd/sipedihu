@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<style>
.status-banner{border-radius:12px;padding:1.3rem 1.4rem;border:1px solid;text-align:center;display:flex;flex-direction:column;align-items:center;gap:3px}
.status-banner.aman    {background:var(--aman-bg);border-color:var(--aman-border)}
.status-banner.waspada {background:var(--waspada-bg);border-color:var(--waspada-border)}
.status-banner.awas    {background:var(--awas-bg);border-color:var(--awas-border)}
.status-label-small{font-size:.68rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;opacity:.65}
.status-val{font-size:1.7rem;font-weight:600;letter-spacing:.03em;line-height:1.1}
.status-desc{font-size:.82rem;opacity:.8}
.status-kekuatan{font-size:.8rem;font-weight:600;margin-top:.15rem;display:flex;align-items:center;gap:6px}
.kekuatan-dot{display:inline-flex;gap:3px}
.kekuatan-dot i{width:7px;height:7px;border-radius:50%;background:currentColor;opacity:.25;font-style:normal}
.kekuatan-dot i.on{opacity:1}
.status-berlaku{font-size:.78rem;font-weight:600;font-family:'DM Mono',monospace;margin-top:.5rem;padding-top:.5rem;border-top:1px solid rgba(0,0,0,.07);width:100%;max-width:320px}
.status-banner.aman .status-val,.status-banner.aman .status-desc,.status-banner.aman .status-kekuatan,.status-banner.aman .status-berlaku{color:var(--aman)}
.status-banner.waspada .status-val,.status-banner.waspada .status-desc,.status-banner.waspada .status-kekuatan,.status-banner.waspada .status-berlaku{color:var(--waspada)}
.status-banner.awas .status-val,.status-banner.awas .status-desc,.status-banner.awas .status-kekuatan,.status-banner.awas .status-berlaku{color:var(--awas)}
.sensor-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.85rem}
.sensor-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:.9rem 1rem}
.sensor-label{font-size:.68rem;font-weight:600;color:var(--muted);letter-spacing:.07em;text-transform:uppercase;margin-bottom:.45rem}
.sensor-val{font-family:'DM Mono',monospace;font-size:1.55rem;font-weight:500;color:var(--text);line-height:1}
.sensor-unit{font-size:.75rem;color:var(--muted);font-weight:400;margin-left:2px}
.mid-row{display:grid;grid-template-columns:1fr 1.5fr .7fr;gap:.85rem}
.pred-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:.78rem;font-weight:600;margin-bottom:.85rem}
.pred-badge.kelas-0{background:var(--aman-bg);color:var(--aman)}
.pred-badge.kelas-1{background:var(--waspada-bg);color:var(--waspada)}
.pred-badge.kelas-2{background:var(--awas-bg);color:var(--awas)}
.pred-badge.warmup{background:#eceef2;color:#5a6577}
.prob-row{margin-bottom:.55rem}
.prob-header{display:flex;justify-content:space-between;margin-bottom:3px}
.prob-name{font-size:.74rem;color:var(--text)}
.prob-ambang{font-size:.66rem;color:var(--muted);margin-left:5px}
.prob-pct{font-size:.74rem;font-weight:600;font-family:'DM Mono',monospace}
.prob-track{height:5px;background:#eef0fb;border-radius:3px;overflow:hidden;position:relative}
.prob-bar{height:100%;border-radius:3px}
.prob-thr{position:absolute;top:-2px;bottom:-2px;width:2px;background:#3a3f55;opacity:.55}
.prob-detail-note{font-size:.66rem;color:var(--muted);margin-top:.6rem;line-height:1.4}
.chart-wrap{position:relative;width:100%;height:175px}
.bat-mini{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem .9rem;display:flex;flex-direction:column}
.bat-mini-title{font-size:.68rem;font-weight:600;color:var(--muted);letter-spacing:.05em;text-transform:uppercase;margin-bottom:.9rem}
.bat-mini-val{font-family:'DM Mono',monospace;font-size:1.5rem;font-weight:500;color:var(--text);line-height:1}
.bat-mini-volt{font-family:'DM Mono',monospace;font-size:.8rem;color:var(--muted);margin-top:.2rem}
.bat-mini-track{height:7px;background:#eef0fb;border-radius:4px;overflow:hidden;margin-top:auto}
.bat-mini-fill{height:100%;border-radius:4px}
.status-banner.offline{background:#eceef2;border-color:#cfd5e0}
.status-banner.offline .status-val,.status-banner.offline .status-desc,.status-banner.offline .status-berlaku{color:#5a6577}

/* ===== RESPONSIVE ===== */
@media (max-width:768px){
  .sensor-grid{grid-template-columns:repeat(2,1fr)}
  .mid-row{grid-template-columns:1fr;gap:.7rem}
  .mid-left{gap:.7rem}
  .status-val{font-size:1.3rem}
  .sensor-val{font-size:1.2rem}
  .chart-panel .chart-wrap{min-height:200px}
  .page-head{flex-direction:column;gap:.5rem;align-items:flex-start}
  .page-update{text-align:left}
  .status-berlaku{font-size:.72rem}
}
@media (max-width:480px){
  .sensor-grid{grid-template-columns:repeat(2,1fr);gap:.6rem}
  .sensor-card{padding:.75rem .8rem}
  .sensor-val{font-size:1.1rem}
  .status-val{font-size:1.15rem}
  .prob-name{font-size:.7rem}
  .bat-sm-val{font-size:.7rem}
  .page-wrap{padding:.75rem}
}
</style>
@endsection

@section('content')
@php
    // status peringatan resmi (3 tingkat)
    $statusMap = [
        0 => ['label'=>'AMAN',    'desc'=>'Tidak Hujan',                'cls'=>'aman'],
        1 => ['label'=>'WASPADA', 'desc'=>'Potensi Hujan Ringan',       'cls'=>'waspada'],
        2 => ['label'=>'AWAS',    'desc'=>'Potensi Hujan Sedang–Sangat Lebat','cls'=>'awas'],
    ];

    $kelasLabel = [
        'Tidak Hujan',
        'Hujan Ringan',
        'Hujan Sedang–Sangat Lebat',
    ];
    $predBadgeKelas = ['kelas-0','kelas-1','kelas-2'];
    $probColors = ['#1a7f52', '#c25a00', '#c02020'];

    // Threshold PR (samakan dgn firmware & receiver)
    $thr = [null, 0.2439, 0.1691];

    // Deteksi warmup: prediksi serba-nol (model TX belum siap)
    $isWarmup = $data && ($data->prob_no_rain + $data->prob_light_rain + $data->prob_medium_rain) == 0;

    $sp  = $data && $data->status_peringatan !== null ? (int) $data->status_peringatan : 0;
    $smp = $statusMap[$sp] ?? $statusMap[0];

    // Override: basi > warmup > normal
    if ($dataBasi) {
        $smp = ['label'=>'MENUNGGU DATA', 'desc'=>'Sistem belum menerima data terbaru', 'cls'=>'offline'];
    } elseif ($isWarmup) {
        $smp = ['label'=>'—', 'desc'=>'Model sedang menyiapkan data', 'cls'=>'offline'];
    }

    $c = $data ? (int) $data->pred_class : 0;

    $probs = $data ? [
        $data->prob_no_rain,
        $data->prob_light_rain,
        $data->prob_medium_rain,
    ] : [0, 0, 0];

    // Kekuatan sinyal: prob kelas aktif / threshold (Lemah/Sedang/Kuat)
    // Hanya untuk status Waspada/Awas, data tidak basi, dan bukan warmup.
    $kekuatan = null;
    $kekuatanLevel = 0;
    if ($data && !$dataBasi && !$isWarmup && $sp != 0) {
        $rasio = $sp == 2 ? ($probs[2] / $thr[2]) : ($probs[1] / $thr[1]);
        if ($rasio < 1.5)      { $kekuatan = 'Lemah';  $kekuatanLevel = 1; }
        elseif ($rasio < 2.5)  { $kekuatan = 'Sedang'; $kekuatanLevel = 2; }
        else                   { $kekuatan = 'Kuat';   $kekuatanLevel = 3; }
    }

    $batPct = $data?->battery_percent ?? 0;
    $batCls = $batPct < 30 ? 'background:var(--awas)'
            : ($batPct < 60 ? 'background:var(--waspada)'
            : 'background:var(--aman)');

    // recorded_at disimpan UTC -> tampilkan WIB
    $recWib = $data ? \Carbon\Carbon::parse($data->recorded_at, 'UTC')->setTimezone('Asia/Jakarta') : null;
    $berlakuSampai = $recWib ? $recWib->copy()->addHour() : null;
@endphp

<div class="page-wrap">
  <div class="page-head">
    <div class="page-head-left">
      <div class="page-title">Dashboard</div>
      <div class="page-sub">Data terkini dari node pengamatan</div>
    </div>
    @if($lastUpdate)
    <div class="page-update">
      <div class="page-update-key">Pembaruan Terakhir</div>
      <div class="page-update-val">{{ $lastUpdate }}</div>
    </div>
    @endif
  </div>

  {{-- STATUS BANNER --}}
  <div class="status-banner {{ $smp['cls'] }}">
    <div class="status-label-small">Status Peringatan Dini</div>
    <div class="status-val">{{ $smp['label'] }}</div>
    <div class="status-desc">{{ $smp['desc'] }}</div>

    @if($kekuatan)
      <div class="status-kekuatan">
        Kekuatan sinyal: {{ $kekuatan }}
        <span class="kekuatan-dot">
          <i class="{{ $kekuatanLevel >= 1 ? 'on' : '' }}"></i>
          <i class="{{ $kekuatanLevel >= 2 ? 'on' : '' }}"></i>
          <i class="{{ $kekuatanLevel >= 3 ? 'on' : '' }}"></i>
        </span>
      </div>
    @endif

    @if($dataBasi)
      <div class="status-berlaku">
        ⚠ Data terakhir {{ $menitBerlalu }} menit lalu
      </div>
    @elseif($isWarmup)
      <div class="status-berlaku">
        Mengumpulkan data awal — prediksi belum tersedia
      </div>
    @elseif($recWib && $berlakuSampai)
      <div class="status-berlaku">
        Diperbarui {{ $recWib->format('H:i') }} — prediksi hingga {{ $berlakuSampai->format('H:i') }} WIB
      </div>
    @endif
  </div>

  {{-- SENSOR CARDS --}}
  <div class="sensor-grid">
    <div class="sensor-card">
      <div class="sensor-label">Suhu</div>
      <div class="sensor-val">{{ $data?->temp ?? '—' }}<span class="sensor-unit">°C</span></div>
    </div>
    <div class="sensor-card">
      <div class="sensor-label">Kelembapan</div>
      <div class="sensor-val">{{ $data?->humidity ?? '—' }}<span class="sensor-unit">%</span></div>
    </div>
    <div class="sensor-card">
      <div class="sensor-label">Tekanan</div>
      <div class="sensor-val">{{ $data?->pressure ?? '—' }}<span class="sensor-unit">hPa</span></div>
    </div>
    <div class="sensor-card">
      <div class="sensor-label">Curah Hujan</div>
      <div class="sensor-val">{{ $data?->rainfall ?? '—' }}<span class="sensor-unit">mm</span></div>
    </div>
  </div>

  {{-- MID ROW: Prediksi | Grafik | Baterai --}}
  <div class="mid-row">
    <div class="panel">
      <div class="panel-title">Prediksi Model ML</div>
      @if($isWarmup)
        <div class="pred-badge warmup">Model menyiapkan data</div>
      @else
        <div class="pred-badge {{ $predBadgeKelas[$c] }}">{{ $kelasLabel[$c] }}</div>
      @endif
      @foreach([
          ['Tidak Hujan',               $probs[0], $probColors[0], $thr[0]],
          ['Hujan Ringan',              $probs[1], $probColors[1], $thr[1]],
          ['Hujan Sedang–Sangat Lebat', $probs[2], $probColors[2], $thr[2]],
      ] as [$nama, $prob, $warna, $ambang])
      <div class="prob-row">
        <div class="prob-header">
          <span class="prob-name">
            {{ $nama }}
            @if($ambang)<span class="prob-ambang">ambang {{ round($ambang * 100) }}%</span>@endif
          </span>
          <span class="prob-pct">{{ $isWarmup ? '—' : round($prob * 100).'%' }}</span>
        </div>
        <div class="prob-track">
          <div class="prob-bar" style="width:{{ $isWarmup ? 0 : round($prob * 100) }}%;background:{{ $warna }}"></div>
          @if($ambang)<div class="prob-thr" style="left:{{ round($ambang * 100) }}%"></div>@endif
        </div>
      </div>
      @endforeach
      <div class="prob-detail-note">
        Angka di atas adalah probabilitas mentah model. Peringatan terpicu bila probabilitas
        melewati ambang (garis penanda). Kekuatan sinyal mengukur seberapa jauh di atas ambang.
      </div>
    </div>

    <div class="panel">
      <div class="panel-title">Tren 1 Jam Terakhir</div>
      <div class="chart-wrap">
        <canvas id="trendChart" role="img" aria-label="Grafik tren suhu, kelembapan, dan curah hujan"></canvas>
      </div>
    </div>

    {{-- BATERAI MINI (kanan grafik) --}}
    <div class="bat-mini">
      <div class="bat-mini-title">Baterai VRLA</div>
      <div class="bat-mini-val">{{ $data ? round($batPct).'%' : '—' }}</div>
      <div class="bat-mini-volt">{{ $data?->battery_voltage ? number_format($data->battery_voltage, 2).' V' : '—' }}</div>
      <div class="bat-mini-track">
        <div class="bat-mini-fill" style="width:{{ round($batPct) }}%;{{ $batCls }}"></div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const trendData = @json($trendData ?? []);
const labels = trendData.map(d => d.waktu);

new Chart(document.getElementById('trendChart'), {
  data: {
    labels,
    datasets: [
      {
        type: 'line', label: 'Suhu (°C)',
        data: trendData.map(d => d.temp),
        borderColor: '#e05050', backgroundColor: 'transparent',
        borderWidth: 1.8, pointRadius: 2.5, tension: .4, yAxisID: 'ySuhu',
      },
      {
        type: 'line', label: 'Kelembapan (%)',
        data: trendData.map(d => d.humidity),
        borderColor: '#3b6ef5', backgroundColor: 'transparent',
        borderWidth: 1.8, pointRadius: 2.5, tension: .4, borderDash: [4, 3], yAxisID: 'yRh',
      },
      {
        type: 'bar', label: 'Curah Hujan (mm)',
        data: trendData.map(d => d.rainfall),
        backgroundColor: 'rgba(26, 127, 82, 0.45)', borderColor: '#1a7f52',
        borderWidth: 1, yAxisID: 'yRain', barThickness: 8,
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: {
        display: true, position: 'top', align: 'end',
        labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, family: 'DM Sans' }, color: '#6b7aaa', padding: 8 }
      },
      tooltip: { callbacks: { title: (items) => items.length ? items[0].label + ' WIB' : '' } }
    },
    scales: {
      x: { ticks: { font: { size: 10 }, color: '#8898cc', maxTicksLimit: 7 }, grid: { display: false } },
      ySuhu: { position: 'left', ticks: { font: { size: 10 }, color: '#e05050' }, grid: { color: 'rgba(0,0,60,0.04)' }, title: { display: true, text: '°C', font: { size: 10 }, color: '#e05050' } },
      yRh: { position: 'right', ticks: { font: { size: 10 }, color: '#3b6ef5' }, grid: { display: false }, min: 0, max: 100, title: { display: true, text: '%', font: { size: 10 }, color: '#3b6ef5' } },
      yRain: { position: 'right', ticks: { font: { size: 10 }, color: '#1a7f52' }, grid: { display: false }, min: 0, offset: true, title: { display: true, text: 'mm', font: { size: 10 }, color: '#1a7f52' } }
    }
  }
});

setInterval(() => location.reload(), 30000);
</script>
@endsection