<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    Redirect::redirect('restaurants.php');
}

$restauraciaModel = new Restauracia();
$restauracia = $restauraciaModel->find($id);
if (!$restauracia) {
    Redirect::redirect('restaurants.php');
}

$produktModel = new Produkt();
$produkty = $produktModel->byRestauracia($id);

// Group products by category
$grouped = [];
foreach ($produkty as $p) {
    $grouped[$p->kategoria][] = $p;
}

// Cart count from session
$cartCount = array_sum(array_column($_SESSION['cart']['items'] ?? [], 'qty'));

// Back-link: zachová search/filter stav z restaurants.php
$back = $_GET['back'] ?? '';
if (!preg_match('/^[a-zA-Z0-9=&%._~+\-]*$/', $back)) {
    $back = '';
}
$backHref = $back ? 'restaurants.php?' . $back : 'restaurants.php';

// Recenzie
$db = (new Database())->getConnection();
$reviewsTableExists = false;
try {
    $reviewsTableExists = (bool)$db->query("SHOW TABLES LIKE 'reviews'")->fetchColumn();
} catch (PDOException $e) {
    Helper::log('restaurant-detail.php reviews check ERROR: ' . $e->getMessage());
}

$reviews = [];
$ratingDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
if ($reviewsTableExists) {
    $rvStmt = $db->prepare("
        SELECT rv.stars, rv.comment, rv.created_at,
               z.meno AS z_meno, z.priezvisko AS z_priezvisko
        FROM reviews rv
        JOIN zakaznici z ON z.id = rv.customer_id
        WHERE rv.restaurant_id = :id
        ORDER BY rv.created_at DESC
        LIMIT 20
    ");
    $rvStmt->execute(['id' => $id]);
    $reviews = $rvStmt->fetchAll();

    $distStmt = $db->prepare("SELECT stars, COUNT(*) AS cnt FROM reviews WHERE restaurant_id = :id GROUP BY stars");
    $distStmt->execute(['id' => $id]);
    foreach ($distStmt->fetchAll() as $row) {
        $ratingDist[(int)$row->stars] = (int)$row->cnt;
    }
}

$canReview      = false;
$existingReview = null;
if (Auth::check() && Auth::isCustomer()) {
    $zStmt = $db->prepare("SELECT id FROM zakaznici WHERE user_id = :uid");
    $zStmt->execute(['uid' => Auth::id()]);
    $zakaznik = $zStmt->fetch();
    if ($zakaznik) {
        $cntStmt = $db->prepare("
            SELECT COUNT(*) FROM objednavky
            WHERE zakaznik_id = :zid AND restauracia_id = :rid AND stav = 'dorucena'
        ");
        $cntStmt->execute(['zid' => $zakaznik->id, 'rid' => $id]);
        $canReview = (int)$cntStmt->fetchColumn() > 0;

        if ($canReview && $reviewsTableExists) {
            $exStmt = $db->prepare("SELECT stars, comment FROM reviews WHERE customer_id = :cid AND restaurant_id = :rid");
            $exStmt->execute(['cid' => $zakaznik->id, 'rid' => $id]);
            $existingReview = $exStmt->fetch();
        }
    }
}

// Category emoji mapping for tabs
$tabEmoji = [
    'Populárne' => '🔥', 'Pizze' => '🍕', 'Cestoviny' => '🍝', 'Prílohy' => '🥗', 'Nápoje' => '🥤',
    'Burgery' => '🍔', 'Nigiri' => '🍣', 'Maki' => '🍱', 'Špeciality' => '🌟', 'Ramen' => '🍜',
    'Polievky' => '🥣', 'Tacos' => '🌮', 'Burritos' => '🌯', 'Bowly' => '🥗', 'Šaláty' => '🥗',
    'Smoothie' => '🥤', 'Raňajky' => '🌅', 'Jedlá' => '🍽️',
];

function getTabEmoji(string $kat, array $map): string {
    return $map[$kat] ?? '🍽️';
}

// Banner styling by category
function getBannerStyle(string $kat): string {
    $low = mb_strtolower($kat);
    if (str_contains($low, 'taliansk') || str_contains($low, 'pizza'))    return 'background:linear-gradient(135deg,#1A1A2E,#2D1B0E);';
    if (str_contains($low, 'japonsk') || str_contains($low, 'sushi'))     return 'background:linear-gradient(135deg,#0E1A2D,#102030);';
    if (str_contains($low, 'americk') || str_contains($low, 'burger'))    return 'background:linear-gradient(135deg,#1A1A0E,#2D2010);';
    if (str_contains($low, 'vegetar') || str_contains($low, 'vegan'))     return 'background:linear-gradient(135deg,#0E2D1A,#103020);';
    if (str_contains($low, 'indick') || str_contains($low, 'curry'))      return 'background:linear-gradient(135deg,#2D1A0E,#3D200A);';
    if (str_contains($low, 'slovensk') || str_contains($low, 'bistro'))   return 'background:linear-gradient(135deg,#1A0E2D,#20103D);';
    if (str_contains($low, 'mexick') || str_contains($low, 'taco'))       return 'background:linear-gradient(135deg,#1A2D0E,#1E3010);';
    return 'background:linear-gradient(135deg,#1A1A2E,#2E1A2D);';
}

function getRestaurantEmoji2(string $kat): string {
    $low = mb_strtolower($kat);
    if (str_contains($low, 'taliansk') || str_contains($low, 'pizza'))    return '🍕';
    if (str_contains($low, 'japonsk') || str_contains($low, 'sushi'))     return '🍣';
    if (str_contains($low, 'americk') || str_contains($low, 'burger'))    return '🍔';
    if (str_contains($low, 'vegetar') || str_contains($low, 'vegan'))     return '🥗';
    if (str_contains($low, 'indick') || str_contains($low, 'curry'))      return '🍛';
    if (str_contains($low, 'slovensk') || str_contains($low, 'bistro'))   return '🥘';
    if (str_contains($low, 'mexick') || str_contains($low, 'taco'))       return '🌮';
    if (str_contains($low, 'ázijsk') || str_contains($low, 'čínsk'))      return '🍜';
    return '🍽️';
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= Helper::h($restauracia->nazov) ?> – QuickBite</title>
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
      <a href="cart.php" class="nav-cart btn btn-ghost" title="Košík">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
        <span class="nav-cart-count" id="cartCount"><?= $cartCount > 0 ? $cartCount : '' ?></span>
      </a>
      <?php if (Auth::check()): ?>
        <div class="navbar-user">
          <span class="navbar-user-avatar"><?= Helper::h(Auth::initials()) ?></span>
          <span class="navbar-user-name"><?= Helper::h(Auth::meno()) ?></span>
          <a href="logout.php" class="btn btn-ghost btn-sm">Odhlásiť</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn btn-ghost">Prihlásiť sa</a>
        <a href="register.php" class="btn btn-primary">Registrovať sa</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main style="padding-top:70px;">

  <!-- Banner header -->
  <div class="restaurant-banner-header" style="<?= getBannerStyle($restauracia->kategoria ?? '') ?>">
    <div class="restaurant-banner-bg"><?= getRestaurantEmoji2($restauracia->kategoria ?? '') ?></div>
    <div class="restaurant-banner-overlay"></div>
    <div class="restaurant-banner-info">
      <div style="margin-bottom:.5rem;">
        <a href="<?= Helper::h($backHref) ?>" style="color:rgba(255,255,255,.7);font-size:.82rem;text-decoration:none;">← Reštaurácie</a>
      </div>
      <h1><?= getRestaurantEmoji2($restauracia->kategoria ?? '') ?> <?= Helper::h($restauracia->nazov) ?></h1>
      <div class="restaurant-banner-meta">
        <?php if ($restauracia->hodnotenie > 0): ?>
          <span>⭐ <?= number_format((float)$restauracia->hodnotenie, 1) ?></span>
        <?php endif; ?>
        <span>🛵 1,99 € doručenie</span>
        <?php if ($restauracia->stav === 'aktivna'): ?>
          <span style="color:#86EFAC;">● Otvorené</span>
        <?php else: ?>
          <span style="color:#FCA5A5;">● Zatvorené</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Info bar -->
  <div class="restaurant-info-bar">
    <div class="info-chip">💶 Min. objednávka: <strong><?= number_format((float)$restauracia->min_objednavka, 2, ',', ' ') ?> €</strong></div>
    <?php if ($restauracia->adresa): ?>
      <div class="info-chip">📍 <?= Helper::h($restauracia->adresa) ?></div>
    <?php endif; ?>
    <?php if ($restauracia->telefon): ?>
      <div class="info-chip">📞 <?= Helper::h($restauracia->telefon) ?></div>
    <?php endif; ?>
    <div class="info-chip">💳 Karta, Apple Pay, Hotovosť</div>
  </div>

  <!-- Category tabs -->
  <?php if (!empty($grouped)): ?>
  <div class="category-tabs-wrap">
    <div class="category-tabs">
      <?php $first = true; foreach (array_keys($grouped) as $kat): ?>
        <a href="#kat-<?= urlencode($kat) ?>" class="category-tab<?= $first ? ' active' : '' ?>">
          <?= getTabEmoji($kat, $tabEmoji) ?> <?= Helper::h($kat) ?>
        </a>
      <?php $first = false; endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Detail layout: menu + cart sidebar -->
  <div class="detail-layout">

    <!-- ── MENU ── -->
    <div class="menu-col">

      <?php if (empty($produkty)): ?>
        <div style="text-align:center;padding:3rem;color:var(--gray-500);">
          <div style="font-size:3rem;margin-bottom:1rem;">🍽️</div>
          <p>Táto reštaurácia zatiaľ nemá pridané produkty.</p>
        </div>
      <?php else: ?>

      <?php foreach ($grouped as $kat => $items): ?>
      <div class="menu-section" id="kat-<?= urlencode($kat) ?>">
        <div class="menu-section-title"><?= getTabEmoji($kat, $tabEmoji) ?> <?= Helper::h($kat) ?></div>
        <div class="menu-items-list">
          <?php foreach ($items as $p): ?>
          <div class="menu-item">
            <div class="menu-item-img" style="background:var(--primary-light);"><?= $p->emoji ?></div>
            <div class="menu-item-info">
              <div class="menu-item-name"><?= Helper::h($p->nazov) ?></div>
              <?php if ($p->popis): ?>
                <div class="menu-item-desc"><?= Helper::h($p->popis) ?></div>
              <?php endif; ?>
              <div class="menu-item-bottom">
                <span class="menu-item-price"><?= number_format((float)$p->cena, 2, ',', ' ') ?> €</span>
                <button class="btn-add-item"
                  data-id="<?= $p->id ?>"
                  data-name="<?= Helper::h($p->nazov) ?>"
                  data-price="<?= $p->cena ?>"
                  data-restauracia-id="<?= $restauracia->id ?>"
                  data-restauracia-nazov="<?= Helper::h($restauracia->nazov) ?>">
                  + Pridať
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php endif; ?>

    </div><!-- /menu-col -->

    <!-- ── CART SIDEBAR ── -->
    <div class="cart-sidebar">
      <div class="cart-sidebar-card">
        <div class="cart-sidebar-header">
          <h4>🛒 Váš košík</h4>
          <span style="font-size:.78rem;opacity:.85;" id="sideCartCount">0 položiek</span>
        </div>

        <div class="cart-empty-msg" id="cartEmptyMsg">
          <span class="cart-empty-emoji">🛒</span>
          Pridajte položky z menu a začnite objednávku
        </div>

        <div id="sideCartItems"></div>

        <div class="cart-sidebar-footer" id="cartSidebarFooter" style="display:none;">
          <div class="cart-side-total-row"><span>Medzisúčet</span><span id="sideSubtotal">0,00 €</span></div>
          <div class="cart-side-total-row"><span>Doručenie</span><span>1,99 €</span></div>
          <div class="cart-side-total-row grand"><span>Spolu</span><span id="sideTotal">0,00 €</span></div>
          <a href="cart.php" class="btn btn-primary w-100" style="margin-top:.85rem;justify-content:center;padding:.7rem;">
            Pokračovať do košíka →
          </a>
        </div>
      </div>
    </div><!-- /cart-sidebar -->

  </div><!-- /detail-layout -->

  <!-- ── RECENZIE ── -->
  <section style="max-width:1100px;margin:0 auto 3rem;padding:0 1.5rem;">
    <div style="border-top:2px solid var(--gray-100);padding-top:2rem;">

      <h3 style="font-size:1.05rem;font-weight:700;color:var(--gray-900);margin-bottom:1.25rem;">
        ⭐ Hodnotenia zákazníkov
        <?php if ($restauracia->pocet_hodnoteni > 0): ?>
          <span style="font-size:.82rem;font-weight:500;color:var(--gray-400);">(<?= (int)$restauracia->pocet_hodnoteni ?>)</span>
        <?php endif; ?>
      </h3>

      <?php if ($reviewsTableExists && $restauracia->pocet_hodnoteni > 0): ?>
      <div style="display:flex;gap:1.5rem;align-items:center;background:var(--gray-50);border:2px solid var(--gray-100);border-radius:var(--radius-lg);padding:1rem 1.25rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div style="text-align:center;flex-shrink:0;">
          <div style="font-size:2.6rem;font-weight:800;color:var(--gray-900);line-height:1;"><?= number_format((float)$restauracia->hodnotenie, 1) ?></div>
          <div style="font-size:1.1rem;color:#F59E0B;letter-spacing:.05em;margin:.25rem 0;"><?= str_repeat('★', (int)round((float)$restauracia->hodnotenie)) ?><?= str_repeat('☆', 5 - (int)round((float)$restauracia->hodnotenie)) ?></div>
          <div style="font-size:.75rem;color:var(--gray-400);"><?= (int)$restauracia->pocet_hodnoteni ?> recenzií</div>
        </div>
        <div style="flex:1;min-width:160px;">
          <?php foreach ([5,4,3,2,1] as $s):
            $cnt  = $ratingDist[$s];
            $pct  = $restauracia->pocet_hodnoteni > 0 ? round($cnt / $restauracia->pocet_hodnoteni * 100) : 0;
          ?>
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;">
            <span style="font-size:.72rem;color:var(--gray-500);width:10px;text-align:right;"><?= $s ?></span>
            <span style="font-size:.72rem;color:#F59E0B;">★</span>
            <div style="flex:1;height:7px;background:var(--gray-200);border-radius:4px;overflow:hidden;">
              <div style="width:<?= $pct ?>%;height:100%;background:#F59E0B;border-radius:4px;"></div>
            </div>
            <span style="font-size:.72rem;color:var(--gray-400);width:22px;"><?= $cnt ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?= Helper::renderFlash() ?>

      <!-- Formulár hodnotenia -->
      <?php if ($canReview && $reviewsTableExists): ?>
      <div style="background:var(--gray-50);border:2px solid var(--gray-200);border-radius:var(--radius-lg);padding:1.25rem;margin-bottom:1.5rem;">
        <h4 style="font-size:.9rem;font-weight:700;color:var(--gray-800);margin-bottom:.85rem;">
          <?= $existingReview ? '✏️ Aktualizovať hodnotenie' : '✍️ Pridať hodnotenie' ?>
        </h4>
        <form method="POST" action="review-submit.php">
          <input type="hidden" name="restaurant_id" value="<?= $id ?>">
          <input type="hidden" name="redirect_to"   value="restaurant-detail.php?id=<?= $id ?>">
          <div style="margin-bottom:.85rem;">
            <div style="display:flex;gap:.4rem;margin-bottom:.75rem;">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <label style="cursor:pointer;font-size:1.75rem;line-height:1;color:#F59E0B;" title="<?= $s ?> hviezdičiek">
                  <input type="radio" name="stars" value="<?= $s ?>" style="display:none;"
                    <?= ($existingReview && (int)$existingReview->stars === $s) ? 'checked' : '' ?>>
                  <span class="star-lbl" data-val="<?= $s ?>"
                        style="opacity:<?= ($existingReview && $s <= (int)$existingReview->stars) ? '1' : '.25' ?>;">★</span>
                </label>
              <?php endfor; ?>
            </div>
            <textarea name="comment" rows="3" class="form-control"
              placeholder="Napíšte recenziu (voliteľné)..."
              style="font-size:.85rem;"><?= $existingReview ? Helper::h($existingReview->comment ?? '') : '' ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">
            <?= $existingReview ? 'Uložiť zmeny' : 'Odoslať hodnotenie' ?>
          </button>
        </form>
      </div>
      <?php elseif ($canReview && !$reviewsTableExists): ?>
      <div style="background:var(--gray-50);border-radius:var(--radius-lg);padding:.9rem 1.1rem;margin-bottom:1.5rem;font-size:.82rem;color:var(--gray-500);">
        Hodnotenia budú dostupné po dokončení migrácie tabuľky recenzií.
      </div>
      <?php elseif (Auth::check() && Auth::isCustomer()): ?>
      <div style="background:var(--gray-50);border-radius:var(--radius-lg);padding:.9rem 1.1rem;margin-bottom:1.5rem;font-size:.82rem;color:var(--gray-500);">
        💡 Môžete hodnotiť iba reštaurácie, z ktorých bola vaša objednávka doručená.
      </div>
      <?php elseif (!Auth::check()): ?>
      <div style="background:var(--gray-50);border-radius:var(--radius-lg);padding:.9rem 1.1rem;margin-bottom:1.5rem;font-size:.82rem;color:var(--gray-500);">
        <a href="login.php" style="color:var(--primary);font-weight:600;">Prihláste sa</a>, ak chcete pridať hodnotenie.
      </div>
      <?php endif; ?>

      <!-- Zoznam recenzií -->
      <?php if (empty($reviews)): ?>
        <p style="color:var(--gray-400);font-size:.85rem;">Zatiaľ žiadne recenzie. Buďte prvý!</p>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:.85rem;">
          <?php foreach ($reviews as $rv): ?>
          <div style="background:#fff;border:2px solid var(--gray-100);border-radius:var(--radius-lg);padding:1rem 1.15rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
              <div style="display:flex;align-items:center;gap:.6rem;">
                <span style="background:var(--primary-light);color:var(--primary);width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">
                  <?= mb_strtoupper(mb_substr($rv->z_meno, 0, 1) . mb_substr($rv->z_priezvisko, 0, 1)) ?>
                </span>
                <span style="font-size:.85rem;font-weight:600;color:var(--gray-800);">
                  <?= Helper::h($rv->z_meno . ' ' . mb_substr($rv->z_priezvisko, 0, 1) . '.') ?>
                </span>
              </div>
              <span style="font-size:.75rem;color:var(--gray-400);"><?= date('d.m.Y', strtotime($rv->created_at)) ?></span>
            </div>
            <div style="font-size:1rem;margin-bottom:.4rem;line-height:1;">
              <span style="color:#F59E0B;"><?= str_repeat('★', (int)$rv->stars) ?></span><span style="color:#E5E7EB;"><?= str_repeat('★', 5 - (int)$rv->stars) ?></span>
            </div>
            <?php if ($rv->comment): ?>
              <p style="font-size:.82rem;color:var(--gray-600);line-height:1.55;margin:0;"><?= Helper::h($rv->comment) ?></p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </section>

</main>

<?php require_once __DIR__ . '/partials/cookie-banner.php'; ?>
<script src="../assets/js/main.js"></script>
<script>
// Local cart state (mirrors session, rebuilt from localStorage for instant UI)
let cart = JSON.parse(localStorage.getItem('qb_cart') || '{}');

function updateCartUI() {
  const items = Object.values(cart);
  const count = items.reduce((s, i) => s + i.qty, 0);
  const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);
  const total = subtotal + (count > 0 ? 1.99 : 0);

  document.getElementById('cartCount').textContent = count || '';
  document.getElementById('sideCartCount').textContent = count + ' položiek';

  const emptyMsg = document.getElementById('cartEmptyMsg');
  const footer   = document.getElementById('cartSidebarFooter');
  const itemsDiv = document.getElementById('sideCartItems');

  if (count === 0) {
    emptyMsg.style.display = '';
    footer.style.display   = 'none';
    itemsDiv.innerHTML     = '';
    return;
  }

  emptyMsg.style.display = 'none';
  footer.style.display   = '';

  itemsDiv.innerHTML = items.map(item => `
    <div class="cart-side-item">
      <div class="qty-control">
        <button class="qty-btn" onclick="changeQty('${item.id}',${item.restauraciaId},${item.restauraciaNazov ? `'${item.restauraciaNazov.replace(/'/g,"\\'")} '` : "''"}, -1)">−</button>
        <span class="qty-num">${item.qty}</span>
        <button class="qty-btn" onclick="changeQty('${item.id}',${item.restauraciaId},'${item.restauraciaNazov ? item.restauraciaNazov.replace(/'/g,"\\'") : ""}', 1)">+</button>
      </div>
      <div class="cart-side-item-info">
        <div class="cart-side-item-name">${item.name}</div>
        <div class="cart-side-item-price">${(item.price * item.qty).toFixed(2).replace('.', ',')} €</div>
      </div>
    </div>
  `).join('');

  document.getElementById('sideSubtotal').textContent = subtotal.toFixed(2).replace('.', ',') + ' €';
  document.getElementById('sideTotal').textContent    = total.toFixed(2).replace('.', ',') + ' €';
}

function changeQty(id, restauraciaId, restauraciaNazov, delta) {
  if (!cart[id]) return;
  cart[id].qty += delta;
  if (cart[id].qty <= 0) {
    delete cart[id];
    sessionUpdate('remove', { id });
  } else {
    sessionUpdate('update', { id, qty: cart[id].qty });
  }
  localStorage.setItem('qb_cart', JSON.stringify(cart));
  updateCartUI();
}

function sessionUpdate(action, params) {
  const fd = new FormData();
  fd.append('action', action);
  Object.entries(params).forEach(([k, v]) => fd.append(k, v));
  fetch('cart-action.php', { method: 'POST', body: fd }).catch(() => {});
}

document.querySelectorAll('.btn-add-item').forEach(btn => {
  btn.addEventListener('click', () => {
    const { id, name, price, restauraciaId, restauraciaNazov } = btn.dataset;

    // Ak košík patrí inej reštaurácii, opýtaj sa zákazníka
    const firstItem = Object.values(cart)[0];
    if (firstItem && firstItem.restauraciaId !== parseInt(restauraciaId)) {
      if (!confirm('Košík obsahuje položky z inej reštaurácie.\nVymazať košík a začať odznova?')) return;
      const cf = new FormData(); cf.append('action', 'clear');
      fetch('cart-action.php', { method: 'POST', body: cf }).catch(() => {});
      cart = {};
    }

    if (cart[id]) {
      cart[id].qty++;
    } else {
      cart[id] = { id, name, price: parseFloat(price), qty: 1, restauraciaId: parseInt(restauraciaId), restauraciaNazov };
    }

    // Sync to session
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('id', id);
    fd.append('name', name);
    fd.append('price', price);
    fd.append('restauracia_id', restauraciaId);
    fd.append('restauracia_nazov', restauraciaNazov);
    fetch('cart-action.php', { method: 'POST', body: fd }).catch(() => {});

    localStorage.setItem('qb_cart', JSON.stringify(cart));
    updateCartUI();
    showToast(name + ' pridaný do košíka 🛒', 'success');
  });
});

// Category tab scrolling
document.querySelectorAll('.category-tab').forEach(tab => {
  tab.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    const target = document.querySelector(tab.getAttribute('href'));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

// Init UI from localStorage
updateCartUI();

// Interaktívne hviezdičky v review formulári
const starLabels = document.querySelectorAll('.star-lbl');
starLabels.forEach(lbl => {
  const val = parseInt(lbl.dataset.val);
  lbl.addEventListener('mouseenter', () => {
    starLabels.forEach(l => { l.style.opacity = parseInt(l.dataset.val) <= val ? '1' : '.25'; });
  });
  lbl.closest('label').addEventListener('click', () => {
    starLabels.forEach(l => { l.style.opacity = parseInt(l.dataset.val) <= val ? '1' : '.25'; });
  });
});
document.querySelector('.star-lbl')?.closest('div')?.addEventListener('mouseleave', () => {
  const checked = document.querySelector('input[name="stars"]:checked');
  const checkedVal = checked ? parseInt(checked.value) : 0;
  starLabels.forEach(l => { l.style.opacity = parseInt(l.dataset.val) <= checkedVal ? '1' : '.25'; });
});

// Validácia: vyžaduj výber hviezdičky pred odoslaním formulára
const reviewForm = document.querySelector('form[action="review-submit.php"]');
if (reviewForm) {
  reviewForm.addEventListener('submit', e => {
    if (!reviewForm.querySelector('input[name="stars"]:checked')) {
      e.preventDefault();
      showToast('Vyberte počet hviezdičiek (1–5).', 'danger');
      reviewForm.querySelector('.star-lbl')?.closest('div').style.setProperty('outline', '2px solid var(--danger)', 'important');
    }
  });
}
</script>
</body>
</html>
