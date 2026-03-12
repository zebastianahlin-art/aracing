<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<?php $seo = is_array($seo ?? null) ? $seo : []; ?>
  <title><?= htmlspecialchars((string) ($seo['title'] ?? $title ?? 'A-Racing Storefront'), ENT_QUOTES, 'UTF-8') ?></title>
  <?php if (!empty($seo['description'])): ?>
    <meta name="description" content="<?= htmlspecialchars((string) $seo['description'], ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <?php if (!empty($seo['robots'])): ?>
    <meta name="robots" content="<?= htmlspecialchars((string) $seo['robots'], ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <?php if (!empty($seo['canonical'])): ?>
    <link rel="canonical" href="<?= htmlspecialchars((string) $seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <style>
    :root { --bg:#0f0f12; --surface:#17171b; --text:#f3f3f4; --muted:#a0a0ab; --accent:#e10600; --line:#23232b; }
    body { margin:0; font-family:Inter,Segoe UI,Arial,sans-serif; background:var(--bg); color:var(--text); }
    header, main, footer { max-width:1100px; margin:0 auto; padding:1rem; }
    nav a { color:var(--text); text-decoration:none; margin-right:1rem; }
    nav a:hover { color:var(--accent); }
    .top-links { margin-top:.55rem; display:flex; gap:.9rem; flex-wrap:wrap; }
    .top-links a { color:var(--muted); font-size:13px; }
    .panel { background:var(--surface); border:1px solid var(--line); border-radius:10px; padding:1rem; }
    .accent { color:var(--accent); }
    .product-grid { display:grid; gap:.8rem; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); }
    .product-card { border:1px solid var(--line); border-radius:8px; padding:.7rem; background:#121217; }
    .product-thumb { width:100%; aspect-ratio:4/3; object-fit:cover; border:1px solid #2d2f38; margin-bottom:.6rem; background:#0e0e12; }
    .image-placeholder { width:100%; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center; border:1px dashed #31333b; border-radius:6px; color:var(--muted); margin-bottom:.6rem; font-size:12px; }
    .product-hero { width:100%; max-width:560px; aspect-ratio:4/3; object-fit:cover; border:1px solid #2f3240; background:#0f1014; }
    .thumb-strip { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.7rem; }
    .thumb-strip img { width:110px; height:82px; object-fit:cover; border:1px solid #2f3240; }
    .muted { color:var(--muted); font-size:13px; }
    .trust-grid { display:grid; gap:.6rem; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); margin:.8rem 0; }
    .trust-item { border:1px solid var(--line); border-radius:8px; padding:.7rem; background:#131319; }
    .image-strip { display:grid; gap:.6rem; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); margin-top:1rem; }
    .image-item { border:1px solid var(--line); padding:.45rem; border-radius:8px; }
    img { max-width:100%; border-radius:6px; }

    .table { width:100%; border-collapse:collapse; margin:.7rem 0; }
    .table th,.table td { border-bottom:1px solid var(--line); padding:.45rem; text-align:left; vertical-align:top; }
    input,textarea,select { width:100%; padding:.45rem; border-radius:6px; border:1px solid #353d52; background:#0f121a; color:var(--text); }
    .filters-grid { display:grid; gap:.6rem; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); }
    .btn-primary,.btn-secondary,.btn-danger { display:inline-block; padding:.4rem .65rem; border-radius:6px; text-decoration:none; border:1px solid transparent; cursor:pointer; }
    .btn-primary { background:var(--accent); color:#fff; border-color:#a50000; }
    .btn-secondary { background:#222838; color:var(--text); border-color:#384055; }
    .btn-danger { background:#2b1414; color:#ffb3b3; border-color:#703030; }
    .inline-form { display:flex; align-items:end; gap:.45rem; margin:.6rem 0; }
    .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    .ok-msg { color:#7ee787; }
    .err-msg { color:#ff9c9c; }
    .pill { display:inline-block; padding:.2rem .45rem; border-radius:999px; font-size:12px; border:1px solid #384055; background:#1a2030; color:#e6e8ef; }
    .pill.bad { background:#2b1414; border-color:#703030; color:#ffb3b3; }
    .pill.ok { background:#173021; border-color:#2f7046; color:#9de9bb; }
    .footer-links { display:flex; gap:.8rem; flex-wrap:wrap; margin-top:.4rem; }
    .ymm-box { margin-top:.8rem; border:1px solid var(--line); border-radius:8px; padding:.7rem; background:#131722; }
    .ymm-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:.5rem; align-items:end; }
    .fitment-banner { margin-top:.55rem; padding:.55rem .65rem; border-radius:8px; border:1px solid #2a3d31; background:#111c15; }
    .fitment-actions { margin-top:.45rem; display:flex; gap:.45rem; flex-wrap:wrap; }
    @media (max-width: 800px) { .grid-2 { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<?php $infoPages = $infoPages ?? []; ?>
<header>
  <h1>A-<span class="accent">Racing</span></h1>
  <nav>
    <a href="/">Start</a>
    <a href="/search">Sök</a>
    <a href="/shop-by-vehicle">Handla till bil</a>
    <a href="/compare">Jämför</a>
    <a href="/cart">Kundvagn</a>
    <a href="/checkout">Checkout</a>
    <a href="/contact">Kontakt</a>
    <a href="/order-status">Orderstatus</a>
    <?php if (!empty($_SESSION['customer_user_id'])): ?>
      <a href="/account">Mina sidor</a>
      <form method="post" action="/logout" style="display:inline;">
        <button type="submit" class="btn-secondary" style="padding:.2rem .45rem;">Logga ut</button>
      </form>
    <?php else: ?>
      <a href="/login">Logga in</a>
      <a href="/register">Registrera</a>
    <?php endif; ?>
    <a href="/admin">Admin</a>
  </nav>

  <?php $fitment = is_array($fitment ?? null) ? $fitment : []; ?>
  <?php $selectedVehicle = is_array($fitment['selected_vehicle'] ?? null) ? $fitment['selected_vehicle'] : null; ?>
  <?php $fitmentStorefront = is_array($fitmentStorefront ?? null) ? $fitmentStorefront : []; ?>
  <?php $vehicleNavigation = is_array($vehicleNavigation ?? null) ? $vehicleNavigation : []; ?>
  <section id="ymm-selector" class="ymm-box">
    <form method="post" action="/fitment/select" class="ymm-grid">
      <input type="hidden" name="return_to" value="<?= htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? '/search'), ENT_QUOTES, 'UTF-8') ?>">
      <div>
        <label>Make</label>
        <input name="make" list="ymm-makes" value="<?= htmlspecialchars((string) ($selectedVehicle['make'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="t.ex. BMW" required>
        <datalist id="ymm-makes"><?php foreach (($fitment['makes'] ?? []) as $value): ?><option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
      </div>
      <div>
        <label>Modell</label>
        <input name="model" list="ymm-models" value="<?= htmlspecialchars((string) ($selectedVehicle['model'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
        <datalist id="ymm-models"><?php foreach (($fitment['models'] ?? []) as $value): ?><option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
      </div>
      <div>
        <label>Generation</label>
        <input name="generation" list="ymm-generations" value="<?= htmlspecialchars((string) ($selectedVehicle['generation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <datalist id="ymm-generations"><?php foreach (($fitment['generations'] ?? []) as $value): ?><option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
      </div>
      <div>
        <label>Motor</label>
        <input name="engine" list="ymm-engines" value="<?= htmlspecialchars((string) ($selectedVehicle['engine'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <datalist id="ymm-engines"><?php foreach (($fitment['engines'] ?? []) as $value): ?><option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?></datalist>
      </div>
      <button class="btn-primary" type="submit">Välj bil</button>
    </form>
    <?php if (($fitmentStorefront['has_active_vehicle'] ?? false) === true): ?>
      <div class="fitment-banner">
        <strong>Du handlar för <?= htmlspecialchars((string) ($fitmentStorefront['active_vehicle_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        <span class="muted" style="margin-left:.35rem;">Byt bil i fälten ovan eller rensa för att visa hela katalogen.</span>
        <?php if (!empty($vehicleNavigation['entry_url'])): ?>
          <div class="fitment-actions">
            <a class="btn-primary" href="<?= htmlspecialchars((string) $vehicleNavigation['entry_url'], ENT_QUOTES, 'UTF-8') ?>">Handla till vald bil</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($selectedVehicle !== null): ?>
      <p style="margin:.6rem 0 .3rem;"><strong>Vald bil:</strong> <?= htmlspecialchars((string) ($selectedVehicle['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      <form method="post" action="/fitment/clear" style="display:inline;">
        <input type="hidden" name="return_to" value="<?= htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? '/search'), ENT_QUOTES, 'UTF-8') ?>">
        <button class="btn-secondary" type="submit">Rensa vald bil</button>
      </form>
      <?php if (!empty($_SESSION['customer_user_id'])): ?>
        <form method="post" action="/account/vehicles/save-current" style="display:inline; margin-left:.35rem;">
          <input type="hidden" name="back_to" value="<?= htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? '/search'), ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn-secondary" type="submit">Spara vald bil</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($selectedVehicle === null && !empty($_SESSION['customer_user_id']) && (($fitmentStorefront['has_saved_vehicles'] ?? false) === true)): ?>
      <p class="muted" style="margin:.55rem 0 0;">Välj snabbt från <a href="/account/vehicles">Mina bilar</a> (<?= (int) ($fitmentStorefront['saved_vehicles_count'] ?? 0) ?> sparade).</p>
    <?php endif; ?>
    <?php if (!empty($fitmentNotice ?? '')): ?><p class="ok-msg" style="margin:.5rem 0 0;"><?= htmlspecialchars((string) $fitmentNotice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  </section>

  <?php if ($infoPages !== []): ?>
    <div class="top-links" aria-label="Informationssidor">
      <?php foreach ($infoPages as $pageLink): ?>
        <a href="<?= htmlspecialchars((string) $pageLink['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $pageLink['label'], ENT_QUOTES, 'UTF-8') ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</header>
<main><?= $content ?? '' ?></main>
<footer>
  <small>Serverrenderad storefront - katalog kopplad till databas</small>


  <?php if ($infoPages !== []): ?>
    <div class="footer-links">
      <?php foreach ($infoPages as $pageLink): ?>
        <a href="<?= htmlspecialchars((string) $pageLink['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $pageLink['label'], ENT_QUOTES, 'UTF-8') ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</footer>
</body>
</html>
