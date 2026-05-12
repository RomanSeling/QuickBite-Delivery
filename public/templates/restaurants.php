<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

$restauraciaModel = new Restauracia();

// ── Vstup z GET ──────────────────────────────────────────────────────────────
$q    = trim($_GET['q']    ?? '');
$kat  = trim($_GET['kat']  ?? '');
$sort = trim($_GET['sort'] ?? '');

$allowedKat  = ['pizza', 'burger', 'sushi', 'mexican', 'healthy', 'asian', 'dessert'];
$allowedSort = ['rating', 'min', 'name'];
if (!in_array($kat,  $allowedKat,  true)) $kat  = '';
if (!in_array($sort, $allowedSort, true)) $sort = 'rating';

// SQL LIKE vyhľadávanie + triedenie
$allForQ = $restauraciaModel->search($q, $sort);

// Počty pre chipy (pred kategóriálnym filtrom)
$katCounts = ['all' => count($allForQ)];
foreach ($allForQ as $r) {
    $f = getRestaurantFilter($r->kategoria ?? '');
    $katCounts[$f] = ($katCounts[$f] ?? 0) + 1;
}

// PHP filter podľa kategórie
$restauracie = $kat !== ''
    ? array_values(array_filter($allForQ, fn($r) => getRestaurantFilter($r->kategoria ?? '') === $kat))
    : $allForQ;

$cartCount = array_sum(array_column($_SESSION['cart']['items'] ?? [], 'qty'));

function getRestaurantFilter(string $kat): string {
    $low = mb_strtolower($kat);
    if (str_contains($low, 'taliansk') || str_contains($low, 'pizza'))           return 'pizza';
    if (str_contains($low, 'americk') || str_contains($low, 'burger'))           return 'burger';
    if (str_contains($low, 'japonsk') || str_contains($low, 'sushi'))            return 'sushi';
    if (str_contains($low, 'mexick') || str_contains($low, 'taco'))              return 'mexican';
    if (str_contains($low, 'vegetar') || str_contains($low, 'vegan') || str_contains($low, 'zdravá')) return 'healthy';
    if (str_contains($low, 'ázijsk') || str_contains($low, 'čínsk') || str_contains($low, 'indick')) return 'asian';
    if (str_contains($low, 'dezert') || str_contains($low, 'sladkost'))          return 'dessert';
    return 'other';
}

function getRestaurantEmoji(string $filter): string {
    return match($filter) {
        'pizza'   => '🍕',
        'burger'  => '🍔',
        'sushi'   => '🍣',
        'mexican' => '🌮',
        'healthy' => '🥗',
        'asian'   => '🍜',
        'dessert' => '🍰',
        default   => '🍽️',
    };
}

