<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/ZakaznikController.php';
ZakaznikController::handle();
$data = ZakaznikController::getData();
extract($data);
// $zakaznici, $stats, $novychObjednavok
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zákazníci – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'zakaznici'; require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="dash-header-title">
        <h2>Zákazníci</h2>
        <p>Správa registrovaných zákazníkov</p>
      </div>
      <div class="dash-header-right">
        <button class="btn btn-primary btn-sm" data-modal-open="customerModal">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Pridať zákazníka
        </button>
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card"><div class="stat-icon orange">👥</div><div class="stat-info"><h4><?= (int)$stats->celkove ?></h4><p>Registrovaní zákazníci</p></div></div>
        <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><h4><?= (int)$stats->aktivni ?></h4><p>Aktívni (posledných 30 dní)</p></div></div>
        <div class="stat-card"><div class="stat-icon amber">💶</div><div class="stat-info"><h4><?= Helper::eur((float)$stats->avg_hodnota) ?></h4><p>Priemerná hodnota objednávky</p></div></div>
        <div class="stat-card"><div class="stat-icon red">🔁</div><div class="stat-info"><h4>–</h4><p>Miera opakovaných obj.</p></div></div>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>👥 Zoznam zákazníkov</h3>
          <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
            <div class="search-input-wrap" style="min-width:220px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="search" class="search-input" placeholder="Hľadať zákazníka..." value="<?= Helper::h($pgSearch) ?>">
            </div>
            <button type="submit" class="btn btn-ghost btn-sm">Hľadať</button>
            <?php if ($pgSearch): ?>
              <a href="zakaznici.php" class="btn btn-ghost btn-sm" style="color:var(--danger);">× Zrušiť</a>
            <?php endif; ?>
          </form>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>Zákazník</th>
                <th>E-mail</th>
                <th>Telefón</th>
                <th>Adresa</th>
                <th>Objednávok</th>
                <th>Utratené</th>
                <th>Akcie</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($zakaznici)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:2rem;">Žiadni zákazníci</td></tr>
              <?php else: ?>
                <?php foreach ($zakaznici as $z): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                      <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#FFB300,#FF5722);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.78rem;font-weight:700;flex-shrink:0;">
                        <?= mb_strtoupper(mb_substr($z->meno,0,1) . mb_substr($z->priezvisko,0,1)) ?>
                      </div>
                      <div class="fw-600"><?= Helper::h($z->meno . ' ' . $z->priezvisko) ?></div>
                    </div>
                  </td>
                  <td style="font-size:.82rem;"><?= Helper::h($z->email) ?></td>
                  <td style="font-size:.82rem;"><?= Helper::h($z->telefon ?? '–') ?></td>
                  <td style="font-size:.8rem;color:var(--gray-500);"><?= Helper::h($z->adresa ?? '–') ?></td>
                  <td class="fw-600"><?= (int)$z->pocet_objednavok ?></td>
                  <td class="fw-600" style="color:var(--primary);"><?= Helper::eur((float)$z->utratene) ?></td>
                  <td>
                    <div class="actions">
                      <button class="btn btn-ghost btn-sm btn-edit-zakaznik"
                        data-id="<?= $z->id ?>"
                        data-meno="<?= Helper::h($z->meno) ?>"
                        data-priezvisko="<?= Helper::h($z->priezvisko) ?>"
                        data-email="<?= Helper::h($z->email) ?>"
                        data-telefon="<?= Helper::h($z->telefon ?? '') ?>"
                        data-adresa="<?= Helper::h($z->adresa ?? '') ?>"
                        title="Upraviť">✏️</button>
                      <a href="?delete=<?= $z->id ?>" class="btn btn-outline-danger btn-sm"
                         onclick="return confirm('Vymazať zákazníka <?= Helper::h($z->meno . ' ' . $z->priezvisko) ?>?')" title="Vymazať">🗑</a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
          <span style="font-size:.8rem;color:var(--gray-500);">
            <?php
              $pgFrom = $pgTotal === 0 ? 0 : ($pgPage - 1) * $pgPerPage + 1;
              $pgTo   = min($pgPage * $pgPerPage, $pgTotal);
            ?>
            Zobrazujem <?= $pgFrom ?>–<?= $pgTo ?> z <?= $pgTotal ?> zákazníkov
          </span>
          <?= $pgNav ?>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="customerModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="customerModalTitle">Nový zákazník</h3>
      <button class="modal-close"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
    </div>
    <form id="customerForm" method="POST" action="">
      <input type="hidden" id="customerId" name="id" value="">
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Meno</label><input type="text" name="meno" id="cMeno" class="form-control" required></div>
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Priezvisko</label><input type="text" name="priezvisko" id="cPriezvisko" class="form-control" required></div>
        </div>
        <div class="form-group mt-3"><label class="form-label">E-mail</label><input type="email" name="email" id="cEmail" class="form-control" required></div>
<div class="form-group"><label class="form-label">Heslo pre prihlasenie</label><input type="password" name="heslo" id="cHeslo" class="form-control" minlength="8" placeholder="Pri uprave nechajte prazdne ak nemenite"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Telefón</label><input type="tel" name="telefon" id="cTelefon" class="form-control"></div>
          <div class="form-group" style="margin-bottom:0;"><label class="form-label">Adresa</label><input type="text" name="adresa" id="cAdresa" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="customerModal">Zrušiť</button>
        <button type="submit" class="btn btn-primary">Uložiť</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.btn-edit-zakaznik').forEach(btn => {
  btn.addEventListener('click', function() {
    const d = this.dataset;
    document.getElementById('customerModalTitle').textContent = 'Upraviť zákazníka';
    document.getElementById('customerId').value    = d.id;
    document.getElementById('cMeno').value         = d.meno;
    document.getElementById('cPriezvisko').value   = d.priezvisko;
    document.getElementById('cEmail').value        = d.email;
    document.getElementById('cTelefon').value      = d.telefon;
    document.getElementById('cAdresa').value       = d.adresa;
    document.getElementById('cHeslo').value        = '';
    document.getElementById('customerModal').classList.add('active');
  });
});
document.querySelector('[data-modal-open="customerModal"]').addEventListener('click', function() {
  document.getElementById('customerModalTitle').textContent = 'Nový zákazník';
  document.getElementById('customerForm').reset();
  document.getElementById('customerId').value = '';
  document.getElementById('cHeslo').value = '';
});
</script>
</body>
</html>



