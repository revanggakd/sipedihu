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
.mid-row{display:grid;grid-template-columns:1fr 1.6fr;gap:.85rem}
.pred-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:.78rem;font-weight:600;margin-bottom:.85rem}
.pred-badge.kelas-0{background:var(--aman-bg);color:var(--aman)}
.pred-badge.kelas-1{background:var(--waspada-bg);color:var(--waspada)}
.pred-badge.kelas-2{background:var(--awas-bg);color:var(--awas)}
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
.bat-panel{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.1rem}
.bat-row-info{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem}
.bat-kv{font-size:.78rem;color:var(--muted)}
.bat-val{font-family:'DM Mono',monospace;font-size:.78rem;font-weight:500}
.bat-track{height:8px;background:#eef0fb;border-radius:4px;overflow:hidden;margin:.4rem 0 .25rem}
.bat-fill{height:100%;border-radius:4px}
.bat-labels{display:flex;justify-content:space-between;font-size:.65rem;color:var(--muted)}
.status-banner.offline{background:#eceef2;border-color:#cfd5e0}
.status-banner.offline .status-val,.status-banner.offline .status-desc,.status-banner.offline .status-berlaku{color:#5a6577}
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

    // pred_class mentah untuk badge ML (3 kelas)
    $kelasLabel = [
        'Tidak Hujan',
        'Hujan Ringan',
        'Hujan Sedang–Sangat Lebat',
    ];
    $predBadgeKelas = ['kelas-0','kelas-1','kelas-2'];
    $probColors = ['#1a7f52', '#c25a00', '#c02020'];  // K1 selaras --waspada

    // Threshold PR (samakan dgn firmware & receiver): K1, K2
    $thr = [null, 0.2439, 0.1691];

    $sp  = $data && $data->status_peringatan !== null ? (int) $data->status_peringatan : 0;
    $smp = $statusMap[$sp] ?? $statusMap[0];

    // Override tampilan kalau data basi
    if ($dataBasi) {
        $smp = ['label'=>'MENUNGGU DATA', 'desc'=>'Sistem belum menerima data terbaru', 'cls'=>'offline'];
    }

    $c = $data ? (int) $data->pred_class : 0;

    $probs = $data ? [
        $data->prob_no_rain,
        $data->prob_light_rain,
        $data->prob_medium_rain,
    ] : [0, 0, 0];

    // Kekuatan sinyal: prob kelas aktif / threshold (Lemah/Sedang/Kuat)
    // Hanya untuk status Waspada/Awas dan data tidak basi.
    $kekuatan = null;       // null = tidak ditampilkan
    $kekuatanLevel = 0;     // 1/2/3 untuk indikator titik
    if ($data && !$dataBasi && $sp != 0) {
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

  {{-- STATUS BANNER (berlapis: status -> kekuatan sinyal -> berlaku) --}}
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

  {{-- MID ROW --}}
  <div class="mid-row">
    <div class="panel">
      <div class="panel-title">Prediksi Model ML</div>
      <div class="pred-badge {{ $predBadgeKelas[$c] }}">{{ $kelasLabel[$c] }}</div>
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
          <span class="prob-pct">{{ round($prob * 100) }}%</span>
        </div>
        <div class="prob-track">
          <div class="prob-bar" style="width:{{ round($prob * 100) }}%;background:{{ $warna }}"></div>
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
  </div>

  {{-- BATERAI --}}
  <div class="bat-panel">
    <div class="panel-title">Status Baterai VRLA</div>
    <div class="bat-row-info">
      <span class="bat-kv">Tegangan</span>
      <span class="bat-val">{{ $data?->battery_voltage ? number_format($data->battery_voltage, 2).' V' : '—' }}</span>
    </div>
    <div class="bat-row-info">
      <span class="bat-kv">Estimasi Kapasitas</span>
      <span class="bat-val">{{ $data ? round($batPct).'%' : '—' }}</span>
    </div>
    <div class="bat-track">
      <div class="bat-fill" style="width:{{ round($batPct) }}%;{{ $batCls }}"></div>
    </div>
    <div class="bat-labels"><span>0%</span><span>100%</span></div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const trendData = @json($trendData ?? []);

// Label waktu sudah diformat WIB di server (field 'waktu')
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