function getRestaurantGradient(string $filter): string {
    return match($filter) {
        'pizza'   => 'linear-gradient(135deg,#FFF3EE,#FFE0CC)',
        'burger'  => 'linear-gradient(135deg,#FFF1F0,#FFD6D0)',
        'sushi'   => 'linear-gradient(135deg,#EEF2FF,#C7D2FE)',
        'mexican' => 'linear-gradient(135deg,#F0FDF4,#BBF7D0)',
        'healthy' => 'linear-gradient(135deg,#F0FFF4,#C6F6D5)',
        'asian'   => 'linear-gradient(135deg,#FFF8E1,#FFE082)',
        'dessert' => 'linear-gradient(135deg,#FDF4FF,#E9D5FF)',
        default   => 'linear-gradient(135deg,#F9FAFB,#E5E7EB)',
    };
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reštaurácie – QuickBite</title>
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
      <li><a href="restaurants.php" style="color:var(--primary);">Reštaurácie</a></li>
      <?php if (Auth::check() && Auth::isCustomer()): ?>
      <li><a href="moje-objednavky.php">Moje objednávky</a></li>
      <li><a href="profil.php">Môj profil</a></li>
      <?php endif; ?>
    </ul>
    <div class="navbar-cta">
      <a href="cart.php" class="nav-cart btn btn-ghost" title="Košík">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
        <span class="nav-cart-count"><?= $cartCount > 0 ? $cartCount : '' ?></span>
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

<main class="page-wrap">

  <!-- Compact hero -->
  <div class="page-hero-sm">
    <div class="page-hero-sm-inner">
      <h1>Reštaurácie vo vašom okolí 🗺️</h1>
      <?php if ($q !== '' || $kat !== ''): ?>
        <p>Nájdených <strong><?= count($restauracie) ?></strong> reštaurácií
          <?= $q !== '' ? 'pre „<strong>' . Helper::h($q) . '</strong>"' : '' ?>
          <?= $kat !== '' ? '· kategória <strong>' . Helper::h($kat) . '</strong>' : '' ?>
        </p>
      <?php else: ?>
        <p>Vyberte si z dostupných reštaurácií a objednajte jedlo priamo k dverám.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Search bar — GET formulár, zachováva aktívnu kategóriu -->
  <div class="search-hero">
    <form method="GET" action="restaurants.php" style="width:100%;">
      <?php if ($kat !== ''): ?>
        <input type="hidden" name="kat" value="<?= Helper::h($kat) ?>">
      <?php endif; ?>
      <?php if ($sort !== 'rating'): ?>
        <input type="hidden" name="sort" value="<?= Helper::h($sort) ?>">
      <?php endif; ?>
      <div class="search-hero-bar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= Helper::h($q) ?>"
               placeholder="Hľadaj reštauráciu alebo kuchyňu..." autocomplete="off">
        <?php if ($q !== ''): ?>
          <?php $clearQs = http_build_query(array_filter(['kat' => $kat, 'sort' => $sort !== 'rating' ? $sort : ''])); ?>
          <a href="restaurants.php<?= $clearQs ? '?'.$clearQs : '' ?>"
             style="color:var(--gray-400);text-decoration:none;padding:0 .5rem;font-size:1.1rem;line-height:1;"
             title="Vymazať hľadanie">×</a>
        <?php endif; ?>
        <button type="submit" style="background:var(--primary);color:#fff;border:none;border-radius:8px;padding:.4rem .9rem;font-size:.85rem;font-weight:600;cursor:pointer;">Hľadať</button>
      </div>
    </form>
  </div>

  <!-- Filter chipy — <a> linky, zachovávajú aktívny search term -->
  <?php
    // Pomocná funkcia pre URL chipu: zachová $q, prepne $kat
    $chipUrl = fn(string $k): string =>
        'restaurants.php?' . http_build_query(array_filter(['q' => $q, 'kat' => $k !== 'all' ? $k : '', 'sort' => $sort !== 'rating' ? $sort : '']));

    $chips = [
        'all'     => ['🍽️', 'Všetky'],
        'pizza'   => ['🍕', 'Pizza'],
        'burger'  => ['🍔', 'Burgery'],
        'sushi'   => ['🍣', 'Sushi'],
        'mexican' => ['🌮', 'Mexicko'],
        'healthy' => ['🥗', 'Zdravé'],
        'asian'   => ['🍜', 'Ázijská'],
        'dessert' => ['🍰', 'Dezerty'],
    ];
  ?>
  <div class="filter-chips-wrap">
    <?php foreach ($chips as $key => [$emoji, $label]): ?>
      <?php $isActive = ($key === 'all' && $kat === '') || $key === $kat; ?>
      <?php $cnt = $key === 'all' ? ($katCounts['all'] ?? 0) : ($katCounts[$key] ?? 0); ?>
      <a href="<?= $chipUrl($key) ?>"
         class="filter-chip<?= $isActive ? ' active' : '' ?>">
        <?= $emoji ?> <?= $label ?><?= $cnt > 0 ? ' <small style="opacity:.65;font-weight:500;">('.$cnt.')</small>' : '' ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Restaurants grid -->
  <section class="restaurants-section">
    <div class="restaurants-section-header">
      <h2>
        <?= ($q !== '' || $kat !== '') ? 'Výsledky hľadania' : 'Otvorené reštaurácie' ?>
        <span style="color:var(--gray-400);font-weight:500;font-size:.9rem;">
          (<?= count($restauracie) ?> nájdených)
        </span>
      </h2>
      <select class="form-control" style="width:auto;"
              onchange="const p=new URLSearchParams(window.location.search);p.set('sort',this.value);window.location.href='restaurants.php?'+p.toString();">
        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Zoradiť: Hodnotenie</option>
        <option value="min"    <?= $sort === 'min'    ? 'selected' : '' ?>>Zoradiť: Min. objednávka</option>
        <option value="name"   <?= $sort === 'name'   ? 'selected' : '' ?>>Zoradiť: Názov A–Z</option>
      </select>
    </div>

    <div class="restaurants-grid" id="restaurantsGrid">

      <?php
        $backQs = http_build_query(array_filter(['q' => $q, 'kat' => $kat, 'sort' => $sort !== 'rating' ? $sort : '']));
        foreach ($restauracie as $r):
        $filter   = getRestaurantFilter($r->kategoria ?? '');
        $emoji    = getRestaurantEmoji($filter);
        $gradient = getRestaurantGradient($filter);
        $isActive = $r->stav === 'aktivna';
        $rating   = $r->hodnotenie > 0 ? number_format((float)$r->hodnotenie, 1) : '—';
        $minOb    = number_format((float)$r->min_objednavka, 2, ',', ' ');
      ?>
      <a href="restaurant-detail.php?id=<?= $r->id ?><?= $backQs ? '&back=' . urlencode($backQs) : '' ?>"
         class="restaurant-card<?= $isActive ? '' : ' restaurant-card--closed' ?>"
         data-category="<?= $filter ?>"
         data-rating="<?= $r->hodnotenie ?>"
         data-min="<?= $r->min_objednavka ?>">
        <div class="restaurant-card-banner" style="background:<?= $gradient ?>;">
          <?php if (!$isActive): ?>
            <span class="restaurant-open closed">● Zatvorené</span>
          <?php else: ?>
            <span class="restaurant-open open">● Otvorené</span>
          <?php endif; ?>
          <?= $emoji ?>
        </div>
        <div class="restaurant-card-body">
          <div class="restaurant-card-top">
            <div class="restaurant-card-name"><?= Helper::h($r->nazov) ?></div>
            <div class="restaurant-rating">⭐ <?= $rating ?></div>
          </div>
          <div class="restaurant-card-cat"><?= Helper::h($r->kategoria ?? '') ?></div>
          <div class="restaurant-card-meta">
            <span>💶 min. <?= $minOb ?> €</span>
            <span>🛵 1,99 €</span>
          </div>
          <?php if ($r->popis): ?>
          <div class="restaurant-tags">
            <span class="restaurant-tag"><?= Helper::h(mb_substr($r->popis, 0, 40)) ?><?= mb_strlen($r->popis) > 40 ? '…' : '' ?></span>
          </div>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>

      <?php if (empty($restauracie)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:3rem 1rem;color:var(--gray-500);">
        <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
        <?php if ($q !== '' || $kat !== ''): ?>
          <p style="font-size:1rem;margin-bottom:1rem;">
            Nenašla sa žiadna reštaurácia
            <?= $q !== '' ? 'pre „<strong>' . Helper::h($q) . '</strong>"' : '' ?>
            <?= $kat !== '' ? '· kategória <strong>' . Helper::h($kat) . '</strong>' : '' ?>
          </p>
          <a href="restaurants.php" class="btn btn-primary" style="justify-content:center;">
            Zobraziť všetky reštaurácie
          </a>
        <?php else: ?>
          <p>Žiadne reštaurácie sa nenašli.</p>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div><!-- /grid -->
  </section>

</main>

<footer class="footer" style="margin-top:3rem;">
  <div class="footer-bottom" style="max-width:1160px;margin:0 auto;border-top:none;">
    <span>© 2025 QuickBite s.r.o.</span>
    <span>Rýchle a spoľahlivé doručenie jedla</span>
  </div>
</footer>

<script src="../assets/js/main.js"></script>
<?php require_once __DIR__ . '/partials/cookie-banner.php'; ?>
</body>
</html>
