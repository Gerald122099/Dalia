<?php require_once __DIR__ . '/bootstrap.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dink It With Dalia — Pickleball Booking</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
:root{
  --bg:#0b0a11; --panel:#111025; --line:#26264a; --text:#f3efff; --muted:#bfb7e6;
  --neon:#8b3dff; --neon2:#4b1cff; --lime:#00ff99; --aqua:#00d7ff; --warn:#ffe27a;
}
*{box-sizing:border-box}
body{
  margin:0;color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto;
  background:
    radial-gradient(900px 600px at -20% -10%, rgba(139,61,255,.3), transparent 60%),
    radial-gradient(700px 500px at 110% 10%, rgba(0,215,255,.18), transparent 60%),
    linear-gradient(180deg,#0b0a11,#090812);
}
body::before, body::after{
  content:""; position:fixed; inset:auto; width:120px; height:120px; border-radius:50%;
  background:
    radial-gradient(circle at 35% 35%,rgba(0,0,0,.35) 0 30%, transparent 31%),
    radial-gradient(circle at 65% 35%,rgba(0,0,0,.35) 0 30%, transparent 31%),
    radial-gradient(circle at 35% 65%,rgba(0,0,0,.35) 0 30%, transparent 31%),
    radial-gradient(circle at 65% 65%,rgba(0,0,0,.35) 0 30%, transparent 31%),
    radial-gradient(circle at 50% 50%, rgba(255,255,255,.07), transparent 60%),
    radial-gradient(circle, rgba(0,255,153,.25), rgba(139,61,255,.25));
  box-shadow:0 0 24px rgba(139,61,255,.4), inset 0 0 30px rgba(255,255,255,.06);
  animation:float 18s linear infinite; pointer-events:none; opacity:.25; z-index:0;
}
body::after{ width:90px; height:90px; animation-duration: 22s; animation-direction: reverse; opacity:.22; }
@keyframes float{ 0%{ transform:translate(-10vw, 10vh) rotate(0deg);} 50%{ transform:translate(100vw, -10vh) rotate(180deg);} 100%{ transform:translate(-10vw, 10vh) rotate(360deg);} }

.wrap{max-width:1100px;margin:0 auto;padding:16px; position:relative; z-index:1}
header.app{display:flex;align-items:center;justify-content:space-between;padding:12px 0}
.brand{display:flex;align-items:center;gap:10px}
.logo{width:44px;height:44px;border-radius:12px;background:conic-gradient(from 0deg, var(--neon), var(--aqua), var(--lime), var(--neon)); box-shadow:0 0 24px rgba(139,61,255,.6)}
h1{margin:0;font-size:1.15rem;letter-spacing:.3px}
.nav a{color:#d0c6ff;text-decoration:none;margin-left:10px}
.badge{font-size:.72rem;color:#04140e;background:var(--lime);padding:3px 7px;border-radius:999px;font-weight:800}

.card{background:linear-gradient(180deg,#16143a,#0f0d22);border:1px solid var(--line);border-radius:16px;padding:14px;box-shadow:0 16px 42px rgba(0,0,0,.35)}
.grid{display:grid;gap:12px}
.grid-2{grid-template-columns:1.1fr .9fr}
@media (max-width:860px){ .grid-2{grid-template-columns:1fr} }

label{display:block;color:var(--muted);font-size:.88rem;margin:6px 0 4px}
input,select,textarea,button{width:100%;padding:11px 12px;border-radius:12px;border:1px solid var(--line);background:#0d0a1a;color:var(--text)}
button{cursor:pointer}
button.primary{background:linear-gradient(180deg,var(--neon),var(--neon2));border:1px solid var(--neon2);box-shadow:0 0 16px rgba(139,61,255,.45), inset 0 0 10px rgba(255,255,255,.05);text-transform:uppercase;letter-spacing:.4px;font-weight:900}
button.ghost{background:transparent;border:1px dashed var(--line)}
button.primary:hover{box-shadow:0 0 26px rgba(139,61,255,.65), inset 0 0 12px rgba(255,255,255,.08)}

.row{display:flex;gap:10px}.row>*{flex:1}
.slot{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px;border:1px solid var(--line);border-radius:12px;background:rgba(139,61,255,.08)}
.chip{padding:4px 8px;border-radius:999px;border:1px solid var(--line);font-size:.8rem}
.chip.ok{background:rgba(0,255,153,.12);border-color:var(--lime);color:#aefbe0}
.chip.pending{background:rgba(255,214,0,.12);border-color:var(--warn);color:var(--warn)}
.chip.confirmed{background:rgba(139,61,255,.15);border-color:#b59bff;color:#e7ddff}
.mini{font-size:.85rem;color:var(--muted)}

.cal-icon{
  display:inline-flex;align-items:center;gap:6px;padding:10px 12px;border:1px solid var(--line);border-radius:12px;
  background:linear-gradient(180deg,#101027,#0a0a1e);
  box-shadow:0 0 0 3px rgba(0,255,153,.45), 0 0 18px rgba(139,61,255,.35);
  color:var(--lime); font-weight:800; letter-spacing:.3px;
}
.cal-icon span{font-weight:900;color:var(--lime)}
.cal-icon:hover{box-shadow:0 0 0 4px rgba(0,255,153,.75), 0 0 24px rgba(139,61,255,.55); transform:translateY(-1px)}

hr.sep{border:0;border-top:1px solid var(--line);margin:10px 0}

/* SweetAlert custom theming */
.swal2-popup{
  background:linear-gradient(180deg,#17143f,#0f0d25); border:1px solid #2a2a5a;
  color:var(--text); border-radius:18px; box-shadow:0 24px 70px rgba(0,0,0,.55);
}
.swal2-title{ font-weight:900; letter-spacing:.3px }
.swal2-styled.swal2-confirm{ background:linear-gradient(180deg,var(--neon),var(--neon2)) !important; border:0 !important }
.swal2-styled.swal2-cancel{ background:transparent !important; border:1px dashed var(--line) !important; color:var(--text) !important }
.swal2-html-container label{ color:#cfc8ff }
</style>
</head>
<body>
<div class="wrap">
  <header class="app">
    <div class="brand">
      <div class="logo"></div>
      <div>
        <h1>Dink It With Dalia <span class="badge">Pickleball</span></h1>
        <div class="mini">Sporty neon purple + green • Mobile-first</div>
      </div>
    </div>
    <nav class="nav"><a href="index.php">Booking</a> • <a href="admin.php">Admin</a></nav>
  </header>
