<?php
/**
 * Zdieľaný sidebar pre admin panel.
 * Pred include nastav: $sidebarActive = 'dashboard' | 'objednavky' | 'restauracie'
 *                                     | 'produkty' | 'statistiky' | 'zakaznici'
 *                                     | 'kurieri' | 'nastavenia'
 */

// Počet nových objednávok — sidebar si ho načíta sám, nevyžaduje premennú z rodiča
$_sbNova = 0;
try {
    $_sbStmt = (new Database())->getConnection()
                ->query("SELECT COUNT(*) FROM objednavky WHERE stav = 'nova'");
    $_sbNova = (int)$_sbStmt->fetchColumn();
} catch (PDOException $_sbE) {
    Helper::log('sidebar.php nova count ERROR: ' . $_sbE->getMessage());
}

// Pomocná closure: vráti triedu sidebar-link s prípadným active
$_sbA = static fn(string $page): string =>
    ($sidebarActive ?? '') === $page ? 'sidebar-link active' : 'sidebar-link';
?>
<aside class="sidebar" id="sidebar">
  <a href="../../index.php" class="sidebar-logo">
    <div class="logo-icon" style="width:32px;height:32px;font-size:.9rem;">🍕</div>
    Quick<span style="color:var(--accent);">Bite</span>
  </a>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Prehľad</div>

    <a href="dashboard.php" class="<?= $_sbA('dashboard') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>

    <a href="objednavky.php" class="<?= $_sbA('objednavky') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Objednávky
      <?php if ($_sbNova > 0): ?>
        <span class="badge badge-primary"><?= $_sbNova ?></span>
      <?php endif; ?>
    </a>

    <div class="sidebar-section">Správa</div>

    <a href="restauracie.php" class="<?= $_sbA('restauracie') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Reštaurácie
    </a>

    <a href="admin-produkty.php" class="<?= $_sbA('produkty') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      Produkty
    </a>

    <a href="zakaznici.php" class="<?= $_sbA('zakaznici') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Zákazníci
    </a>

    <a href="kurieri.php" class="<?= $_sbA('kurieri') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61H19.4a2 2 0 001.98-1.72L23 6H6"/></svg>
      Kuriéri
    </a>

    <a href="statistiky.php" class="<?= $_sbA('statistiky') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
      Štatistiky
    </a>

    <div class="sidebar-section">Systém</div>

    <a href="nastavenia.php" class="<?= $_sbA('nastavenia') ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 005.45 18.36M4.93 4.93a10 10 0 0013.14 15.14"/></svg>
      Nastavenia
    </a>
  </nav>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= Helper::h(Auth::initials()) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= Helper::h(Auth::meno()) ?></div>
        <div class="sidebar-user-role"><?= Auth::isAdmin() ? 'Administrátor' : 'Manažér' ?></div>
      </div>
      <a href="logout.php" class="sidebar-logout-btn" title="Odhlásiť sa">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>
