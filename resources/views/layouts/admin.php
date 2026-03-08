<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'A-Racing Admin', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root { --bg:#0c0c10; --sidebar:#13131a; --card:#1a1a22; --text:#f5f5f7; --muted:#9ea0ac; --accent:#e10600; }
    body { margin:0; font-family:Inter,Segoe UI,Arial,sans-serif; background:var(--bg); color:var(--text); display:grid; grid-template-columns:230px 1fr; min-height:100vh; }
    aside { background:var(--sidebar); padding:1rem; border-right:1px solid #23232f; }
    aside a { display:block; color:var(--text); text-decoration:none; margin:.7rem 0; }
    aside a:hover { color:var(--accent); }
    main { padding:1.2rem; }
    .card { background:var(--card); border:1px solid #2b2b36; border-radius:10px; padding:1rem; }
    h1 span { color:var(--accent); }
    small { color:var(--muted); }
  </style>
</head>
<body>
  <aside>
    <h2>Admin</h2>
    <a href="/admin">Dashboard</a>
    <a href="/">Till storefront</a>
  </aside>
  <main>
    <?= $content ?? '' ?>
  </main>
</body>
</html>
