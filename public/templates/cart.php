<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

// Úspešne odoslaná objednávka — PRG vzor
$objednane = (int)($_GET['objednane'] ?? 0);

$cart    = $_SESSION['cart'] ?? null;
$items   = $cart['items'] ?? [];
$orderOk = false;
$orderId = null;
$error   = null;

// Calculate totals
$subtotal    = array_reduce($items, fn($s, $i) => $s + $i['price'] * $i['qty'], 0);
$delivery    = count($items) > 0 ? 1.99 : 0;
$total       = $subtotal + $delivery;
$itemCount   = array_sum(array_column($items, 'qty'));
$navCount    = $itemCount;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    if (!Auth::check()) {
        $error = 'Pre dokončenie objednávky sa musíte prihlásiť.';

    } elseif (!Auth::isCustomer()) {
        $error = 'Objednavku moze vytvorit iba zakaznicky ucet.';

    } elseif (empty($items)) {
        $error = 'Košík je prázdny.';

    } else {
        try {
            $db   = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT id, adresa FROM zakaznici WHERE user_id = :uid");
            $stmt->execute(['uid' => Auth::id()]);
            $zakaznikRow = $stmt->fetch();

            if (!$zakaznikRow) {
                $error = 'Váš zákaznícky profil nebol nájdený.';
            } else {
                $platbaRaw       = $_POST['payment'] ?? '';
                $platba          = in_array($platbaRaw, ['karta', 'hotovost', 'apple_pay'], true) ? $platbaRaw : 'karta';
                $adresaDorucenia = trim($_POST['delivery_address'] ?? $zakaznikRow->adresa ?? '');
                $poznamka        = trim($_POST['note'] ?? '');
                $restauraciaId   = (int)($cart['restauracia_id'] ?? 0);
                if ($restauraciaId <= 0) {
                    $firstItemKey = array_key_first($items);
                    $restauraciaId = (int)($items[$firstItemKey]['restauracia_id'] ?? 0);
                }

                if ($restauraciaId <= 0) {
                    $error = 'Objednavka nema vybranu restauraciu. Pridajte prosim polozku znova.';
                } else {
                    $polozky = implode(', ', array_map(
                        static fn(array $i): string => trim(($i['name'] ?? 'Polozka') . ' x' . (int)($i['qty'] ?? 1)),
                        array_values($items)
                    ));

                    $db->beginTransaction();

                    $ins = $db->prepare(
                        "INSERT INTO objednavky (zakaznik_id, restauracia_id, polozky, suma, platba, adresa_dorucenia, poznamka)
                         VALUES (:zid, :rid, :polozky, :suma, :platba, :adresa, :poznamka)"
                    );
                    $ins->execute([
                        'zid'     => $zakaznikRow->id,
                        'rid'     => $restauraciaId,
                        'polozky' => $polozky,
                        'suma'    => $total,
                        'platba'  => $platba,
                        'adresa'  => $adresaDorucenia,
                        'poznamka'=> $poznamka,
                    ]);

                    $orderId = (int)$db->lastInsertId();

                    $insItem = $db->prepare(
                        "INSERT INTO polozky_objednavky (objednavka_id, produkt_id, nazov, mnozstvo, cena)
                         VALUES (:oid, :pid, :nazov, :mnozstvo, :cena)"
                    );
                    foreach ($items as $item) {
                        $insItem->execute([
                            'oid'     => $orderId,
                            'pid'     => (int)($item['id'] ?? 0) ?: null,
                            'nazov'   => $item['name'] ?? 'Položka',
                            'mnozstvo'=> (int)($item['qty'] ?? 1),
                            'cena'    => (float)($item['price'] ?? 0),
                        ]);
                    }

                    $db->commit();
                    $_SESSION['cart'] = null;
                    // PRG — zabraňuje duplikátnej objednávke pri F5
                    Redirect::redirect('cart.php?objednane=' . $orderId);
                }
            }
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            Helper::log("cart.php order ERROR: " . $e->getMessage());
            $error = 'Nastala chyba pri ukladaní objednávky. Skúste prosím znova.';
        }
    }
}

