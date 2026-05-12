<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/ObjednavkaController.php';
ObjednavkaController::handle();
$data = ObjednavkaController::getData();
extract($data);
// $objednavky, $statusCounts, $novychObjednavok, $zakaznici, $restauracie, $kurieri, $aktualnyStav
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Objednávky – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'objednavky'; require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="dash-header-title">
        <h2>Objednávky</h2>
        <p>Správa a prehľad všetkých objednávok</p>
      </div>
      <div class="dash-header-right">
        <button class="btn btn-primary btn-sm" data-modal-open="orderModal">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Nová objednávka
        </button>
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <!-- Status tabs -->
      <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
        <a href="objednavky.php" class="btn <?= $aktualnyStav==='' ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
          Všetky <span class="badge" style="background:rgba(<?= $aktualnyStav==='' ? '255,255,255,.25' : '0,0,0,.08' ?>);color:<?= $aktualnyStav==='' ? '#fff' : 'var(--gray-700)' ?>;margin-left:.3rem;"><?= array_sum($statusCounts) ?></span>
        </a>
        <?php foreach ([
          'nova'       => ['🔵 Nové',        'badge-info'],
          'pripravuje' => ['🟡 Pripravuje sa','badge-warning'],
          'ceste'      => ['🟣 Na ceste',     'badge-purple'],
          'dorucena'   => ['🟢 Doručené',     'badge-success'],
          'zrusena'    => ['🔴 Zrušené',      'badge-danger'],
        ] as $s => [$label, $badgeClass]): ?>
        <a href="?stav=<?= $s ?>" class="btn <?= $aktualnyStav===$s ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
          <?= $label ?> <span class="badge <?= $badgeClass ?>" style="margin-left:.3rem;"><?= $statusCounts[$s] ?? 0 ?></span>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <h3>🛵 Všetky objednávky</h3>
          <form method="GET" style="display:flex;gap:.5rem;align-items:center;">
            <?php if ($aktualnyStav): ?><input type="hidden" name="stav" value="<?= Helper::h($aktualnyStav) ?>"><?php endif; ?>
            <div class="search-input-wrap" style="min-width:200px;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="search" class="search-input" placeholder="Hľadať..." value="<?= Helper::h($_GET['search'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-ghost btn-sm">Hľadať</button>
          </form>
        </div>
        <div class="table-wrapper">
          <table class="table">
            <thead>
              <tr>
                <th>#ID</th>
                <th>Zákazník</th>
                <th>Reštaurácia</th>
                <th>Položky</th>
                <th>Suma</th>
                <th>Platba</th>
                <th>Stav</th>
                <th>Čas</th>
                <th>Kuriér</th>
                <th>Akcie</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($objednavky)): ?>
                <tr><td colspan="10" style="text-align:center;color:var(--gray-400);padding:2rem;">Žiadne objednávky</td></tr>
              <?php else: ?>
                <?php foreach ($objednavky as $o): ?>
                <tr>
                  <td><strong>#<?= $o->id ?></strong></td>
                  <td><?= Helper::h($o->zakaznik_meno ?? '–') ?></td>
                  <td style="font-size:.875rem;"><?= Helper::h($o->restauracia_nazov ?? '–') ?></td>
                  <td style="font-size:.8rem;color:var(--gray-500);"><?= Helper::h($o->polozky) ?></td>
                  <td class="fw-600" style="color:<?= $o->stav==='zrusena' ? 'var(--gray-400);text-decoration:line-through' : 'var(--primary)' ?>;"><?= Helper::eur((float)$o->suma) ?></td>
                  <td>
                    <?php $platbaLabels = ['karta'=>'Karta','hotovost'=>'Hotovosť','apple_pay'=>'Apple Pay']; ?>
                    <span class="badge badge-gray"><?= Helper::h($platbaLabels[$o->platba] ?? $o->platba) ?></span>
                  </td>
                  <td><?= Helper::stavBadge($o->stav) ?></td>
                  <td style="font-size:.8rem;"><?= date('H:i', strtotime($o->created_at)) ?></td>
                  <td style="font-size:.8rem;"><?= Helper::h($o->kurier_meno ?? '—') ?></td>
                  <td>
                    <div class="actions">
                      <button class="btn btn-ghost btn-sm btn-edit-order"
                        data-id="<?= $o->id ?>"
                        data-zakaznik_id="<?= $o->zakaznik_id ?>"
                        data-restauracia_id="<?= $o->restauracia_id ?>"
                        data-kurier_id="<?= $o->kurier_id ?? '' ?>"
                        data-polozky="<?= Helper::h($o->polozky) ?>"
                        data-suma="<?= $o->suma ?>"
                        data-platba="<?= $o->platba ?>"
                        data-stav="<?= $o->stav ?>"
                        data-adresa="<?= Helper::h($o->adresa_dorucenia ?? '') ?>"
                        data-poznamka="<?= Helper::h($o->poznamka ?? '') ?>"
                        title="Upraviť">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <a href="?delete=<?= $o->id ?>"
                         class="btn btn-outline-danger btn-sm"
                         onclick="return confirm('Vymazať objednávku #<?= $o->id ?>?')"
                         title="Vymazať">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                      </a>
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
            Zobrazujem <?= $pgFrom ?>–<?= $pgTo ?> z <?= $pgTotal ?> objednávok
          </span>
          <?= $pgNav ?>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- MODAL – Pridať / Upraviť objednávku -->
<div class="modal-overlay" id="orderModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="orderModalTitle">Nová objednávka</h3>
      <button class="modal-close" aria-label="Zavrieť">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="orderForm" method="POST" action="">
      <input type="hidden" id="orderId" name="id" value="">
      <div class="modal-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Zákazník</label>
            <select name="zakaznik_id" class="form-control" id="orderZakaznik" required>
              <option value="">– vybrať –</option>
              <?php foreach ($zakaznici as $z): ?>
                <option value="<?= $z->id ?>"><?= Helper::h($z->meno_cele) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Reštaurácia</label>
            <select name="restauracia_id" class="form-control" id="orderRestauracia" required>
              <option value="">– vybrať –</option>
              <?php foreach ($restauracie as $r): ?>
                <option value="<?= $r->id ?>"><?= Helper::h($r->nazov) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group mt-3">
          <label class="form-label">Položky</label>
          <input type="text" name="polozky" id="orderPolozky" class="form-control" placeholder="Napr. Margherita, Cola ×2" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-top:1rem;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Suma (€)</label>
            <input type="number" name="suma" id="orderSuma" class="form-control" placeholder="0.00" step="0.01" min="0" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Platba</label>
            <select name="platba" id="orderPlatba" class="form-control">
              <option value="karta">Karta</option>
              <option value="hotovost">Hotovosť</option>
              <option value="apple_pay">Apple Pay</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Stav</label>
            <select name="stav" id="orderStav" class="form-control">
              <option value="nova">Nová</option>
              <option value="pripravuje">Pripravuje sa</option>
              <option value="ceste">Na ceste</option>
              <option value="dorucena">Doručená</option>
              <option value="zrusena">Zrušená</option>
            </select>
          </div>
        </div>
        <div class="form-group mt-3">
          <label class="form-label">Kuriér</label>
          <select name="kurier_id" id="orderKurier" class="form-control">
            <option value="">– nepriradený –</option>
            <?php foreach ($kurieri as $k): ?>
              <option value="<?= $k->id ?>"><?= Helper::h($k->meno_cele) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Adresa doručenia</label>
          <input type="text" name="adresa_dorucenia" id="orderAdresa" class="form-control" placeholder="Ulica, číslo, mesto">
        </div>
        <div class="form-group">
          <label class="form-label">Poznámka</label>
          <textarea name="poznamka" id="orderPoznamka" class="form-control" placeholder="Špeciálne požiadavky..." style="min-height:80px;"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="orderModal">Zrušiť</button>
        <button type="submit" class="btn btn-primary">Uložiť objednávku</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Naplnenie formulára pri editácii
document.querySelectorAll('.btn-edit-order').forEach(btn => {
  btn.addEventListener('click', function() {
    const d = this.dataset;
    document.getElementById('orderModalTitle').textContent = 'Upraviť objednávku #' + d.id;
    document.getElementById('orderId').value          = d.id;
    document.getElementById('orderZakaznik').value    = d.zakaznik_id;
    document.getElementById('orderRestauracia').value = d.restauracia_id;
    document.getElementById('orderKurier').value      = d.kurier_id || '';
    document.getElementById('orderPolozky').value     = d.polozky;
    document.getElementById('orderSuma').value        = d.suma;
    document.getElementById('orderPlatba').value      = d.platba;
    document.getElementById('orderStav').value        = d.stav;
    document.getElementById('orderAdresa').value      = d.adresa;
    document.getElementById('orderPoznamka').value    = d.poznamka;
    document.getElementById('orderModal').classList.add('active');
  });
});

// Reset formulára pri pridávaní novej
document.querySelector('[data-modal-open="orderModal"]').addEventListener('click', function() {
  document.getElementById('orderModalTitle').textContent = 'Nová objednávka';
  document.getElementById('orderForm').reset();
  document.getElementById('orderId').value = '';
});
</script>
</body>
</html>
