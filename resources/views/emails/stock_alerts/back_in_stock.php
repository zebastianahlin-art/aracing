<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <title>Produkt tillbaka i lager</title>
</head>
<body style="font-family:Arial,sans-serif;color:#111;line-height:1.45;">
  <h2>Din bevakade produkt finns nu i lager</h2>
  <p><strong><?= htmlspecialchars((string) $productName, ENT_QUOTES, 'UTF-8') ?></strong> är nu köpbar igen hos A-Racing.</p>
  <p><a href="<?= htmlspecialchars((string) $productUrl, ENT_QUOTES, 'UTF-8') ?>">Gå till produkten och beställ</a></p>
  <p>Detta är ett automatiskt meddelande baserat på din produktbevakning.</p>
</body>
</html>
