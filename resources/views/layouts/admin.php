<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'A-Racing Admin', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#0c0c10; --sidebar:#11131a; --card:#171922; --line:#2a2f3d; --text:#f5f5f7; --muted:#9ea0ac; --accent:#e10600; }
    body { margin:0; font:14px/1.4 Inter,Segoe UI,Arial,sans-serif; background:var(--bg); color:var(--text); display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
    aside { background:var(--sidebar); padding:1rem; border-right:1px solid var(--line); }
    aside a { display:block; color:var(--text); text-decoration:none; margin:.5rem 0; padding:.35rem .45rem; border-radius:6px; }
    aside a:hover { color:var(--accent); background:#1b1f2b; }
    main { padding:1rem; }
    .card { background:var(--card); border:1px solid var(--line); border-radius:8px; padding:.9rem; }
    .topline { display:flex; justify-content:space-between; align-items:center; margin-bottom:.8rem; }
    .btn { background:#222838; color:var(--text); border:1px solid #384055; padding:.35rem .55rem; border-radius:6px; text-decoration:none; }
    .btn:hover { border-color:var(--accent); color:var(--accent); }
    .table { width:100%; border-collapse:collapse; font-size:13px; }
    .table th,.table td { border-bottom:1px solid var(--line); padding:.4rem; text-align:left; vertical-align:top; }
    input,select,textarea { width:100%; padding:.45rem; border-radius:6px; border:1px solid #353d52; background:#0f121a; color:var(--text); }
    textarea { min-height:100px; }
    .grid { display:grid; gap:.7rem; grid-template-columns:1fr 1fr; }
    label { display:block; margin:.35rem 0 .2rem; color:var(--muted); }
    .pill { display:inline-block; padding:.15rem .45rem; border-radius:999px; font-size:12px; border:1px solid #353d52; }
    .pill.ok { color:#7ee787; border-color:#2d6a3f; background:#122018; }
    .pill.warn { color:#ffd479; border-color:#7b5a14; background:#221b0c; }
    .pill.bad { color:#ff8d8d; border-color:#8a2d2d; background:#2a1212; }
    .error-box { border:1px solid #8a2d2d; background:#2a1212; color:#ffb3b3; padding:.55rem .7rem; border-radius:6px; }
    pre { white-space:pre-wrap; margin:0; max-width:520px; overflow:auto; font-size:12px; }
    .compact th,.compact td { font-size:12px; padding:.35rem; }
    .grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:.7rem; align-items:end; }
    .grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:.7rem; align-items:end; margin-bottom:.8rem; }
    .actions-inline { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; margin:.5rem 0 .9rem; }
    .actions-inline form { margin:0; }
    .timeline-item { border:1px solid var(--line); background:#111522; border-radius:6px; padding:.45rem .55rem; margin-bottom:.45rem; }
    .timeline-item small { color:var(--muted); }
  </style>
</head>
<body>
  <aside>
    <h2>Admin</h2>
    <a href="/admin">Dashboard</a>
    <a href="/admin/brands">Brands</a>
    <a href="/admin/categories">Categories</a>
    <a href="/admin/products">Products</a>
    <a href="/admin/orders">Orders</a>
    <a href="/admin/suppliers">Leverantörer</a>
    <a href="/admin/import-profiles">Importprofiler</a>
    <a href="/admin/import-runs">Importkörningar</a>
    <a href="/">Till storefront</a>
  </aside>
  <main><?= $content ?? '' ?></main>
</body>
</html>
