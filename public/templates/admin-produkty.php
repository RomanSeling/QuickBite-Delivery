<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

if (!Auth::isAdmin()) {
    Redirect::redirect('dashboard.php');
}

$produktModel    = new Produkt();
$restauraciaModel = new Restauracia();

// ── CRUD akcie ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id             = (int)($_POST['id'] ?? 0);
    $restauracia_id = (int)($_POST['restauracia_id'] ?? 0);
    $nazov          = trim($_POST['nazov'] ?? '');
    $popis          = trim($_POST['popis'] ?? '');
    $cena           = (float)str_replace(',', '.', $_POST['cena'] ?? '0');
    $kategoria      = trim($_POST['kategoria'] ?? 'Jedlá');
    $emoji          = trim($_POST['emoji'] ?? '🍽️');
    $dostupny       = isset($_POST['dostupny']) ? 1 : 0;

    if ($nazov !== '' && $restauracia_id > 0 && $cena > 0) {
        if ($id > 0) {
            $ok = $produktModel->update($id, $restauracia_id, $nazov, $popis, $cena, $kategoria, $emoji, $dostupny);
            Helper::flash($ok ? 'Produkt bol upravený.' : 'Chyba pri úprave produktu.', $ok ? 'success' : 'error');
        } else {
            $ok = $produktModel->create($restauracia_id, $nazov, $popis, $cena, $kategoria, $emoji, $dostupny);
            Helper::flash($ok ? 'Produkt bol pridaný.' : 'Chyba pri pridávaní produktu.', $ok ? 'success' : 'error');
        }
    } else {
        Helper::flash('Vyplňte všetky povinné polia (reštaurácia, názov, cena > 0).', 'error');
    }
    Redirect::redirect('admin-produkty.php');
}

if (isset($_GET['delete'])) {
    $ok = $produktModel->delete((int)$_GET['delete']);
    Helper::flash($ok ? 'Produkt bol vymazaný.' : 'Chyba pri mazaní.', $ok ? 'success' : 'error');
    Redirect::redirect('admin-produkty.php');
}

// ── Dáta pre view ────────────────────────────────────────────────────────────
$produkty     = $produktModel->all();
$stats        = $produktModel->getStats();
$restauracie  = $restauraciaModel->allForSelect();

// Počet nových objednávok pre badge v sidebari
$objednavkaModel  = new Objednavka();
$novychObjednavok = ($objednavkaModel->getStatusCounts())['nova'] ?? 0;
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Produkty – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'produkty'; require __DIR__ . '/partials/sidebar.php'; ?>

  <!-- ── HLAVNÝ OBSAH ──────────────────────────────────────────────────────── -->
  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="dash-header-title">
        <h2>Produkty</h2>
        <p>Správa menu reštaurácií</p>
      </div>
      <div class="dash-header-right">
        <button class="btn btn-primary btn-sm" data-modal-open="produktModal">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Pridať produkt
        </button>
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <!-- Štatistické karty -->
      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card">
          <div class="stat-icon orange">🍽️</div>
          <div class="stat-info"><h4><?= (int)$stats->celkove ?></h4><p>Celkovo produktov</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">✅</div>
          <div class="stat-info"><h4><?= (int)$stats->dostupne ?></h4><p>Dostupné</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon amber">🏠</div>
          <div class="stat-info"><h4><?= (int)$stats->restauracii ?></h4><p>Reštaurácií</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">🏷️</div>
          <div class="stat-info"><h4><?= (int)$stats->kategorii ?></h4><p>Kategórií</p></div>
        </div>
      </div>

      <!-- Tabuľka produktov -->
      <div class="card">
        <div class="card-header">
          <h3>🍕 Zoznam produktov</h3>
          <div class="search-input-wrap" style="min-width:220px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="search-input" id="produktSearch" placeholder="Hľadať produkt...">
          </div>
        </div>
        <div class="table-wrapper">
          <table class="table" id="produktyTable">
            <thead>
              <tr>
                <th>Produkt</th>
                <th>Reštaurácia</th>
                <th>Kategória</th>
                <th>Cena</th>
                <th>Dostupný</th>
                <th>Akcie</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($produkty)): ?>
                <tr>
                  <td colspan="6" style="text-align:center;color:var(--gray-400);padding:2rem;">
                    Žiadne produkty. Pridajte prvý produkt tlačidlom hore.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($produkty as $p): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.75rem;">
                      <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#FFE0CC,#FFB380);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                        <?= $p->emoji ?>
                      </div>
                      <div>
                        <div class="fw-600" style="color:var(--gray-900);"><?= Helper::h($p->nazov) ?></div>
                        <?php if ($p->popis): ?>
                          <div style="font-size:.74rem;color:var(--gray-500);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= Helper::h($p->popis) ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td style="font-size:.85rem;"><?= Helper::h($p->restauracia_nazov) ?></td>
                  <td><span class="badge badge-primary"><?= Helper::h($p->kategoria) ?></span></td>
                  <td class="fw-600"><?= Helper::eur((float)$p->cena) ?></td>
                  <td>
                    <?php if ($p->dostupny): ?>
                      <span class="badge badge-success">● Áno</span>
                    <?php else: ?>
                      <span class="badge badge-danger">● Nie</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="actions">
                      <button class="btn btn-ghost btn-sm btn-edit-produkt"
                        data-id="<?= $p->id ?>"
                        data-restauracia-id="<?= $p->restauracia_id ?>"
                        data-nazov="<?= Helper::h($p->nazov) ?>"
                        data-popis="<?= Helper::h($p->popis ?? '') ?>"
                        data-cena="<?= $p->cena ?>"
                        data-kategoria="<?= Helper::h($p->kategoria) ?>"
                        data-emoji="<?= Helper::h($p->emoji) ?>"
                        data-dostupny="<?= $p->dostupny ?>"
                        title="Upraviť">✏️</button>
                      <a href="?delete=<?= $p->id ?>"
                         class="btn btn-outline-danger btn-sm"
                         onclick="return confirm('Vymazať produkt <?= Helper::h($p->nazov) ?>?')"
                         title="Vymazať">🗑</a>
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

