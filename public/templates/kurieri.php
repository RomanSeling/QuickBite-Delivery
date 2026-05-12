<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/KurierController.php';
KurierController::handle();
$data = KurierController::getData();
extract($data);
// $kurieri, $stats, $novychObjednavok
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kuriéri – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'kurieri'; require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="dash-header-title">
        <h2>Kuriéri</h2>
        <p>Správa a monitoring doručovacieho tímu</p>
      </div>
      <div class="dash-header-right">
        <button class="btn btn-primary btn-sm" data-modal-open="courierModal">+ Pridať kuriéra</button>
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div class="stat-icon green">🟢</div><div class="stat-info"><h4><?= (int)$stats->online ?></h4><p>Online kuriéri</p></div></div>
        <div class="stat-card"><div class="stat-icon orange">🛵</div><div class="stat-info"><h4><?= (int)$stats->aktivne_dorucenia ?></h4><p>Aktívne doručenia</p></div></div>
        <div class="stat-card"><div class="stat-icon amber">⭐</div><div class="stat-info"><h4><?= number_format((float)$stats->avg_hodnotenie, 1) ?></h4><p>Priemerné hodnotenie</p></div></div>
        <div class="stat-card"><div class="stat-icon red">📦</div><div class="stat-info"><h4><?= (int)$stats->doruceni_dnes ?></h4><p>Doručení dnes</p></div></div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>🛵 Zoznam kuriérov</h3>
          <div style="display:flex;gap:.5rem;">
            <span style="font-size:.82rem;color:var(--gray-500);">Celkom: <?= (int)$stats->celkove ?></span>
          </div>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Kuriér</th>
                <th>Kontakt</th>
                <th>Vozidlo</th>
                <th>Doručení dnes</th>
                <th>Hodnotenie</th>
                <th>Stav</th>
                <th>Akcie</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($kurieri)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">Žiadni kuriéri</td></tr>
              <?php else: ?>
                <?php foreach ($kurieri as $k): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.65rem;">
                      <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#FF5722,#FFB300);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.82rem;font-weight:700;">
                        <?= mb_strtoupper(mb_substr($k->meno,0,1) . mb_substr($k->priezvisko,0,1)) ?>
                      </div>
                      <div>
                        <div class="fw-600" style="font-size:.875rem;"><?= Helper::h($k->meno . ' ' . $k->priezvisko) ?></div>
                        <div style="font-size:.72rem;color:var(--gray-500);"><?= Helper::h($k->email ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td style="font-size:.82rem;color:var(--gray-600);"><?= Helper::h($k->telefon ?? '–') ?></td>
                  <td style="font-size:.85rem;">
                    <?php $vozidlaIkony = ['bicykel'=>'🚲','skuter'=>'🛵','auto'=>'🚗']; ?>
                    <?= ($vozidlaIkony[$k->vozidlo] ?? '') . ' ' . ucfirst($k->vozidlo) ?>
                  </td>
                  <td class="fw-600"><?= (int)$k->doruceni_dnes ?></td>
                  <td>⭐ <?= number_format((float)$k->hodnotenie, 1) ?></td>
                  <td>
                    <?php if ($k->stav === 'online'): ?>
                      <span class="badge badge-success">● Online</span>
                    <?php elseif ($k->stav === 'zaneprazdneny'): ?>
                      <span class="badge badge-warning">● Zaneprázdnený</span>
                    <?php else: ?>
                      <span class="badge badge-gray">⚫ Offline</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="actions">
                      <button class="btn btn-ghost btn-sm btn-edit-kurier"
                        data-id="<?= $k->id ?>"
                        data-meno="<?= Helper::h($k->meno) ?>"
                        data-priezvisko="<?= Helper::h($k->priezvisko) ?>"
                        data-email="<?= Helper::h($k->email ?? '') ?>"
                        data-telefon="<?= Helper::h($k->telefon ?? '') ?>"
                        data-vozidlo="<?= $k->vozidlo ?>"
                        data-stav="<?= $k->stav ?>"
                        title="Upraviť">✏️</button>
                      <a href="?delete=<?= $k->id ?>" class="btn btn-outline-danger btn-sm"
                         onclick="return confirm('Vymazať kuriéra <?= Helper::h($k->meno . ' ' . $k->priezvisko) ?>?')" title="Vymazať">🗑</a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="courierModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="courierModalTitle">Pridať kuriéra</h3>
      <button class="modal-close"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <form id="courierForm" method="POST" action="">
      <input type="hidden" id="courierId" name="id" value="">
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Meno</label><input type="text" name="meno" id="kMeno" class="form-control" required></div>
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Priezvisko</label><input type="text" name="priezvisko" id="kPriezvisko" class="form-control" required></div>
        </div>
        <div class="form-group mt-3"><label class="form-label">E-mail</label><input type="email" name="email" id="kEmail" class="form-control"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Telefón</label><input type="tel" name="telefon" id="kTelefon" class="form-control"></div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Vozidlo</label>
            <select name="vozidlo" id="kVozidlo" class="form-control">
              <option value="skuter">🛵 Skuter</option>
              <option value="bicykel">🚲 Bicykel</option>
              <option value="auto">🚗 Auto</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Stav</label>
            <select name="stav" id="kStav" class="form-control">
              <option value="offline">Offline</option>
              <option value="online">Online</option>
              <option value="zaneprazdneny">Zaneprázdnený</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="courierModal">Zrušiť</button>
        <button type="submit" class="btn btn-primary">Uložiť</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.btn-edit-kurier').forEach(btn => {
  btn.addEventListener('click', function() {
    const d = this.dataset;
    document.getElementById('courierModalTitle').textContent = 'Upraviť kuriéra';
    document.getElementById('courierId').value    = d.id;
    document.getElementById('kMeno').value        = d.meno;
    document.getElementById('kPriezvisko').value  = d.priezvisko;
    document.getElementById('kEmail').value       = d.email;
    document.getElementById('kTelefon').value     = d.telefon;
    document.getElementById('kVozidlo').value     = d.vozidlo;
    document.getElementById('kStav').value        = d.stav;
    document.getElementById('courierModal').classList.add('active');
  });
});
document.querySelector('[data-modal-open="courierModal"]').addEventListener('click', function() {
  document.getElementById('courierModalTitle').textContent = 'Pridať kuriéra';
  document.getElementById('courierForm').reset();
  document.getElementById('courierId').value = '';
});
</script>
</body>
</html>
