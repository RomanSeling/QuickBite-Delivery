<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

if (Auth::check()) {
    Redirect::redirect(Auth::isCustomer() ? 'restaurants.php' : 'dashboard.php');
}

require_once __DIR__ . '/../../app/controllers/AuthController.php';
$error = AuthController::handleRegister();
?><!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrácia – QuickBite</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="auth-page">

  <!-- ═══ Left panel ═══ -->
  <div class="auth-left">
    <div class="auth-emoji">🍣</div>
    <div class="auth-emoji">🎉</div>
    <div class="auth-emoji">🍜</div>

    <div class="auth-left-content">
      <a href="../../index.php" class="auth-left-logo">
        <div class="logo-icon">🍕</div>
        Quick<span style="color:var(--accent);">Bite</span>
      </a>
      <div class="auth-tagline">Prvá objednávka<br><span>zadarmo!</span></div>
      <p class="auth-subtext">Zaregistrujte sa a získajte doručenie prvej objednávky bez poplatku. Žiadna kreditná karta nie je potrebná.</p>
      <div class="auth-points">
        <div class="auth-point">
          <div class="auth-point-icon">🎁</div>
          <span>Prvé doručenie úplne zadarmo</span>
        </div>
        <div class="auth-point">
          <div class="auth-point-icon">⭐</div>
          <span>500+ overených reštaurácií</span>
        </div>
        <div class="auth-point">
          <div class="auth-point-icon">🛵</div>
          <span>Doručenie do 30 minút</span>
        </div>
        <div class="auth-point">
          <div class="auth-point-icon">📱</div>
          <span>Sledovanie v reálnom čase</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ Right panel – form ═══ -->
  <div class="auth-right">
    <div class="auth-card">
      <div class="auth-card-header">
        <h2>Vytvorte účet 🚀</h2>
        <p>Registrácia je rýchla a úplne zadarmo</p>
      </div>

      <!-- Alert placeholder -->
      <!-- <div class="alert alert-danger">E-mail je už registrovaný.</div> -->

      <?php if ($error): ?><div class="alert alert-danger" style="padding:.75rem 1rem;margin-bottom:1rem;border-radius:10px;background:rgba(239,68,68,.12);color:#dc2626;font-size:.875rem;"><?= Helper::h($error) ?></div><?php endif; ?><form action="" method="POST" id="registerForm">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="firstName">Meno</label>
            <input
              type="text"
              id="firstName"
              name="first_name"
              class="form-control"
              placeholder="Ján"
              required
              autocomplete="given-name"
            >
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label" for="lastName">Priezvisko</label>
            <input
              type="text"
              id="lastName"
              name="last_name"
              class="form-control"
              placeholder="Novák"
              required
              autocomplete="family-name"
            >
          </div>
        </div>

        <div class="form-group mt-3">
          <label class="form-label" for="email">E-mailová adresa</label>
          <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            placeholder="vas@email.com"
            required
            autocomplete="email"
          >
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Telefónne číslo</label>
          <input
            type="tel"
            id="phone"
            name="phone"
            class="form-control"
            placeholder="+421 9XX XXX XXX"
            autocomplete="tel"
          >
          <span class="form-hint">Pre potvrdenie objednávky a kontakt kuriéra</span>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Heslo</label>
          <div class="password-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Minimálne 8 znakov"
              required
              autocomplete="new-password"
              minlength="8"
            >
            <button type="button" class="password-toggle" aria-label="Zobraziť heslo">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="passwordConfirm">Potvrdiť heslo</label>
          <div class="password-wrapper">
            <input
              type="password"
              id="passwordConfirm"
              name="password_confirm"
              class="form-control"
              placeholder="Zopakujte heslo"
              required
              autocomplete="new-password"
            >
            <button type="button" class="password-toggle" aria-label="Zobraziť heslo">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div style="display:flex; align-items:flex-start; gap:.6rem; margin-bottom:1.5rem;">
          <input type="checkbox" id="terms" name="terms" required style="width:16px; height:16px; margin-top:3px; accent-color:var(--primary); cursor:pointer; flex-shrink:0;">
          <label for="terms" style="font-size:.82rem; color:var(--gray-600); cursor:pointer; line-height:1.5;">
            Súhlasím s <a href="#">Podmienkami používania</a> a <a href="#">Zásadami ochrany súkromia</a> spoločnosti QuickBite
          </label>
        </div>

        <button type="submit" class="btn btn-primary w-100" style="justify-content:center; padding:.72rem;">
          Vytvoriť účet zadarmo
        </button>
      </form>

      <div class="auth-footer-link">
        Už máte účet? <a href="login.php">Prihláste sa</a>
      </div>

      <div style="text-align:center; margin-top:1rem;">
        <a href="../../index.php" style="font-size:.82rem; color:var(--gray-400);">← Späť na hlavnú stránku</a>
      </div>
    </div>
  </div>

</div>

<script src="../assets/js/main.js"></script>
<?php require_once __DIR__ . '/partials/cookie-banner.php'; ?>
</body>
</html>


