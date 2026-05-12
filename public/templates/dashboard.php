<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/DashboardController.php';
$data = DashboardController::getData();
extract($data);
// $stats, $statusCounts, $posledneObjednavky, $topRestauracie, $novychObjednavok
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'dashboard'; require __DIR__ . '/partials/sidebar.php'; ?>

  <!-- MAIN -->
  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="dash-header-title">
        <h2>Správa objednávok</h2>
        <p><?= date('l, j. F Y') ?></p>
      </div>
      <div class="dash-header-right">
        <button class="icon-btn" title="Upozornenia">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          <?php if ($novychObjednavok > 0): ?><span class="notif-dot"></span><?php endif; ?>
        </button>
        <div class="header-avatar" title="Profil"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <!-- Stat cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          </div>
          <div class="stat-info">
            <h4><?= (int) $stats->celkove ?></h4>
            <p>Celkové objednávky</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div class="stat-info">
            <h4><?= (int) $stats->aktivne ?></h4>
            <p>Aktívne objednávky</p>
            <?php if ($stats->nove > 0): ?>
              <div class="trend up">↑ <?= (int)$stats->nove ?> nové</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <div class="stat-info">
            <h4><?= Helper::eur((float) $stats->trzby_dnes) ?></h4>
            <p>Tržby dnes</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <div class="stat-info">
            <h4>–</h4>
            <p>Priem. čas doručenia</p>
          </div>
        </div>
      </div>

      <!-- Main grid -->
      <div class="content-grid">

        <!-- Orders table -->
        <div class="card">
          <div class="card-header">
            <h3>🛵 Posledné objednávky</h3>
            <a href="objednavky.php" class="btn btn-primary btn-sm">Zobraziť všetky</a>
          </div>
          <div class="table-wrapper">
            <table class="table">
              <thead>
                <tr>
                  <th>#ID</th>
                  <th>Zákazník</th>
                  <th>Reštaurácia</th>
                  <th>Objednané položky</th>
                  <th>Suma</th>
                  <th>Stav</th>
                  <th>Čas</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($posledneObjednavky)): ?>
                  <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">Žiadne objednávky</td></tr>
                <?php else: ?>
                  <?php foreach ($posledneObjednavky as $o): ?>
                  <tr>
                    <td><span class="fw-600">#<?= $o->id ?></span></td>
                    <td>
                      <div style="display:flex;align-items:center;gap:.6rem;">
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#FFB300,#FF5722);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0;">
                          <?= mb_strtoupper(mb_substr($o->zakaznik_meno ?? '?', 0, 2)) ?>
                        </div>
                        <div class="fw-600" style="font-size:.875rem;"><?= Helper::h($o->zakaznik_meno ?? '–') ?></div>
                      </div>
                    </td>
                    <td style="font-size:.875rem;"><?= Helper::h($o->restauracia_nazov ?? '–') ?></td>
                    <td style="font-size:.78rem;color:var(--gray-600);max-width:200px;">
                      <?php
                        $polozkySkratene = $o->polozky ?? '';
                        echo Helper::h(mb_strlen($polozkySkratene) > 60
                            ? mb_substr($polozkySkratene, 0, 57) . '…'
                            : $polozkySkratene);
                      ?>
                    </td>
                    <td class="fw-600" style="color:var(--primary);"><?= Helper::eur((float)$o->suma) ?></td>
                    <td><?= Helper::stavBadge($o->stav) ?></td>
                    <td style="font-size:.8rem;color:var(--gray-500);"><?= date('H:i', strtotime($o->created_at)) ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Side widgets -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

          <!-- Status overview -->
          <?php
            $celkove = max(1, (int)$stats->celkove);
            $stavyInfo = [
              'dorucena'   => ['Doručené',     'linear-gradient(90deg,var(--success),#34D399)'],
              'ceste'      => ['Na ceste',      'linear-gradient(90deg,#7C3AED,#A78BFA)'],
              'pripravuje' => ['Pripravuje sa', 'linear-gradient(90deg,var(--warning),#FCD34D)'],
              'zrusena'    => ['Zrušené',       'linear-gradient(90deg,var(--danger),#FCA5A5)'],
            ];
          ?>
          <div class="card widget">
            <div class="card-header"><h3>📊 Stav objednávok</h3></div>
            <div class="card-body">
              <?php foreach ($stavyInfo as $key => [$label, $gradient]): ?>
                <?php $pocet = $statusCounts[$key] ?? 0; $pct = round($pocet / $celkove * 100); ?>
                <div class="progress-item">
                  <div class="progress-label"><span><?= $label ?></span><span><?= $pocet ?> / <?= (int)$stats->celkove ?></span></div>
                  <div class="progress-bar-wrap"><div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $gradient ?>;"></div></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Top restaurants -->
          <div class="card widget">
            <div class="card-header"><h3>🏆 Top reštaurácie dnes</h3></div>
            <div class="card-body" style="padding:1rem 1.25rem;">
              <?php if (empty($topRestauracie)): ?>
                <p style="font-size:.82rem;color:var(--gray-400);">Zatiaľ žiadne objednávky dnes.</p>
              <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:.75rem;">
                  <?php foreach ($topRestauracie as $r): ?>
                  <div style="display:flex;align-items:center;gap:.75rem;">
                    <div style="flex:1;">
                      <div style="font-size:.82rem;font-weight:600;color:var(--gray-900);"><?= Helper::h($r->nazov) ?></div>
                      <div style="font-size:.72rem;color:var(--gray-500);"><?= (int)$r->pocet_dnes ?> objednávok dnes</div>
                    </div>
                    <span class="badge badge-success"><?= (int)$r->pocet_dnes ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
