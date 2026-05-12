<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();
Auth::requireLogin();

require_once __DIR__ . '/../../app/controllers/NastaveniaController.php';
NastaveniaController::handle();
$data = NastaveniaController::getData();
extract($data);
// $settings, $currentUser, $novychObjednavok

$s = function(string $key, string $default = '') use ($settings): string {
    return htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nastavenia – QuickBite Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
  <!-- SIDEBAR -->
  <?php $sidebarActive = 'nastavenia'; require __DIR__ . '/partials/sidebar.php'; ?>

  <div class="dash-main">
    <header class="dash-header">
      <button class="sidebar-toggle" id="sidebarToggle"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="dash-header-title">
        <h2>Nastavenia</h2>
        <p>Konfigurácia systému QuickBite</p>
      </div>
      <div class="dash-header-right">
        <button type="submit" form="settingsForm" class="btn btn-primary btn-sm">Uložiť zmeny</button>
        <div class="header-avatar"><?= Helper::h(Auth::initials()) ?></div>
      </div>
    </header>

    <main class="dash-content">

      <?= Helper::renderFlash() ?>

      <form id="settingsForm" method="POST" action="">
      <div class="settings-layout">

        <!-- Settings menu -->
        <div class="settings-menu">
          <a href="#general"       class="settings-menu-link active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 005.45 18.36M4.93 4.93a10 10 0 0013.14 15.14"/></svg>Všeobecné
          </a>
          <a href="#account" class="settings-menu-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Účet
          </a>
          <a href="#delivery" class="settings-menu-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>Doručenie
          </a>
        </div>

        <!-- Settings content -->
        <div>

          <!-- General -->
          <div class="settings-block" id="general">
            <div class="settings-block-header">
              <div class="settings-block-icon orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 005.45 18.36M4.93 4.93a10 10 0 0013.14 15.14"/></svg>
              </div>
              <div>
                <div class="settings-block-heading">Všeobecné nastavenia</div>
                <div class="settings-block-desc">Základné informácie a konfigurácia systému</div>
              </div>
            </div>
            <div class="settings-two-col">
              <div class="form-group"><label class="form-label">Názov aplikácie</label><input type="text" name="nazov_platformy" class="form-control" value="<?= $s('nazov_platformy','QuickBite') ?>"></div>
              <div class="form-group"><label class="form-label">Kontaktný e-mail</label><input type="email" name="kontaktny_email" class="form-control" value="<?= $s('kontaktny_email','admin@quickbite.sk') ?>"></div>
            </div>
            <div class="settings-two-col">
              <div class="form-group"><label class="form-label">Telefón podpory</label><input type="tel" name="telefon_podpory" class="form-control" value="<?= $s('telefon_podpory','+421 800 123 456') ?>"></div>
              <div class="form-group"><label class="form-label">Mena</label>
                <select class="form-control"><option selected>EUR (€)</option></select>
              </div>
            </div>
            <div class="form-group" style="margin-bottom:0;"><label class="form-label">Adresa prevádzky</label><input type="text" name="adresa_prevadzky" class="form-control" value="<?= $s('adresa_prevadzky','Obchodná 1, 811 06 Bratislava') ?>"></div>
          </div>

          <!-- Account -->
          <?php
            $profMeno       = $currentUser ? explode(' ', $currentUser->meno)[0] ?? '' : '';
            $profPriezvisko = $currentUser ? (explode(' ', $currentUser->meno)[1] ?? '') : '';
          ?>
          <div class="settings-block" id="account">
            <div class="settings-block-header">
              <div class="settings-block-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              </div>
              <div>
                <div class="settings-block-heading">Nastavenia účtu</div>
                <div class="settings-block-desc">Váš profil a kontaktné údaje</div>
              </div>
            </div>
            <div class="account-profile">
              <div class="account-avatar-lg"><?= Helper::h(Auth::initials()) ?></div>
              <div class="account-profile-info">
                <h4><?= Helper::h($currentUser->meno ?? Auth::meno()) ?></h4>
                <p><?= Auth::isAdmin() ? 'Administrátor' : 'Manažér' ?> · <?= Helper::h($currentUser->email ?? '') ?></p>
              </div>
            </div>
            <div class="settings-two-col">
              <div class="form-group"><label class="form-label">Meno</label><input type="text" name="prof_meno" class="form-control" value="<?= Helper::h($profMeno) ?>"></div>
              <div class="form-group"><label class="form-label">Priezvisko</label><input type="text" name="prof_priezvisko" class="form-control" value="<?= Helper::h($profPriezvisko) ?>"></div>
            </div>
            <div class="form-group" style="margin-bottom:0;"><label class="form-label">E-mailová adresa</label><input type="email" name="prof_email" class="form-control" value="<?= Helper::h($currentUser->email ?? '') ?>"></div>
            <div class="settings-two-col" style="margin-top:1rem;">
              <div class="form-group"><label class="form-label">Nové heslo (nepovinné)</label><input type="password" name="prof_heslo" class="form-control" placeholder="Minimálne 8 znakov"></div>
              <div class="form-group"><label class="form-label">Potvrdiť heslo</label><input type="password" class="form-control" placeholder="Zopakujte heslo"></div>
            </div>
          </div>

          <!-- Delivery -->
          <div class="settings-block" id="delivery">
            <div class="settings-block-header">
              <div class="settings-block-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
              </div>
              <div>
                <div class="settings-block-heading">Nastavenia doručenia</div>
                <div class="settings-block-desc">Poplatky, polomer a časové limity</div>
              </div>
            </div>
            <div class="settings-two-col">
              <div class="form-group"><label class="form-label">Základný poplatok za doručenie (€)</label><input type="number" name="poplatok_dorucenie" class="form-control" value="<?= $s('poplatok_dorucenie','1.99') ?>" step="0.10" min="0"></div>
              <div class="form-group"><label class="form-label">Min. objednávka pre doručenie zadarmo (€)</label><input type="number" name="min_dorucenie_zadarmo" class="form-control" value="<?= $s('min_dorucenie_zadarmo','25.00') ?>" step="1"></div>
            </div>
            <div class="settings-two-col">
              <div class="form-group" style="margin-bottom:0;"><label class="form-label">Max. polomer doručenia (km)</label><input type="number" name="max_polomer" class="form-control" value="<?= $s('max_polomer','15') ?>" step="1"></div>
              <div class="form-group" style="margin-bottom:0;"><label class="form-label">Cieľový čas doručenia (min)</label><input type="number" name="cas_dorucenia" class="form-control" value="<?= $s('cas_dorucenia','30') ?>" step="5"></div>
            </div>
          </div>

        </div>
      </div>
      </form>

    </main>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
document.querySelectorAll('.settings-menu-link').forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.settings-menu-link').forEach(l => l.classList.remove('active'));
    this.classList.add('active');
    const target = document.querySelector(this.getAttribute('href'));
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});
</script>
</body>
</html>
