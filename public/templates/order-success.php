<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    Redirect::redirect('restaurants.php');
}

// Load order and verify it belongs to the logged-in customer
try {
    $db   = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT o.id, o.suma, o.platba, o.stav, o.adresa_dorucenia, o.poznamka, o.created_at,
               r.nazov AS restauracia_nazov,
               CONCAT(z.meno, ' ', z.priezvisko) AS zakaznik_meno
        FROM objednavky o
        JOIN restauracie r ON r.id = o.restauracia_id
        JOIN zakaznici z   ON z.id  = o.zakaznik_id
        WHERE o.id = :id AND z.user_id = :uid
    ");
    $stmt->execute(['id' => $orderId, 'uid' => Auth::id()]);
    $order = $stmt->fetch();
} catch (PDOException $e) {
    Helper::log('order-success.php ERROR: ' . $e->getMessage());
    $order = null;
}

if (!$order) {
    Redirect::redirect('restaurants.php');
}

// Load order items
$orderItems = [];
try {
    $iStmt = $db->prepare("SELECT nazov, mnozstvo, cena FROM polozky_objednavky WHERE objednavka_id = :oid ORDER BY id");
    $iStmt->execute(['oid' => $orderId]);
    $orderItems = $iStmt->fetchAll();
} catch (PDOException $e) {}

$platbaLabels = ['karta' => '💳 Kreditná karta', 'hotovost' => '💵 Hotovosť', 'apple_pay' => '🍎 Apple Pay'];
$cartCount = 0;
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Objednávka potvrdená – QuickBite</title>
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
      <li><a href="moje-objednavky.php">Moje objednávky</a></li>
      <li><a href="profil.php">Môj profil</a></li>
    </ul>
    <div class="navbar-cta">
      <a href="cart.php" class="nav-cart btn btn-ghost" title="Košík" style="color:var(--primary);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
        <span class="nav-cart-count"></span>
      </a>
      <div class="navbar-user">
        <span class="navbar-user-avatar"><?= Helper::h(Auth::initials()) ?></span>
        <span class="navbar-user-name"><?= Helper::h(Auth::meno()) ?></span>
        <a href="logout.php" class="btn btn-ghost btn-sm">Odhlásiť</a>
      </div>
    </div>
  </div>
</nav>

<main class="page-wrap">

  <!-- Thank-you hero -->
  <div style="max-width:620px;margin:3rem auto 0;padding:0 1.5rem;text-align:center;">

    <div style="font-size:5rem;margin-bottom:1rem;animation:popIn .5s ease;">🎉</div>
    <h1 style="font-size:1.75rem;font-weight:800;color:var(--gray-900);margin-bottom:.6rem;">
      Ďakujeme za objednávku!
    </h1>
    <p style="font-size:1.05rem;color:var(--gray-500);margin-bottom:.4rem;">
      Vaše jedlo sa už pripravuje.
    </p>
    <p style="font-size:.9rem;color:var(--gray-400);">
      Odhadovaný čas doručenia: <strong style="color:var(--primary);">25–35 minút</strong>
    </p>

    <!-- Order summary card -->
    <div class="card" style="margin-top:2rem;text-align:left;">
      <div class="card-body" style="padding:1.5rem;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
          <div>
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-400);margin-bottom:.25rem;">Číslo objednávky</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--primary);">#<?= $order->id ?></div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;color:var(--gray-400);margin-bottom:.25rem;">Stav</div>
            <?= Helper::stavBadge($order->stav) ?>
          </div>
        </div>

        <div style="border-top:2px solid var(--gray-100);padding-top:1rem;margin-bottom:1rem;">
          <div style="font-size:.8rem;font-weight:700;color:var(--gray-600);margin-bottom:.6rem;text-transform:uppercase;letter-spacing:.06em;">
            🍽️ <?= Helper::h($order->restauracia_nazov) ?>
          </div>
          <?php foreach ($orderItems as $item): ?>
          <div style="display:flex;justify-content:space-between;padding:.35rem 0;font-size:.88rem;color:var(--gray-700);border-bottom:1px solid var(--gray-100);">
            <span><?= Helper::h($item->nazov) ?> × <?= (int)$item->mnozstvo ?></span>
            <span style="font-weight:600;"><?= Helper::eur((float)$item->cena * (int)$item->mnozstvo) ?></span>
          </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;padding:.35rem 0;font-size:.85rem;color:var(--gray-500);">
            <span>Doručenie</span>
            <span>1,99 €</span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:.5rem 0;font-size:1rem;font-weight:700;color:var(--gray-900);border-top:2px solid var(--gray-200);margin-top:.25rem;">
            <span>Celkom</span>
            <span style="color:var(--primary);"><?= Helper::eur((float)$order->suma) ?></span>
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:.45rem;font-size:.85rem;color:var(--gray-600);">
          <div><span style="font-weight:600;">📍 Adresa:</span> <?= Helper::h($order->adresa_dorucenia ?: '—') ?></div>
          <div><span style="font-weight:600;">💳 Platba:</span> <?= Helper::h($platbaLabels[$order->platba] ?? $order->platba) ?></div>
          <?php if ($order->poznamka): ?>
          <div><span style="font-weight:600;">📝 Poznámka:</span> <?= Helper::h($order->poznamka) ?></div>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- Action buttons -->
    <div style="display:flex;flex-direction:column;gap:.75rem;margin-top:1.75rem;">
      <a href="moje-objednavky.php" class="btn btn-primary" style="justify-content:center;padding:.8rem;font-size:.95rem;">
        📋 Zobraziť moje objednávky
      </a>
      <a href="restaurants.php" class="btn btn-ghost" style="justify-content:center;padding:.8rem;font-size:.95rem;">
        🍕 Objednať znova
      </a>
    </div>

    <!-- Auto-redirect countdown -->
    <p style="margin-top:1.5rem;font-size:.78rem;color:var(--gray-400);">
      Budete automaticky presmerovaný na reštaurácie o <strong id="countdown">10</strong> sekúnd.
    </p>

  </div>

</main>

<?php require_once __DIR__ . '/partials/cookie-banner.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
// Clear cart from localStorage — order is placed
localStorage.removeItem('qb_cart');

// Auto-redirect countdown
let secs = 10;
const el = document.getElementById('countdown');
const timer = setInterval(() => {
  secs--;
  if (el) el.textContent = secs;
  if (secs <= 0) {
    clearInterval(timer);
    window.location.href = 'restaurants.php';
  }
}, 1000);
</script>
<style>
@keyframes popIn {
  0%   { transform: scale(.4); opacity: 0; }
  70%  { transform: scale(1.15); }
  100% { transform: scale(1);   opacity: 1; }
}
</style>
</body>
</html>
