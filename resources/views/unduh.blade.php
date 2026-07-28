@extends('layouts.app')

@section('title', 'Unduh CSV')

@section('styles')
<style>
.unduh-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.4rem;max-width:480px}
.field{margin-bottom:.9rem}
.field label{display:block;font-size:.68rem;font-weight:600;color:var(--muted);letter-spacing:.07em;text-transform:uppercase;margin-bottom:.35rem}
.field input[type=date]{width:100%;padding:.55rem .8rem;border:1px solid var(--border);border-radius:8px;font-family:'DM Mono',monospace;font-size:.83rem;color:var(--text);background:#fafbff;outline:none;transition:border-color .15s;box-sizing:border-box}
.field input[type=date]:focus{border-color:var(--accent);background:#fff}
.unduh-btn{display:inline-flex;align-items:center;gap:7px;padding:.6rem 1.3rem;background:var(--navy);color:#fff;border:none;border-radius:8px;font-family:'DM Sans',sans-serif;font-size:.83rem;font-weight:600;cursor:pointer;transition:background .15s,transform .1s;text-decoration:none;user-select:none}
.unduh-btn:hover{background:var(--navy3)}
.unduh-btn:active{transform:scale(.97)}
.unduh-btn.loading{opacity:.65;pointer-events:none;cursor:not-allowed}
.info-box{background:#f0f4ff;border:1px solid #c8d8f8;border-radius:8px;padding:.75rem 1rem;font-size:.78rem;color:var(--muted);margin-top:.75rem}
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
      <label for="tgl-mulai">Tanggal Mulai</label>
      <input type="date" id="tgl-mulai"
             value="{{ date('Y-m-d', strtotime('-7 days')) }}"
             max="{{ date('Y-m-d') }}">
    </div>
    <div class="field">
      <label for="tgl-selesai">Tanggal Selesai</label>
      <input type="date" id="tgl-selesai"
             value="{{ date('Y-m-d') }}"
             max="{{ date('Y-m-d') }}">
    </div>

    <button id="unduh-btn" class="unduh-btn" type="button">
      <svg id="btn-icon" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" viewBox="0 0 24 24">
        <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12v9M8 16l4 4 4-4"/>
      </svg>
      <span id="btn-label">Unduh CSV</span>
    </button>

    <div class="info-box">
      Rentang maksimal 31 hari.
      File CSV dapat dibuka di Microsoft Excel atau Google Sheets.
    </div>

    <div class="error-box" id="error-box"></div>
  </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
  var btn      = document.getElementById('unduh-btn');
  var btnLabel = document.getElementById('btn-label');
  var errorBox = document.getElementById('error-box');
  var exportUrl = "{{ route('unduh.export') }}";

  btn.addEventListener('click', function () {
    errorBox.classList.remove('show');

    var mulai   = document.getElementById('tgl-mulai').value;
    var selesai = document.getElementById('tgl-selesai').value;

    // Validasi: harus diisi
    if (!mulai || !selesai) {
      tampilkanError('Mohon isi tanggal mulai dan tanggal selesai.');
      return;
    }

    var tglMulai   = new Date(mulai);
    var tglSelesai = new Date(selesai);
    var hariIni    = new Date(new Date().toISOString().split('T')[0]);

    // Validasi: urutan tanggal
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
    var selisihHari = Math.floor((tglSelesai - tglMulai) / (1000 * 60 * 60 * 24)) + 1;
    if (selisihHari > 31) {
      tampilkanError(
        'Rentang melebihi 31 hari (saat ini ' + selisihHari + ' hari). '
      );
      return;
    }

    // Lolos validasi — trigger download via <a> tersembunyi
    // Teknik ini lebih andal di Chrome/Edge dibanding window.location.href
    // karena browser memperlakukan klik <a download> sebagai download intent
    // yang eksplisit, bukan navigasi.
    setLoading(true);

    var url = exportUrl + '?dari=' + encodeURIComponent(mulai) + '&sampai=' + encodeURIComponent(selesai);
    var a   = document.createElement('a');
    a.href  = url;
    a.setAttribute('download', 'sipedihu_' + mulai + '_' + selesai + '.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    // Kembalikan tombol ke normal setelah jeda singkat
    setTimeout(function () { setLoading(false); }, 2500);
  });

  function setLoading(aktif) {
    if (aktif) {
      btn.classList.add('loading');
      btnLabel.textContent = 'Mengunduh…';
    } else {
      btn.classList.remove('loading');
      btnLabel.textContent = 'Unduh CSV';
    }
  }

  function tampilkanError(pesan) {
    errorBox.textContent = '⚠ ' + pesan;
    errorBox.classList.add('show');
  }
})();
</script>
@endsection