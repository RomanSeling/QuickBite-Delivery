<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

// Len pre prihlásených zákazníkov — admin má vlastné nastavenia.php
if (!Auth::check()) {
    Redirect::redirect('login.php?redirect=profil.php');
}
if (!Auth::isCustomer()) {
    Redirect::redirect('../../index.php');
}

$userId = Auth::id();
$errors  = [];
$success = false;

$db = (new Database())->getConnection();

// Načítanie aktuálnych dát
$uStmt = $db->prepare("SELECT meno, email FROM users WHERE id = :id");
$uStmt->execute(['id' => $userId]);
$user = $uStmt->fetch();

$zStmt = $db->prepare("SELECT id, meno, priezvisko, email, telefon, adresa FROM zakaznici WHERE user_id = :uid");
$zStmt->execute(['uid' => $userId]);
$zakaznik = $zStmt->fetch();

// Spracovanie formulára
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meno       = trim($_POST['meno']            ?? '');
    $priezvisko = trim($_POST['priezvisko']      ?? '');
    $email      = trim($_POST['email']           ?? '');
    $telefon    = trim($_POST['telefon']         ?? '');
    $adresa     = trim($_POST['adresa']          ?? '');
    $stareHeslo = $_POST['stare_heslo']          ?? '';
    $noveHeslo  = $_POST['nove_heslo']           ?? '';
    $potvrdenie = $_POST['potvrdenie_hesla']     ?? '';

    // Validácia povinných polí
    if (empty($meno))       $errors[] = 'Meno je povinné.';
    if (empty($priezvisko)) $errors[] = 'Priezvisko je povinné.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Zadajte platný e-mail.';
    }

    // Kontrola unikátnosti e-mailu (iný používateľ nesmie mať rovnaký)
    if (empty($errors) || !in_array('Zadajte platný e-mail.', $errors)) {
        $chkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $chkStmt->execute(['email' => $email, 'id' => $userId]);
        if ($chkStmt->fetch()) {
            $errors[] = 'Tento e-mail už používa iný účet.';
        }
    }

    // Validácia zmeny hesla — len ak zákazník vyplnil aspoň jedno pole hesla
    $changePassword = false;
    if (!empty($stareHeslo) || !empty($noveHeslo) || !empty($potvrdenie)) {
        $passStmt = $db->prepare("SELECT heslo FROM users WHERE id = :id");
        $passStmt->execute(['id' => $userId]);
        $passRow = $passStmt->fetch();

        if (!password_verify($stareHeslo, $passRow->heslo)) {
            $errors[] = 'Aktuálne heslo je nesprávne.';
        } elseif (strlen($noveHeslo) < 8) {
            $errors[] = 'Nové heslo musí mať aspoň 8 znakov.';
        } elseif ($noveHeslo !== $potvrdenie) {
            $errors[] = 'Nové heslo a jeho potvrdenie sa nezhodujú.';
        } else {
            $changePassword = true;
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Aktualizácia tabuľky users
            $menoFull = $meno . ' ' . $priezvisko;
            if ($changePassword) {
                $upUser = $db->prepare(
                    "UPDATE users SET meno = :meno, email = :email, heslo = :heslo WHERE id = :id"
                );
                $upUser->execute([
                    'meno'  => $menoFull,
                    'email' => $email,
                    'heslo' => password_hash($noveHeslo, PASSWORD_DEFAULT),
                    'id'    => $userId,
                ]);
            } else {
                $upUser = $db->prepare(
                    "UPDATE users SET meno = :meno, email = :email WHERE id = :id"
                );
                $upUser->execute(['meno' => $menoFull, 'email' => $email, 'id' => $userId]);
            }

            // Aktualizácia tabuľky zakaznici
            $upZak = $db->prepare(
                "UPDATE zakaznici SET meno = :meno, priezvisko = :priezvisko,
                    email = :email, telefon = :telefon, adresa = :adresa
                 WHERE user_id = :uid"
            );
            $upZak->execute([
                'meno'       => $meno,
                'priezvisko' => $priezvisko,
                'email'      => $email,
                'telefon'    => $telefon,
                'adresa'     => $adresa,
                'uid'        => $userId,
            ]);

            $db->commit();

            // Aktualizácia session mena (zobrazuje sa v navbare)
            $_SESSION['user_meno'] = $menoFull;

            // Refresh lokálnych premenných pre predvyplnenie formulára
            $user->meno            = $menoFull;
            $user->email           = $email;
            $zakaznik->meno        = $meno;
            $zakaznik->priezvisko  = $priezvisko;
            $zakaznik->email       = $email;
            $zakaznik->telefon     = $telefon;
            $zakaznik->adresa      = $adresa;

            $success = true;

        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            Helper::log("profil.php update ERROR: " . $e->getMessage());
            $errors[] = 'Nastala chyba pri ukladaní. Skúste prosím znova.';
        }
    }
}

$cartCount = array_sum(array_column($_SESSION['cart']['items'] ?? [], 'qty'));

