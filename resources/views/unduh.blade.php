@extends('layouts.app')

@section('title', 'Unduh CSV')

@section('styles')
<style>
.unduh-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.4rem;max-width:480px}
.field{margin-bottom:.9rem}
.field label{display:block;font-size:.68rem;font-weight:600;color:var(--muted);letter-spacing:.07em;text-transform:uppercase;margin-bottom:.35rem}
.field input[type=date]{width:100%;padding:.55rem .8rem;border:1px solid var(--border);border-radius:8px;font-family:'DM Mono',monospace;font-size:.83rem;color:var(--text);background:#fafbff;outline:none;transition:border-color .15s}
.field input[type=date]:focus{border-color:var(--accent);background:#fff}
.unduh-btn{display:inline-flex;align-items:center;gap:7px;padding:.6rem 1.3rem;background:var(--navy);color:#fff;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.83rem;font-weight:600;cursor:pointer;transition:background .15s,transform .1s;text-decoration:none}
.unduh-btn:hover{background:var(--navy3)}
.unduh-btn:active{transform:scale(.97)}
.info-box{background:#f0f4ff;border:1px solid #c8d8f8;border-radius:8px;padding:.75rem 1rem;font-size:.78rem;color:var(--muted);margin-top:.5rem}
.error-box{background:var(--awas-bg);border:1px solid var(--awas-border);border-radius:8px;padding:.65rem 1rem;font-size:.78rem;color:var(--awas);margin-top:.75rem;display:none;font-weight:500}
.error-box.show{display:block;animation:fadeUp .2s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
</style>
@endsection

@section('content')
<div class="page-wrap">
  <div class="page-head">
    <div class="page-head-left">
      <div class="page-title">Unduh Data CSV</div>
      <div class="page-sub">Ekspor data pengamatan dalam rentang tanggal tertentu</div>
    </div>
    @if($lastUpdate)
    <div class="page-update">
      <div class="page-update-key">Pembaruan Terakhir</div>
      <div class="page-update-val">{{ $lastUpdate }}</div>
    </div>
    @endif
  </div>

  <div class="unduh-card">
    <div class="field">
      <label>Tanggal Mulai</label>
      <input type="date" id="tgl-mulai" value="{{ date('Y-m-d', strtotime('-7 days')) }}" max="{{ date('Y-m-d') }}">
    </div>
    <div class="field">
      <label>Tanggal Selesai</label>
      <input type="date" id="tgl-selesai" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
    </div>

    <a id="unduh-btn" class="unduh-btn" href="#">
      <svg fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" viewBox="0 0 24 24">
        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12v9M8 16l4 4 4-4"/>
      </svg>
      Unduh CSV
    </a>

    <div class="info-box">
      Rentang maksimal 31 hari. File CSV dapat dibuka di Microsoft Excel atau Google Sheets.
    </div>

    <div class="error-box" id="error-box"></div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('unduh-btn').addEventListener('click', function(e) {
  e.preventDefault();

  const mulai      = document.getElementById('tgl-mulai').value;
  const selesai    = document.getElementById('tgl-selesai').value;
  const errorBox   = document.getElementById('error-box');

  errorBox.classList.remove('show');

  // Validasi: harus diisi
  if (!mulai || !selesai) {
    tampilkanError('Mohon isi tanggal mulai dan tanggal selesai.');
    return;
  }

  const tglMulai   = new Date(mulai);
  const tglSelesai = new Date(selesai);
  const hariIni    = new Date(new Date().toISOString().split('T')[0]);

  // Validasi: tanggal mulai tidak boleh > tanggal selesai
  if (tglMulai > tglSelesai) {
    tampilkanError('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
    return;
  }

  // Validasi: tidak boleh masa depan
  if (tglMulai > hariIni || tglSelesai > hariIni) {
    tampilkanError('Tanggal tidak boleh melebihi hari ini.');
    return;
  }

  // Validasi: rentang maksimal 31 hari
  const selisihHari = Math.floor((tglSelesai - tglMulai) / (1000 * 60 * 60 * 24)) + 1;
  if (selisihHari > 31) {
    tampilkanError(`Rentang melebihi 31 hari (saat ini ${selisihHari} hari). Mohon persempit rentang tanggal.`);
    return;
  }

  // Lolos semua validasi, unduh CSV
  window.location.href = "{{ route('unduh.export') }}?dari=" + mulai + "&sampai=" + selesai;
});

function tampilkanError(pesan) {
  const errorBox = document.getElementById('error-box');
  errorBox.textContent = '⚠ ' + pesan;
  errorBox.classList.add('show');
}
</script>
@endsection