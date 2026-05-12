<?php if (!isset($_COOKIE['qb_cookie_consent'])): ?>
<div id="cookieBanner" class="cookie-banner" role="dialog" aria-label="Súhlas s cookies">
  <div class="cookie-banner-inner">
    <div class="cookie-banner-text">
      <strong>🍪 Táto stránka používa cookies</strong>
      Používame nevyhnutné cookies (prihlásenie, košík) a analytické cookies na zlepšenie
      vášho zážitku. Môžete si vybrať, ktoré súbory cookie prijmete.
    </div>
    <div class="cookie-banner-actions">
      <button class="btn btn-ghost btn-sm cookie-btn-essential" onclick="setCookieConsent('essential')">
        Len nevyhnutné
      </button>
      <button class="btn btn-primary btn-sm" onclick="setCookieConsent('all')">
        Prijať všetky
      </button>
    </div>
  </div>
</div>
<?php endif; ?>
