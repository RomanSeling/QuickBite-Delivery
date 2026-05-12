<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

if (Auth::check()) {
    Redirect::redirect(Auth::isCustomer() ? 'restaurants.php' : 'dashboard.php');
}

require_once __DIR__ . '/../../app/controllers/AuthController.php';
$error = AuthController::handleLogin();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Prihlásenie – QuickBite</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="auth-page">

  <!-- ═══ Left panel ═══ -->
  <div class="auth-left">
    <div class="auth-emoji">🍕</div>
    <div class="auth-emoji">🛵</div>
    <div class="auth-emoji">🍔</div>

    <div class="auth-left-content">
      <a href="../../index.php" class="auth-left-logo">
        <div class="logo-icon">🍕</div>
        Quick<span style="color:var(--accent);">Bite</span>
      </a>
      <div class="auth-tagline">Rýchlo. Chutne.<br><span>Doručené.</span></div>
      <p class="auth-subtext">Prihláste sa a začnite objednávať.</p>
      <div class="auth-points">
        <div class="auth-point"><div class="auth-point-icon">⚡</div><span>Expresné doručenie do 30 minút</span></div>
        <div class="auth-point"><div class="auth-point-icon">📍</div><span>Sledovanie objednávky v reálnom čase</span></div>
        <div class="auth-point"><div class="auth-point-icon">🎁</div><span>Zbierajte body a získajte zľavy</span></div>
        <div class="auth-point"><div class="auth-point-icon">🛡️</div><span>Bezpečná a šifrovaná platba</span></div>
      </div>
    </div>
  </div>

  <!-- ═══ Right panel – form ═══ -->
  <div class="auth-right">
    <div class="auth-card">
      <div class="auth-card-header">
        <h2>Vitajte späť 👋</h2>
        <p>Prihláste sa do svojho účtu QuickBite</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger" style="padding:.75rem 1rem;margin-bottom:1rem;border-radius:10px;background:rgba(239,68,68,.12);color:#dc2626;font-size:.875rem;">
          <?= Helper::h($error) ?>
        </div>
      <?php endif; ?>

      <?php
        $loginRedirect = '';
        $raw = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
        $allowed = ['cart.php', 'restaurants.php'];
        if (in_array($raw, $allowed, true)) {
            $loginRedirect = $raw;
        }
      ?>
      <form action="" method="POST" id="loginForm">
        <div class="form-group">
          <label class="form-label" for="email">E-mailová adresa</label>
          <input type="email" id="email" name="email" class="form-control"
            placeholder="vas@email.com" required autocomplete="email"
            value="<?= Helper::h($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="password" style="display:flex;justify-content:space-between;align-items:center;">
            Heslo
            <a href="#" style="font-weight:500;font-size:.8rem;">Zabudli ste heslo?</a>
          </label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" class="form-control"
              placeholder="Vaše heslo" required autocomplete="current-password">
            <button type="button" class="password-toggle" aria-label="Zobraziť heslo">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1.5rem;">
          <input type="checkbox" id="remember" name="remember" style="width:16px;height:16px;accent-color:var(--primary);cursor:pointer;">
          <label for="remember" style="font-size:.875rem;color:var(--gray-600);cursor:pointer;">Zapamätať ma</label>
        </div>

        <?php if ($loginRedirect): ?>
          <input type="hidden" name="redirect" value="<?= Helper::h($loginRedirect) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary w-100" style="justify-content:center;padding:.72rem;">
          Prihlásiť sa
        </button>
      </form>

      <div class="auth-footer-link">
        Nemáte účet? <a href="register.php">Zaregistrujte sa zadarmo</a>
      </div>
      <div style="text-align:center;margin-top:1rem;">
        <a href="../../index.php" style="font-size:.82rem;color:var(--gray-400);">← Späť na hlavnú stránku</a>
      </div>
    </div>
  </div>

</div>

<script src="../assets/js/main.js"></script>
<?php require_once __DIR__ . '/partials/cookie-banner.php'; ?>
</body>
</html>

        