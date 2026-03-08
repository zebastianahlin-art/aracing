<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'A-Racing Storefront', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#0f0f12; --surface:#17171b; --text:#f3f3f4; --muted:#a0a0ab; --accent:#e10600; --line:#23232b; }
    body { margin:0; font-family:Inter,Segoe UI,Arial,sans-serif; background:var(--bg); color:var(--text); }
    header, main, footer { max-width:1100px; margin:0 auto; padding:1rem; }
    nav a { color:var(--text); text-decoration:none; margin-right:1rem; }
    nav a:hover { color:var(--accent); }
    .panel { background:var(--surface); border:1px solid var(--line); border-radius:10px; padding:1rem; }
    .accent { color:var(--accent); }
    .product-grid { display:grid; gap:.8rem; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); }
    .product-card { border:1px solid var(--line); border-radius:8px; padding:.7rem; background:#121217; }
    .muted { color:var(--muted); font-size:13px; }
    .image-strip { display:grid; gap:.6rem; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); margin-top:1rem; }
    .image-item { border:1px solid var(--line); padding:.45rem; border-radius:8px; }
    img { max-width:100%; border-radius:6px; }
  </style>
</head>
<body>
<header>
  <h1>A-<span class="accent">Racing</span></h1>
  <nav>
    <a href="/">Start</a>
    <a href="/cart">Kundvagn</a>
    <a href="/checkout">Checkout</a>
    <a href="/admin">Admin</a>
  </nav>
</header>
<main><?= $content ?? '' ?></main>
<footer><small>Serverrenderad storefront - katalog kopplad till databas</small></footer>
</body>
</html>
