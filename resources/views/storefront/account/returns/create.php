<?php
ob_start();
$order = $context['order'] ?? null;
$orderItems = $context['items'] ?? [];
$reasonCodes = $context['reason_codes'] ?? [];
$reasonLabels = $reasonLabels ?? [];
?>
<section class="panel">
  <h2>Skapa returförfrågan</h2>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <?php if ($order !== null): ?>
    <p>Order: <strong><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <form method="post" action="/account/orders/<?= (int) $order['id'] ?>/returns">
      <table class="table">
        <thead><tr><th>Välj</th><th>Produkt</th><th>SKU</th><th>Beställt</th><th>Returantal</th><th>Radorsak</th><th>Kommentar</th></tr></thead>
        <tbody>
        <?php foreach ($orderItems as $item): ?>
          <tr>
            <td><input type="checkbox" name="items[<?= (int) $item['id'] ?>][selected]" value="1"></td>
            <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $item['quantity'] ?></td>
            <td><input type="number" min="1" max="<?= (int) $item['quantity'] ?>" name="items[<?= (int) $item['id'] ?>][quantity]" value="1"></td>
            <td>
              <select name="items[<?= (int) $item['id'] ?>][reason_code]">
                <option value="">-</option>
                <?php foreach ($reasonCodes as $reasonCode): ?>
                  <option value="<?= htmlspecialchars((string) $reasonCode, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($reasonLabels[$reasonCode] ?? $reasonCode), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="text" name="items[<?= (int) $item['id'] ?>][comment]" value=""></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <label for="reason_code">Övergripande anledning</label>
      <select id="reason_code" name="reason_code">
        <option value="">Välj anledning</option>
        <?php foreach ($reasonCodes as $reasonCode): ?>
          <option value="<?= htmlspecialchars((string) $reasonCode, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($reasonLabels[$reasonCode] ?? $reasonCode), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>

      <label for="customer_comment">Kommentar</label>
      <textarea id="customer_comment" name="customer_comment" placeholder="Beskriv kort varför du vill returnera."></textarea>
      <button type="submit" class="btn-primary">Skapa returförfrågan</button>
    </form>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Skapa retur | A-Racing';
require __DIR__ . '/../../../layouts/storefront.php';