// Recalculate after order
$subtotal  = array_reduce($items, fn($s, $i) => $s + $i['price'] * $i['qty'], 0);
$delivery  = count($items) > 0 ? 1.99 : 0;
$total     = $subtotal + $delivery;
$itemCount = array_sum(array_column($items, 'qty'));
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Košík – QuickBite</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="restaurants.php" class="navbar-logo">
      <div class="logo-icon">🍕</div>
      <span class="logo-text">Quick<span>Bite</span></span>
    </a>
    <ul class="navbar-links">
      <li><a href="restaurants.php">Reštaurácie</a></li>
      <?php if (Auth::check() && Auth::isCustomer()): ?>
      <li><a href="moje-objednavky.php">Moje objednávky</a></li>
      <li><a href="profil.php">Môj profil</a></li>
      <?php endif; ?>
    </ul>
    <div class="navbar-cta">
      <a href="cart.php" class="nav-cart btn btn-ghost" title="Košík" style="color:var(--primary);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
        <span class="nav-cart-count"><?= $navCount > 0 ? $navCount : '' ?></span>
      </a>
      <?php if (Auth::check()): ?>
        <div class="navbar-user">
          <span class="navbar-user-avatar"><?= Helper::h(Auth::initials()) ?></span>
          <span class="navbar-user-name"><?= Helper::h(Auth::meno()) ?></span>
          <a href="logout.php" class="btn btn-ghost btn-sm">Odhlásiť</a>
        </div>
      <?php else: ?>
        <a href="login.php?redirect=cart.php" class="btn btn-ghost">Prihlásiť sa</a>
        <a href="register.php" class="btn btn-primary">Registrovať sa</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="page-wrap">
  <div class="page-hero-sm">
    <div class="page-hero-sm-inner">
      <h1>Váš košík 🛒</h1>
      <?php if ($cart && isset($cart['restauracia_id'])): ?>
        <p><a href="restaurant-detail.php?id=<?= (int)$cart['restauracia_id'] ?>">← Späť do <?= Helper::h($cart['restauracia_nazov'] ?? 'reštaurácie') ?></a></p>
      <?php else: ?>
        <p><a href="restaurants.php">← Späť na reštaurácie</a></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($error): ?>
    <div style="max-width:900px;margin:0 auto 1rem;padding:0 1.5rem;">
      <div class="alert alert-danger" style="padding:.75rem 1rem;border-radius:10px;background:rgba(239,68,68,.12);color:#dc2626;font-size:.875rem;">
        <?= Helper::h($error) ?>
        <?php if (!Auth::check()): ?>
          <a href="login.php?redirect=cart.php" style="margin-left:.5rem;color:#dc2626;font-weight:600;">Prihlásiť sa →</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <!-- Empty cart -->
    <div style="max-width:500px;margin:4rem auto;text-align:center;padding:0 1.5rem;">
      <div style="font-size:4rem;margin-bottom:1.5rem;">🛒</div>
      <h2 style="margin-bottom:.75rem;">Košík je prázdny</h2>
      <p style="margin-bottom:2rem;">Pridajte si jedlá z niektorej reštaurácie.</p>
      <a href="restaurants.php" class="btn btn-primary btn-lg" style="justify-content:center;">Prezrieť reštaurácie</a>
    </div>
  <?php else: ?>

  <div class="cart-page-wrap">
    <div class="cart-page-grid">

      <!-- ── Cart items ── -->
      <div>
        <div class="cart-items-card">
          <div class="cart-items-header">
            <h3><?= Helper::h($cart['restauracia_nazov'] ?? 'Košík') ?></h3>
            <span style="font-size:.8rem;color:var(--gray-500);"><?= $itemCount ?> položiek</span>
          </div>

          <?php foreach ($items as $item): ?>
          <div class="cart-item-row" data-id="<?= Helper::h($item['id']) ?>">
            <div class="cart-item-emoji" style="background:var(--primary-light);">🍽️</div>
            <div class="cart-item-details">
              <div class="cart-item-title"><?= Helper::h($item['name']) ?></div>
              <div class="cart-item-sub"><?= number_format((float)$item['price'], 2, ',', ' ') ?> € / ks</div>
            </div>
            <div class="qty-control">
              <button class="qty-btn" onclick="cartUpdate('<?= Helper::h($item['id']) ?>', -1)">−</button>
              <span class="qty-num" id="qty-<?= Helper::h($item['id']) ?>"><?= (int)$item['qty'] ?></span>
              <button class="qty-btn" onclick="cartUpdate('<?= Helper::h($item['id']) ?>', 1)">+</button>
            </div>
            <div class="cart-item-price" id="price-<?= Helper::h($item['id']) ?>">
              <?= number_format((float)$item['price'] * $item['qty'], 2, ',', ' ') ?> €
            </div>
            <button class="cart-remove-btn" onclick="cartRemove('<?= Helper::h($item['id']) ?>')" title="Odstrániť">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Note -->
        <div class="card mt-3">
          <div class="card-body">
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label">📝 Poznámka pre reštauráciu</label>
              <textarea class="form-control" name="note" form="orderForm" placeholder="Napr. bez cibule, extra omáčka..." style="min-height:80px;"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Order summary ── -->
      <div>
        <form method="POST" id="orderForm">
          <input type="hidden" name="place_order" value="1">

          <div class="order-summary-card">
            <div class="order-summary-header">
              <h3>Súhrn objednávky</h3>
            </div>
            <div class="order-summary-body">

              <!-- Price breakdown -->
              <div class="summary-row">
                <span>Medzisúčet (<?= $itemCount ?> položiek)</span>
                <span id="summarySubtotal"><?= number_format($subtotal, 2, ',', ' ') ?> €</span>
              </div>
              <div class="summary-row">
                <span>Doručenie</span>
                <span>1,99 €</span>
              </div>
              <div class="summary-row total">
                <span>Celkom</span>
                <span id="summaryTotal"><?= number_format($total, 2, ',', ' ') ?> €</span>
              </div>

              <!-- Delivery address -->
              <?php
                $zakaznikAdresa = '';
                if (Auth::check()) {
                    try {
                        $db2 = (new Database())->getConnection();
                        $s2 = $db2->prepare("SELECT adresa FROM zakaznici WHERE user_id = :uid");
                        $s2->execute(['uid' => Auth::id()]);
                        $zRow = $s2->fetch();
                        $zakaznikAdresa = $zRow ? ($zRow->adresa ?? '') : '';
                    } catch (PDOException $e) {}
                }
              ?>
              <div style="margin-top:1.25rem;margin-bottom:1rem;">
                <label class="form-label" style="margin-bottom:.6rem;">📍 Adresa doručenia</label>
                <input type="text" name="delivery_address" class="form-control"
                  placeholder="Zadajte adresu doručenia..."
                  value="<?= Helper::h($zakaznikAdresa) ?>">
              </div>

              <!-- Payment method -->
              <div style="margin-bottom:1.25rem;">
                <div class="form-label" style="margin-bottom:.6rem;">💳 Spôsob platby</div>
                <div class="payment-option selected" data-value="karta">
                  <input type="radio" name="payment" value="karta" checked>
                  <span class="payment-option-label">Kreditná / Debetná karta</span>
                  <span class="payment-option-icon">💳</span>
                </div>
                <div class="payment-option" data-value="apple_pay">
                  <input type="radio" name="payment" value="apple_pay">
                  <span class="payment-option-label">Apple Pay</span>
                  <span class="payment-option-icon">🍎</span>
                </div>
                <div class="payment-option" data-value="hotovost">
                  <input type="radio" name="payment" value="hotovost">
                  <span class="payment-option-label">Hotovosť pri doručení</span>
                  <span class="payment-option-icon">💵</span>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100"
                      style="justify-content:center;padding:.8rem;font-size:.95rem;">
                🛵 Objednať za <?= number_format($total, 2, ',', ' ') ?> €
              </button>
              <?php if (!Auth::check()): ?>
              <p style="font-size:.75rem;text-align:center;margin-top:.6rem;color:var(--warning);">
                ⚠️ Pre dokončenie objednávky sa musíte <a href="login.php">prihlásiť</a>.
              </p>
              <?php endif; ?>
            </div>
          </div>
        </form>

        <div class="card mt-3" style="border:2px solid var(--primary-light);">
          <div class="card-body" style="display:flex;align-items:center;gap:1rem;padding:1rem 1.25rem;">
            <div style="font-size:2rem;">⏱️</div>
            <div>
              <div style="font-weight:700;color:var(--gray-900);font-size:.9rem;">Odhadovaný čas doručenia</div>
              <div style="font-size:1.2rem;font-weight:800;color:var(--primary);">25–35 minút</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php endif; ?>
