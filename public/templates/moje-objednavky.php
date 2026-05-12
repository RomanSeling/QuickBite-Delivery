<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

if (!Auth::check() || !Auth::isCustomer()) {
    Redirect::redirect('login.php?redirect=moje-objednavky.php');
}

$objednavky      = [];
$existingReviews = [];
$zakaznikId      = 0;

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("SELECT id FROM zakaznici WHERE user_id = :uid");
    $stmt->execute(['uid' => Auth::id()]);
    $zakaznikRow = $stmt->fetch();

    if ($zakaznikRow) {
        $zakaznikId = $zakaznikRow->id;

        $stmt = $db->prepare("
            SELECT o.id, o.restauracia_id, o.created_at, o.stav, o.suma, o.platba, o.adresa_dorucenia,
                   r.nazov AS restauracia_nazov
            FROM objednavky o
            JOIN restauracie r ON r.id = o.restauracia_id
            WHERE o.zakaznik_id = :zid
            ORDER BY o.created_at DESC
        ");
        $stmt->execute(['zid' => $zakaznikId]);
        $objednavky = $stmt->fetchAll();

        // Položky objednávok — vlastný try-catch, aby chyba neovplyvnila recenzie
        try {
            $itemStmt = $db->prepare("
                SELECT nazov, mnozstvo, cena
                FROM polozky_objednavky
                WHERE objednavka_id = :oid
                ORDER BY id
            ");
            foreach ($objednavky as &$o) {
                $itemStmt->execute(['oid' => $o->id]);
                $o->polozky_detail = $itemStmt->fetchAll();
            }
            unset($o);
        } catch (PDOException $e) {
            Helper::log("moje-objednavky.php polozky ERROR: " . $e->getMessage());
            foreach ($objednavky as &$o) { $o->polozky_detail = []; }
            unset($o);
        }
    }
} catch (PDOException $e) {
    Helper::log("moje-objednavky.php ERROR: " . $e->getMessage());
}

// Načítaj existujúce recenzie — oddelený try-catch, nezávislý od ostatných dotazov
if ($zakaznikId > 0) {
    try {
        $reviewsExists = (bool)$db->query("SHOW TABLES LIKE 'reviews'")->fetchColumn();
        if ($reviewsExists) {
            $rvStmt = $db->prepare("
                SELECT restaurant_id, stars, comment
                FROM reviews
                WHERE customer_id = :cid
            ");
            $rvStmt->execute(['cid' => $zakaznikId]);
            foreach ($rvStmt->fetchAll() as $rv) {
                $existingReviews[(int)$rv->restaurant_id] = $rv;
            }
        }
    } catch (PDOException $e) {
        Helper::log("moje-objednavky.php reviews ERROR: " . $e->getMessage());
    }
}

$cartCount = array_sum(array_column($_SESSION['cart']['items'] ?? [], 'qty'));

function platbaLabel(string $platba): string {
    return match ($platba) {
        'karta'     => '💳 Karta',
        'apple_pay' => '🍎 Apple Pay',
        'hotovost'  => '💵 Hotovosť',
        default     => htmlspecialchars($platba, ENT_QUOTES, 'UTF-8'),
    };
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moje objednávky – QuickBite</title>
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
      <li><a href="moje-objednavky.php" style="color:var(--primary);">Moje objednávky</a></li>
      <li><a href="profil.php">Môj profil</a></li>
    </ul>
    <div class="navbar-cta">
      <a href="cart.php" class="nav-cart btn btn-ghost" title="Košík">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
        <span class="nav-cart-count"><?= $cartCount > 0 ? $cartCount : '' ?></span>
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
  <div class="page-hero-sm">
    <div class="page-hero-sm-inner">
      <h1>Moje objednávky 📋</h1>
      <p>História všetkých vašich objednávok na QuickBite.</p>
    </div>
  </div>

  <div style="max-width:900px;margin:0 auto;padding:0 1.5rem 3rem;">

    <?= Helper::renderFlash() ?>

    <?php if (empty($objednavky)): ?>
      <div style="text-align:center;padding:4rem 1rem;">
        <div style="font-size:4rem;margin-bottom:1.5rem;">📭</div>
        <h2 style="margin-bottom:.75rem;">Zatiaľ žiadne objednávky</h2>
        <p style="margin-bottom:2rem;color:var(--gray-500);">Objednajte si jedlo z niektorej reštaurácie.</p>
        <a href="restaurants.php" class="btn btn-primary btn-lg" style="justify-content:center;">Prezrieť reštaurácie</a>
      </div>
    <?php else: ?>

      <!-- Súhrnná štatistika -->
      <?php
        $celkemUtratene = array_sum(array_column($objednavky, 'suma'));
        $pocetDorucena  = count(array_filter($objednavky, fn($o) => $o->stav === 'dorucena'));
      ?>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;">
        <div class="card" style="text-align:center;padding:1.25rem;">
          <div style="font-size:1.75rem;font-weight:800;color:var(--primary);"><?= count($objednavky) ?></div>
          <div style="font-size:.8rem;color:var(--gray-500);margin-top:.25rem;">Celkom objednávok</div>
        </div>
        <div class="card" style="text-align:center;padding:1.25rem;">
          <div style="font-size:1.75rem;font-weight:800;color:var(--success);"><?= $pocetDorucena ?></div>
          <div style="font-size:.8rem;color:var(--gray-500);margin-top:.25rem;">Úspešne doručených</div>
        </div>
        <div class="card" style="text-align:center;padding:1.25rem;">
          <div style="font-size:1.75rem;font-weight:800;color:var(--gray-800);"><?= Helper::eur($celkemUtratene) ?></div>
          <div style="font-size:.8rem;color:var(--gray-500);margin-top:.25rem;">Celková útrata</div>
        </div>
      </div>

      <!-- Zoznam objednávok -->
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php foreach ($objednavky as $obj): ?>
        <div class="card" style="overflow:hidden;">
          <!-- Hlavička objednávky -->
          <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--gray-100);flex-wrap:wrap;gap:.5rem;">
            <div style="display:flex;align-items:center;gap:1rem;">
              <div>
                <span style="font-weight:700;color:var(--gray-900);">Objednávka #<?= $obj->id ?></span>
                <span style="color:var(--gray-400);margin:0 .4rem;">·</span>
                <span style="color:var(--gray-500);font-size:.85rem;"><?= Helper::h($obj->restauracia_nazov) ?></span>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
              <?= Helper::stavBadge($obj->stav) ?>
              <span style="font-weight:700;color:var(--gray-900);"><?= Helper::eur((float)$obj->suma) ?></span>
            </div>
          </div>

          <!-- Detail objednávky -->
          <div style="padding:1rem 1.25rem;">
            <div style="display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:.9rem;font-size:.85rem;color:var(--gray-500);">
              <span>📅 <?= date('d.m.Y H:i', strtotime($obj->created_at)) ?></span>
              <span><?= platbaLabel($obj->platba) ?></span>
              <?php if ($obj->adresa_dorucenia): ?>
              <span>📍 <?= Helper::h($obj->adresa_dorucenia) ?></span>
              <?php endif; ?>
            </div>

            <!-- Položky objednávky -->
            <?php if (!empty($obj->polozky_detail)): ?>
            <div style="border-top:1px solid var(--gray-100);padding-top:.85rem;">
              <div style="font-size:.78rem;font-weight:600;color:var(--gray-400);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem;">Položky</div>
              <div style="display:flex;flex-direction:column;gap:.35rem;">
                <?php foreach ($obj->polozky_detail as $pol): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:.875rem;">
                  <span style="color:var(--gray-700);">
                    <?= Helper::h($pol->nazov) ?>
                    <span style="color:var(--gray-400);font-size:.8rem;">× <?= (int)$pol->mnozstvo ?></span>
                  </span>
                  <span style="font-weight:600;color:var(--gray-800);"><?= Helper::eur((float)$pol->cena * (int)$pol->mnozstvo) ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Akcie + hodnotenie -->
          <?php
            $rid        = (int)$obj->restauracia_id;
            $isDorucena = $obj->stav === 'dorucena';
            $myReview   = $existingReviews[$rid] ?? null;
          ?>
          <div style="background:var(--gray-50);border-top:1px solid var(--gray-100);">

            <!-- Hodnotiaci formulár — len pre doručené objednávky -->
            <?php if ($isDorucena): ?>
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--gray-100);">
              <?php if ($myReview): ?>
                <!-- Zákazník už ohodnotil túto reštauráciu -->
                <div style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:var(--gray-600);">
                  <span style="font-size:1rem;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <span style="color:<?= $s <= $myReview->stars ? '#F59E0B' : 'var(--gray-300)' ?>;">★</span>
                    <?php endfor; ?>
                  </span>
                  <span>Vaše hodnotenie: <strong><?= (int)$myReview->stars ?>/5</strong></span>
                  <?php if ($myReview->comment): ?>
                    <span style="color:var(--gray-400);">· „<?= Helper::h(mb_substr($myReview->comment, 0, 60)) ?><?= mb_strlen($myReview->comment) > 60 ? '…' : '' ?>"</span>
                  <?php endif; ?>
                  <!-- Umožni aktualizáciu hodnotenia -->
                  <button onclick="document.getElementById('reviewForm-<?= $obj->id ?>').classList.toggle('hidden')"
                          class="btn btn-ghost btn-sm" style="font-size:.75rem;margin-left:auto;">
                    Upraviť
                  </button>
                </div>
                <div id="reviewForm-<?= $obj->id ?>" class="hidden" style="margin-top:.75rem;">
              <?php else: ?>
                <div style="font-size:.85rem;font-weight:600;color:var(--gray-700);margin-bottom:.6rem;">
                  ⭐ Ohodnoťte reštauráciu <?= Helper::h($obj->restauracia_nazov) ?>
                </div>
                <div id="reviewForm-<?= $obj->id ?>">
              <?php endif; ?>

                <!-- Formulár (rovnaký pre nové aj aktualizáciu) -->
                <form method="POST" action="review-submit.php" class="review-form">
                  <input type="hidden" name="restaurant_id" value="<?= $rid ?>">
                  <input type="hidden" name="stars" id="starsVal-<?= $obj->id ?>" value="<?= (int)($myReview->stars ?? 0) ?>">

                  <!-- Hviezdičkový selektor -->
                  <div class="stars-selector" id="starsSel-<?= $obj->id ?>"
                       style="display:flex;gap:.2rem;margin-bottom:.6rem;cursor:pointer;">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <span class="star-btn"
                            data-val="<?= $s ?>"
                            style="font-size:1.6rem;color:<?= $s <= ($myReview->stars ?? 0) ? '#F59E0B' : 'var(--gray-300)' ?>;transition:color .1s;">
                        ★
                      </span>
                    <?php endfor; ?>
                  </div>

                  <textarea name="comment" rows="2"
                            style="width:100%;border:1px solid var(--gray-200);border-radius:8px;padding:.5rem .7rem;font-size:.82rem;font-family:inherit;resize:vertical;margin-bottom:.5rem;"
                            placeholder="Komentár (voliteľné)..."><?= Helper::h($myReview->comment ?? '') ?></textarea>

                  <button type="submit" class="btn btn-primary btn-sm">
                    <?= $myReview ? 'Aktualizovať hodnotenie' : 'Odoslať hodnotenie' ?>
                  </button>
                </form>
              </div><!-- /reviewForm -->
            </div>
            <?php endif; ?>

            <!-- Tlačidlo Objednať znova -->
            <div style="padding:.75rem 1.25rem;display:flex;justify-content:flex-end;">
              <a href="restaurant-detail.php?id=<?= $rid ?>" class="btn btn-ghost btn-sm"
                 style="font-size:.8rem;">
                Objednať znova z <?= Helper::h($obj->restauracia_nazov) ?> →
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</main>

<script src="../assets/js/main.js"></script>
<script>
// Hviezdičkové selektory — inicializácia pre všetky formuláre na stránke
document.querySelectorAll('.stars-selector').forEach(sel => {
  const formId   = sel.id.replace('starsSel-', '');
  const hidden   = document.getElementById('starsVal-' + formId);
  const starBtns = sel.querySelectorAll('.star-btn');

  function paint(n) {
    starBtns.forEach((btn, i) => {
      btn.style.color = i < n ? '#F59E0B' : 'var(--gray-300)';
    });
  }

  starBtns.forEach(btn => {
    btn.addEventListener('mouseenter', () => paint(+btn.dataset.val));
    btn.addEventListener('click',      () => { hidden.value = btn.dataset.val; paint(+btn.dataset.val); });
  });
  sel.addEventListener('mouseleave', () => paint(+hidden.value));
});

// Validácia: vyžaduj kliknutie na hviezdičku pred odoslaním
document.querySelectorAll('.review-form').forEach(form => {
  form.addEventListener('submit', e => {
    const starsInput = form.querySelector('input[name="stars"]');
    if (!starsInput || parseInt(starsInput.value, 10) < 1) {
      e.preventDefault();
      showToast('Vyberte počet hviezdičiek (1–5).', 'danger');
      form.querySelector('.stars-selector')?.classList.add('stars-required');
      setTimeout(() => form.querySelector('.stars-selector')?.classList.remove('stars-required'), 1500);
    }
  });
});
</script>
<style>
  .hidden { display: none !important; }
  .stars-required { outline: 2px solid var(--danger); border-radius: 6px; }
</style>
</body>
</html>