// Predvyplnenie formulárových polí — z POST pri chybe, inak z DB
$f = [
    'meno'       => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['meno']       ?? '') : ($zakaznik->meno       ?? ''),
    'priezvisko' => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['priezvisko'] ?? '') : ($zakaznik->priezvisko ?? ''),
    'email'      => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['email']      ?? '') : ($user->email           ?? ''),
    'telefon'    => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['telefon']    ?? '') : ($zakaznik->telefon    ?? ''),
    'adresa'     => $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['adresa']     ?? '') : ($zakaznik->adresa     ?? ''),
];
?>
<!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Môj profil – QuickBite</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="restaurants.php" class="navbar-logo">
      <div class="logo-icon">🍕</div>
      <span class="logo-text">Quick<span>Bite</span></span>
    </a>
    <ul class="navbar-links">
      <li><a href="restaurants.php">Reštaurácie</a></li>
      <li><a href="moje-objednavky.php">Moje objednávky</a></li>
      <li><a href="profil.php" style="color:var(--primary);">Môj profil</a></li>
    </ul>
    <div class="navbar-cta">
      <a href="cart.php" class="nav-cart btn btn-ghost" title="Košík">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
        <span class="nav-cart-count"><?= $cartCount > 0 ? $cartCount : '' ?></span>
      </a>
      <div class="navbar-user">
        <span class="navbar-user-avatar"><?= Helper::h(Auth::initials()) ?></span>
        <span class="navbar-user-name"><?= Helper::h(Auth::meno()) ?></span>
        <a href="logout.php" class="btn btn-ghost btn-sm">Odhlásiť</a>
      </div>
    </div>
  </div>
</nav>

<main class="page-wrap">
  <div class="page-hero-sm">
    <div class="page-hero-sm-inner">
      <h1>Môj profil ⚙️</h1>
      <p>Spravujte svoje osobné údaje a prihlasovacie heslo.</p>
    </div>
  </div>

  <div style="max-width:680px;margin:0 auto;padding:0 1.5rem 3rem;">

    <!-- Flash správy -->
    <?php if ($success): ?>
    <div style="padding:.875rem 1.1rem;margin-bottom:1.5rem;border-radius:10px;
                background:rgba(16,185,129,.12);color:#059669;font-weight:500;">
      ✓ Profil bol úspešne uložený.
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div style="padding:.875rem 1.1rem;margin-bottom:1.5rem;border-radius:10px;
                background:rgba(239,68,68,.1);color:#dc2626;">
      <strong>Opravte nasledujúce chyby:</strong>
      <ul style="margin:.5rem 0 0 1.2rem;padding:0;">
        <?php foreach ($errors as $e): ?>
          <li style="margin:.2rem 0;"><?= Helper::h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>

      <!-- ── Osobné údaje ── -->
      <div class="card" style="margin-bottom:1.25rem;">
        <div class="card-body" style="padding:1.5rem;">
          <h3 style="margin:0 0 1.25rem;font-size:1rem;color:var(--gray-900);">
            👤 Osobné údaje
          </h3>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Meno <span style="color:var(--danger);">*</span></label>
              <input type="text" name="meno" class="form-control"
                     value="<?= Helper::h($f['meno']) ?>"
                     placeholder="Ján" required>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Priezvisko <span style="color:var(--danger);">*</span></label>
              <input type="text" name="priezvisko" class="form-control"
                     value="<?= Helper::h($f['priezvisko']) ?>"
                     placeholder="Novák" required>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label">E-mail <span style="color:var(--danger);">*</span></label>
            <input type="email" name="email" class="form-control"
                   value="<?= Helper::h($f['email']) ?>"
                   placeholder="jan.novak@email.com" required>
          </div>

          <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label">Telefón</label>
            <input type="tel" name="telefon" class="form-control"
                   value="<?= Helper::h($f['telefon']) ?>"
                   placeholder="+421 9XX XXX XXX">
          </div>

          <div class="form-group" style="margin:0;">
            <label class="form-label">Adresa doručenia</label>
            <textarea name="adresa" class="form-control" rows="2"
                      placeholder="Ulica číslo, Mesto"><?= Helper::h($f['adresa']) ?></textarea>
            <span style="font-size:.75rem;color:var(--gray-400);">
              Táto adresa sa predvyplní pri každej objednávke.
            </span>
          </div>
        </div>
      </div>

      <!-- ── Zmena hesla ── -->
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body" style="padding:1.5rem;">
          <h3 style="margin:0 0 .25rem;font-size:1rem;color:var(--gray-900);">
            🔒 Zmena hesla
          </h3>
          <p style="margin:0 0 1.25rem;font-size:.82rem;color:var(--gray-400);">
            Vypĺňajte len ak chcete zmeniť heslo. Nové heslo musí mať aspoň 8 znakov.
          </p>

          <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label">Aktuálne heslo</label>
            <input type="password" name="stare_heslo" class="form-control"
                   autocomplete="current-password" placeholder="Vaše súčasné heslo">
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group" style="margin:0;">
              <label class="form-label">Nové heslo</label>
              <input type="password" name="nove_heslo" class="form-control"
                     autocomplete="new-password" placeholder="Aspoň 8 znakov">
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label">Potvrdenie nového hesla</label>
              <input type="password" name="potvrdenie_hesla" class="form-control"
                     autocomplete="new-password" placeholder="Zopakujte nové heslo">
            </div>
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primary" style="padding:.75rem 2rem;font-size:.95rem;">
          Uložiť zmeny
        </button>
      </div>

    </form>
  </div>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>
