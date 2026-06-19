@extends('layouts.app')

@section('title', 'Riwayat')

@section('styles')
<style>
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:.78rem}
thead tr{background:#f5f7fd}
th{padding:.7rem .85rem;text-align:left;font-size:.66rem;font-weight:600;color:var(--muted);letter-spacing:.07em;text-transform:uppercase;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:.65rem .85rem;border-bottom:1px solid #f0f2fa;font-family:'DM Mono',monospace;font-size:.76rem}
tr:last-child td{border-bottom:none}
tbody tr:hover{background:#f8f9fd}
tr.row-uji{background:#fffaf2}
tr.row-uji:hover{background:#fff4e3}
.empty-state{text-align:center;padding:2.5rem;color:var(--muted);font-family:'DM Sans',sans-serif}
.sipedih-pagination{display:flex;justify-content:space-between;align-items:center;padding:.85rem 1rem;border-top:1px solid var(--border);background:#fafbff}
.pag-info{font-size:.75rem;color:var(--muted);font-family:'DM Sans',sans-serif}
.pag-links{display:flex;align-items:center;gap:.5rem}
.pag-btn{display:inline-flex;align-items:center;padding:.35rem .75rem;border:1px solid var(--border);border-radius:6px;background:#fff;color:var(--text);font-size:.74rem;font-weight:500;text-decoration:none;font-family:'DM Sans',sans-serif;transition:all .15s}
.pag-btn:hover:not(.disabled){background:var(--navy);color:#fff;border-color:var(--navy)}
.pag-btn.disabled{color:#c0c8e0;cursor:not-allowed;background:#f5f7fd}
.pag-current{font-size:.74rem;color:var(--text);font-family:'DM Sans',sans-serif;padding:0 .5rem}
.info-banner{background:#f0f4ff;border:1px solid #c8d8f8;border-radius:8px;padding:.6rem 1rem;font-size:.75rem;color:var(--muted)}
.riwayat-toolbar{display:flex;justify-content:flex-end;align-items:center;gap:.6rem}
.toggle-uji{display:inline-flex;align-items:center;gap:6px;padding:.4rem .8rem;border:1px solid var(--border);border-radius:6px;background:#fff;color:var(--text);font-size:.74rem;font-weight:500;text-decoration:none;font-family:'DM Sans',sans-serif;transition:all .15s}
.toggle-uji:hover{background:var(--navy);color:#fff;border-color:var(--navy)}
.toggle-uji.active{background:var(--waspada-bg);border-color:var(--waspada-border);color:var(--waspada)}
.pill-uji{display:inline-block;padding:2px 7px;border-radius:20px;font-size:.62rem;font-weight:600;background:var(--waspada-bg);color:var(--waspada);font-family:'DM Sans',sans-serif;margin-left:5px}
</style>
@endsection

@section('content')
@php
    $kelasLabel  = ['Tidak Hujan','Hujan Ringan','Hujan Sedang–Sangat Lebat'];
    $statusLabel = ['Aman','Waspada','Awas'];
    $tampilkanUji = $tampilkanUji ?? false;
@endphp

<div class="page-wrap">
  <div class="page-head">
    <div class="page-head-left">
      <div class="page-title">Riwayat Pengamatan</div>
      <div class="page-sub">Catatan pengamatan 24 jam terakhir</div>
    </div>
    @if($lastUpdate)
    <div class="page-update">
      <div class="page-update-key">Pembaruan Terakhir</div>
      <div class="page-update-val">{{ $lastUpdate }}</div>
    </div>
    @endif
  </div>

  <div class="info-banner">
    Riwayat dibatasi 24 jam terakhir. Untuk data lebih lama, gunakan menu Unduh CSV.
  </div>

  {{-- Toggle data uji (injeksi/demo) --}}
  <div class="riwayat-toolbar">
    @if($tampilkanUji)
      <a href="{{ route('riwayat') }}" class="toggle-uji active">● Data uji ditampilkan — sembunyikan</a>
    @else
      <a href="{{ route('riwayat', ['tampilkan_uji' => 1]) }}" class="toggle-uji">Tampilkan data uji</a>
    @endif
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Waktu</th>
          <th>T (°C)</th>
          <th>RH (%)</th>
          <th>P (hPa)</th>
          <th>RR (mm)</th>
          <th>Prediksi +1 Jam</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($riwayat as $row)
        <tr class="{{ $row->is_test ? 'row-uji' : '' }}">
          <td>{{ \Carbon\Carbon::parse($row->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</td>
          <td>{{ \Carbon\Carbon::parse($row->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('H:i:s') }}</td>
          <td>{{ $row->temp }}</td>
          <td>{{ $row->humidity }}</td>
          <td>{{ $row->pressure }}</td>
          <td>{{ $row->rainfall }}</td>
          <td><span class="pill pill-{{ $row->pred_class }}">{{ $kelasLabel[$row->pred_class] }}</span></td>
          <td>
            <span class="pill pill-{{ $row->status }}">{{ $statusLabel[$row->status] ?? '-' }}</span>
            @if($row->is_test)<span class="pill-uji">UJI</span>@endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" class="empty-state">Belum ada data pengamatan dalam 24 jam terakhir</td></tr>
        @endforelse
      </tbody>
    </table>

    {{ $riwayat->links('vendor.pagination.sipedih') }}
  </div>
</div>
@endsection
