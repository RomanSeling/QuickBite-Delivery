<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/StatistikyController.php';
$data = StatistikyController::getData();
extract($data);
// $statusCounts, $stats, $statsByMonth, $topRestauracie, $novychObjednavok

$totalObjednavok = array_sum($statusCounts);
$trzby_celkove   = 0;
foreach ($statsByMonth as $m) {
    $trzby_celkove += (float)$m->trzby;
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Štatistiky – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'statistiky'; require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="dash-header-title">
        <h2>Štatistiky</h2>
        <p>Prehľad výkonu a trendov</p>
      </div>
      <div class="dash-header-right">
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <!-- KPI row -->
      <div class="stats-grid" style="margin-bottom:1.75rem;">
        <div class="stat-card">
          <div class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
          <div class="stat-info"><h4><?= Helper::eur($trzby_celkove) ?></h4><p>Tržby celkom (6 mes.)</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg></div>
          <div class="stat-info"><h4><?= (int)$stats->celkove ?></h4><p>Objednávky celkom</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
          <div class="stat-info"><h4><?= Helper::eur((float)$stats->trzby_dnes) ?></h4><p>Tržby dnes</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <div class="stat-info"><h4><?= (int)$stats->aktivne ?></h4><p>Aktívne objednávky</p></div>
        </div>
      </div>

      <!-- Tržby po mesiacoch -->
      <?php if (!empty($statsByMonth)): ?>
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
          <div>
            <div class="chart-card-title">Tržby a objednávky po mesiacoch</div>
            <div class="chart-card-sub">Posledných 6 mesiacov</div>
          </div>
        </div>
        <div class="card-body">
          <div class="bar-chart">
            <?php
              $maxTrzby = max(array_map(fn($m) => (float)$m->trzby, $statsByMonth)) ?: 1;
              foreach ($statsByMonth as $m):
                $h = round((float)$m->trzby / $maxTrzby * 130);
            ?>
            <div class="bar-wrap">
              <div class="bar-val"><?= Helper::eur((float)$m->trzby) ?></div>
              <div class="bar" style="height:<?= max($h, 4) ?>px;" title="<?= Helper::h($m->mesiac) ?>: <?= (int)$m->pocet ?> objednávok"></div>
              <div class="bar-label"><?= Helper::h(substr($m->mesiac, 5)) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Stav objednávok + Top reštaurácie -->
      <div class="chart-grid-main">
        <div class="card">
          <div class="card-header"><div class="chart-card-title">Stav objednávok</div></div>
          <div class="card-body">
            <?php
              $stavyInfo = [
                'dorucena'   => ['Doručené',     'linear-gradient(90deg,var(--success),#34D399)'],
                'ceste'      => ['Na ceste',      'linear-gradient(90deg,#7C3AED,#A78BFA)'],
                'pripravuje' => ['Pripravuje sa', 'linear-gradient(90deg,var(--warning),#FCD34D)'],
                'nova'       => ['Nové',          'linear-gradient(90deg,#3B82F6,#93C5FD)'],
                'zrusena'    => ['Zrušené',       'linear-gradient(90deg,var(--danger),#FCA5A5)'],
              ];
              $celkove = max(1, $totalObjednavok);
            ?>
            <?php foreach ($stavyInfo as $key => [$label, $gradient]): ?>
              <?php $pocet = $statusCounts[$key] ?? 0; $pct = round($pocet / $celkove * 100); ?>
              <div class="cat-row">
                <div class="cat-row-label"><span><?= $label ?></span></div>
                <div class="cat-progress"><div class="progress-bar-wrap"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $gradient ?>;"></div></div></div>
                <span class="fw-600" style="font-size:.82rem;min-width:48px;text-align:right;"><?= $pocet ?> (<?= $pct ?>%)</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><div class="chart-card-title">Top reštaurácie dnes</div></div>
          <div class="card-body" style="padding:1rem 1.25rem;">
            <?php if (empty($topRestauracie)): ?>
              <p style="font-size:.82rem;color:var(--gray-400);">Zatiaľ žiadne objednávky dnes.</p>
            <?php else: ?>
              <div style="display:flex;flex-direction:column;gap:.75rem;">
                <?php foreach ($topRestauracie as $r): ?>
                <div style="display:flex;align-items:center;gap:.75rem;">
                  <div style="flex:1;">
                    <div style="font-size:.82rem;font-weight:600;"><?= Helper::h($r->nazov) ?></div>
                    <div style="font-size:.72rem;color:var(--gray-500);"><?= (int)$r->pocet_dnes ?> objednávok</div>
                  </div>
                  <span class="badge badge-success"><?= (int)$r->pocet_dnes ?></span>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
