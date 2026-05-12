<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/RestauraciaController.php';
RestauraciaController::handle();
$data = RestauraciaController::getData();
extract($data);
// $restauracie, $stats, $novychObjednavok
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reštaurácie – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'restauracie'; require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="dash-header-title">
        <h2>Reštaurácie</h2>
        <p>Správa partnerských reštaurácií</p>
      </div>
      <div class="dash-header-right">
        <button class="btn btn-primary btn-sm" data-modal-open="restaurantModal">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Pridať reštauráciu
        </button>
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div class="stat-icon orange">🍽️</div><div class="stat-info"><h4><?= (int)$stats->celkove ?></h4><p>Partnerské reštaurácie</p></div></div>
        <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><h4><?= (int)$stats->aktivne ?></h4><p>Aktívne (otvorené)</p></div></div>
        <div class="stat-card"><div class="stat-icon amber">⭐</div><div class="stat-info"><h4><?= number_format((float)$stats->avg_hodnotenie, 1) ?></h4><p>Priemerné hodnotenie</p></div></div>
        <div class="stat-card"><div class="stat-icon red">📦</div><div class="stat-info"><h4><?= (int)$stats->objednavky_dnes ?></h4><p>Objednávky dnes</p></div></div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>🏠 Zoznam reštaurácií</h3>
          <div class="search-input-wrap" style="min-width:220px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="search-input" placeholder="Hľadať reštauráciu...">
          </div>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Reštaurácia</th>
                <th>Kategória</th>
                <th>Adresa</th>
                <th>Hodnotenie</th>
                <th>Objednávky dnes</th>
                <th>Min. objednávka</th>
                <th>Stav</th>
                <th>Akcie</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($restauracie)): ?>
                <tr><td colspan="8" style="text-align:center;color:var(--gray-400);padding:2rem;">Žiadne reštaurácie</td></tr>
              <?php else: ?>
                <?php foreach ($restauracie as $r): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.75rem;">
                      <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#FFE0CC,#FFB380);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">🍽️</div>
                      <div>
                        <div class="fw-600" style="color:var(--gray-900);"><?= Helper::h($r->nazov) ?></div>
                        <div style="font-size:.74rem;color:var(--gray-500);"><?= Helper::h($r->email ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge badge-primary"><?= Helper::h($r->kategoria ?? '–') ?></span></td>
                  <td style="font-size:.82rem;color:var(--gray-600);"><?= Helper::h($r->adresa ?? '–') ?></td>
                  <td>⭐ <?= number_format((float)$r->hodnotenie, 1) ?></td>
                  <td class="fw-600"><?= (int)$r->objednavky_dnes ?></td>
                  <td><?= Helper::eur((float)$r->min_objednavka) ?></td>
                  <td>
                    <?php if ($r->stav === 'aktivna'): ?>
                      <span class="badge badge-success">● Otvorená</span>
                    <?php else: ?>
                      <span class="badge badge-danger">● Zatvorená</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="actions">
                      <button class="btn btn-ghost btn-sm btn-edit-restauracia"
                        data-id="<?= $r->id ?>"
                        data-nazov="<?= Helper::h($r->nazov) ?>"
                        data-kategoria="<?= Helper::h($r->kategoria ?? '') ?>"
                        data-adresa="<?= Helper::h($r->adresa ?? '') ?>"
                        data-telefon="<?= Helper::h($r->telefon ?? '') ?>"
                        data-email="<?= Helper::h($r->email ?? '') ?>"
                        data-popis="<?= Helper::h($r->popis ?? '') ?>"
                        data-min_objednavka="<?= $r->min_objednavka ?>"
                        data-stav="<?= $r->stav ?>"
                        title="Upraviť">✏️</button>
                      <a href="?delete=<?= $r->id ?>" class="btn btn-outline-danger btn-sm"
                         onclick="return confirm('Vymazať reštauráciu <?= Helper::h($r->nazov) ?>?')" title="Vymazať">🗑</a>
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

<!-- MODAL – Pridať / Upraviť reštauráciu -->
<div class="modal-overlay" id="restaurantModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="restaurantModalTitle">Nová reštaurácia</h3>
      <button class="modal-close"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <form id="restaurantForm" method="POST" action="">
      <input type="hidden" id="restaurantId" name="id" value="">
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Názov</label><input type="text" name="nazov" id="rNazov" class="form-control" required></div>
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Kategória</label><input type="text" name="kategoria" id="rKategoria" class="form-control" placeholder="Talianska, Japonská..."></div>
        </div>
        <div class="form-group mt-3"><label class="form-label">Adresa</label><input type="text" name="adresa" id="rAdresa" class="form-control"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Telefón</label><input type="tel" name="telefon" id="rTelefon" class="form-control"></div>
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">E-mail</label><input type="email" name="email" id="rEmail" class="form-control"></div>
        </div>
        <div class="form-group mt-3"><label class="form-label">Popis</label><textarea name="popis" id="rPopis" class="form-control" style="min-height:60px;"></textarea></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Min. objednávka (€)</label><input type="number" name="min_objednavka" id="rMin" class="form-control" value="5.00" step="0.50" min="0"></div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Stav</label>
            <select name="stav" id="rStav" class="form-control">
              <option value="aktivna">Aktívna</option>
              <option value="neaktivna">Neaktívna</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="restaurantModal">Zrušiť</button>
        <button type="submit" class="btn btn-primary">Uložiť</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.btn-edit-restauracia').forEach(btn => {
  btn.addEventListener('click', function() {
    const d = this.dataset;
    document.getElementById('restaurantModalTitle').textContent = 'Upraviť reštauráciu';
    document.getElementById('restaurantId').value = d.id;
    document.getElementById('rNazov').value        = d.nazov;
    document.getElementById('rKategoria').value    = d.kategoria;
    document.getElementById('rAdresa').value       = d.adresa;
    document.getElementById('rTelefon').value      = d.telefon;
    document.getElementById('rEmail').value        = d.email;
    document.getElementById('rPopis').value        = d.popis;
    document.getElementById('rMin').value          = d.min_objednavka;
    document.getElementById('rStav').value         = d.stav;
    document.getElementById('restaurantModal').classList.add('active');
  });
});
document.querySelector('[data-modal-open="restaurantModal"]').addEventListener('click', function() {
  document.getElementById('restaurantModalTitle').textContent = 'Nová reštaurácia';
  document.getElementById('restaurantForm').reset();
  document.getElementById('restaurantId').value = '';
});
</script>
</body>
</html>