</main>

<!-- Order success modal (PRG – zobrazí sa po redirect s ?objednane=ID) -->
<?php if ($objednane > 0): ?>
<div class="modal-overlay" id="orderSuccessModal" style="display:flex;">
  <div class="modal" style="max-width:420px;text-align:center;">
    <div class="modal-body" style="padding:2.5rem 2rem;">
      <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
      <h3 style="color:var(--gray-900);margin-bottom:.75rem;">Objednávka prijatá!</h3>
      <p style="margin-bottom:1.5rem;">Vaša objednávka č. <strong>#<?= $objednane ?></strong> bola úspešne odoslaná. Čas doručenia: <strong>25–35 min</strong>.</p>
      <a href="moje-objednavky.php" class="btn btn-primary" style="justify-content:center;width:100%;padding:.75rem;margin-bottom:.6rem;">
        Zobraziť moje objednávky
      </a>
      <a href="restaurants.php" class="btn btn-ghost" style="justify-content:center;width:100%;padding:.75rem;">
        Objednať znova 🍕
      </a>
    </div>
  </div>
</div>
<script>localStorage.removeItem('qb_cart');</script>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/cookie-banner.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
// Cart update (quantity)
function cartUpdate(id, delta) {
  const qtyEl   = document.getElementById('qty-' + id);
  const priceEl = document.getElementById('price-' + id);
  if (!qtyEl) return;

  let qty = parseInt(qtyEl.textContent) + delta;
  if (qty < 1) { cartRemove(id); return; }
  qtyEl.textContent = qty;

  // Get unit price from sub text
  const row      = document.querySelector(`.cart-item-row[data-id="${id}"]`);
  const subText  = row ? row.querySelector('.cart-item-sub').textContent : '';
  const match    = subText.match(/([\d,]+)/);
  const unitPrice = match ? parseFloat(match[1].replace(',', '.')) : 0;
  if (priceEl) priceEl.textContent = (unitPrice * qty).toFixed(2).replace('.', ',') + ' €';

  // Sync to session
  const fd = new FormData();
  fd.append('action', 'update');
  fd.append('id', id);
  fd.append('qty', qty);
  fetch('cart-action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(() => { location.reload(); })
    .catch(() => location.reload());
}

function cartRemove(id) {
  const row = document.querySelector(`.cart-item-row[data-id="${id}"]`);
  if (row) {
    row.style.transition = 'opacity .3s, transform .3s';
    row.style.opacity = '0';
    row.style.transform = 'translateX(20px)';
  }

  const fd = new FormData();
  fd.append('action', 'remove');
  fd.append('id', id);
  fetch('cart-action.php', { method: 'POST', body: fd })
    .then(() => location.reload())
    .catch(() => location.reload());
}

// Payment selection
document.querySelectorAll('.payment-option').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
    opt.querySelector('input').checked = true;
  });
});
</script>
</body>
</html>