<!-- ── MODAL – Pridať / Upraviť produkt ─────────────────────────────────────── -->
<div class="modal-overlay" id="produktModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="produktModalTitle">Nový produkt</h3>
      <button class="modal-close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="produktForm" method="POST" action="">
      <input type="hidden" id="pId" name="id" value="">
      <div class="modal-body">

        <!-- Reštaurácia -->
        <div class="form-group">
          <label class="form-label">Reštaurácia <span style="color:var(--danger);">*</span></label>
          <select name="restauracia_id" id="pRestauracia" class="form-control" required>
            <option value="">— Vyberte reštauráciu —</option>
            <?php foreach ($restauracie as $r): ?>
              <option value="<?= $r->id ?>"><?= Helper::h($r->nazov) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Názov + emoji -->
        <div style="display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:end;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Názov produktu <span style="color:var(--danger);">*</span></label>
            <input type="text" name="nazov" id="pNazov" class="form-control" placeholder="napr. Margherita" required>
          </div>
          <div class="form-group" style="margin-bottom:0;width:90px;">
            <label class="form-label">Emoji</label>
            <input type="text" name="emoji" id="pEmoji" class="form-control" value="🍽️" maxlength="4" style="text-align:center;font-size:1.4rem;">
          </div>
        </div>

        <!-- Popis -->
        <div class="form-group mt-3">
          <label class="form-label">Popis</label>
          <textarea name="popis" id="pPopis" class="form-control" style="min-height:60px;" placeholder="Krátky popis ingrediencií..."></textarea>
        </div>

        <!-- Cena + kategória -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Cena (€) <span style="color:var(--danger);">*</span></label>
            <input type="number" name="cena" id="pCena" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Kategória</label>
            <input type="text" name="kategoria" id="pKategoria" class="form-control" value="Jedlá" placeholder="Jedlá, Nápoje, Dezerty...">
          </div>
        </div>

        <!-- Dostupnosť -->
        <div class="form-group mt-3" style="margin-bottom:0;">
          <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;">
            <input type="checkbox" name="dostupny" id="pDostupny" value="1" checked style="width:16px;height:16px;">
            <span class="form-label" style="margin-bottom:0;">Produkt je dostupný (zobrazí sa zákazníkom)</span>
          </label>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="produktModal">Zrušiť</button>
        <button type="submit" class="btn btn-primary">Uložiť produkt</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// Tlačidlo "Pridať produkt" – reset formulára
document.querySelector('[data-modal-open="produktModal"]').addEventListener('click', function () {
  document.getElementById('produktModalTitle').textContent = 'Nový produkt';
  document.getElementById('produktForm').reset();
  document.getElementById('pId').value = '';
  document.getElementById('pEmoji').value = '🍽️';
  document.getElementById('pDostupny').checked = true;
  document.getElementById('produktModal').classList.add('active');
});

// Tlačidlá "Upraviť" – naplnenie formulára dátami
document.querySelectorAll('.btn-edit-produkt').forEach(btn => {
  btn.addEventListener('click', function () {
    const d = this.dataset;
    document.getElementById('produktModalTitle').textContent = 'Upraviť produkt';
    document.getElementById('pId').value                    = d.id;
    document.getElementById('pRestauracia').value           = d.restauraciaId;
    document.getElementById('pNazov').value                 = d.nazov;
    document.getElementById('pPopis').value                 = d.popis;
    document.getElementById('pCena').value                  = d.cena;
    document.getElementById('pKategoria').value             = d.kategoria;
    document.getElementById('pEmoji').value                 = d.emoji;
    document.getElementById('pDostupny').checked            = d.dostupny === '1';
    document.getElementById('produktModal').classList.add('active');
  });
});

// Vyhľadávanie v tabuľke (live filter)
document.getElementById('produktSearch').addEventListener('input', function () {
  const query = this.value.toLowerCase();
  document.querySelectorAll('#produktyTable tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
});
</script>
</body>
</html>
