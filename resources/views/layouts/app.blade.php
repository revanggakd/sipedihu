<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPEDIH — @yield('title', 'Sistem Peringatan Dini Hujan')</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#0f1f4b;--navy2:#162354;--navy3:#1e2f6a;--accent:#3b6ef5;
  --bg:#f4f6fb;--card:#ffffff;--text:#0d1633;--muted:#6b7aaa;--border:#dde3f5;
  --aman:#1a7f52;--aman-bg:#e6f5ed;--aman-border:#b2dfc6;
  --bersiap:#b07d00;--bersiap-bg:#fdf4d7;--bersiap-border:#f0d78a;
  --waspada:#c25a00;--waspada-bg:#fff0e0;--waspada-border:#f5c68a;
  --awas:#c02020;--awas-bg:#fdeaea;--awas-border:#f0a0a0;
  --evaluasi:#5a6a99;--evaluasi-bg:#e8eaf2;--evaluasi-border:#c8cfe0;
  --sidebar:200px;--header:60px;
}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* SIDEBAR — fixed full height dari atas */
aside{
  position:fixed;top:0;left:0;
  width:var(--sidebar);height:100vh;
  background:var(--navy2);
  display:flex;flex-direction:column;
  padding:1.5rem 0;z-index:200;
}
.brand{padding:.25rem 1.25rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:.75rem}
.brand{display:flex;align-items:center;gap:10px;padding:.25rem 1.25rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:.75rem}
.brand-logo{width:45px;height:45px;object-fit:contain;flex-shrink:0}
.brand-name{font-size:.9rem;font-weight:600;color:#fff;letter-spacing:.03em}
.brand-sub{font-size:.65rem;color:rgba(255,255,255,.4);margin-top:2px}
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:.65rem 1.25rem;color:rgba(255,255,255,.55);
  font-size:.85rem;font-weight:500;cursor:pointer;
  border-left:3px solid transparent;transition:all .15s;text-decoration:none;
}
.nav-item svg{width:16px;height:16px;flex-shrink:0;opacity:.7}
.nav-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.85)}
.nav-item.active{background:rgba(59,110,245,.18);color:#fff;border-left-color:#5b8df7}
.nav-item.active svg{opacity:1}

/* HEADER — judul di tengah */
header{
  height:var(--header);background:var(--navy);
  display:flex;align-items:center;justify-content:center;
  padding:0 1.5rem;
  position:fixed;top:0;left:var(--sidebar);right:0;z-index:100;
}
.header-title{color:#fff;font-size:.95rem;font-weight:600;letter-spacing:.04em;text-align:center}

/* MAIN */
main{
  margin-left:var(--sidebar);
  margin-top:var(--header);
  min-height:calc(100vh - var(--header));
  display:flex;flex-direction:column;
}

.page-wrap{flex:1;display:flex;flex-direction:column;padding:1.5rem 2rem;gap:1.1rem}

/* HEADER HALAMAN + pembaruan terakhir */
.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.3rem}
.page-head-left{display:flex;flex-direction:column}
.page-update{text-align:right;flex-shrink:0}
.page-update-key{font-size:.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}
.page-update-val{font-size:.78rem;font-weight:600;font-family:'DM Mono',monospace;color:var(--text);margin-top:1px}

.page-title{font-size:1.05rem;font-weight:600}
.page-sub{font-size:.75rem;color:var(--muted);margin-top:2px;margin-bottom:.1rem}
.panel{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.1rem}
.panel-title{font-size:.68rem;font-weight:600;color:var(--muted);letter-spacing:.07em;text-transform:uppercase;margin-bottom:.85rem}
.pill{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.68rem;font-weight:600;font-family:'DM Sans',sans-serif}
.pill-0{background:var(--aman-bg);color:var(--aman)}
.pill-1{background:var(--bersiap-bg);color:var(--bersiap)}
.pill-2{background:var(--awas-bg);color:var(--awas)}
footer{text-align:center;padding:.65rem;font-size:.68rem;color:var(--muted);border-top:1px solid var(--border);background:#f9fafc}
@yield('styles')
</style>
</head>
<body>

<aside>
  <div class="brand">
    <img src="{{ asset('img/stmkg_logo.png') }}" alt="STMKG" class="brand-logo">
    <div class="brand-name">SIPEDIHU</div>
  </div>
  <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </a>
  <a href="{{ route('riwayat') }}" class="nav-item {{ request()->routeIs('riwayat') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    Riwayat
  </a>
  <a href="{{ route('unduh') }}" class="nav-item {{ request()->routeIs('unduh') ? 'active' : '' }}">
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12v9M8 16l4 4 4-4"/></svg>
    Unduh CSV
  </a>
</aside>

<header>
  <div class="header-title">Sistem Peringatan Dini Hujan</div>
</header>

<main>
  @yield('content')
  <footer>SIPEDIHU — Sistem Peringatan Dini Hujan &nbsp;|&nbsp; {{ date('Y') }}</footer>
</main>

@yield('scripts')
</body>
</html>