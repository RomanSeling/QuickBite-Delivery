<?php
/**
 * POST endpoint pre odoslanie hodnotenia reštaurácie.
 * Vzor PRG — po spracovaní vždy presmeruje, nikdy nevypisuje HTML.
 */
require_once __DIR__ . '/../../app/core/App.php';
App::init();

// Len prihlásený zákazník
if (!Auth::check() || !Auth::isCustomer()) {
    Redirect::redirect('login.php?redirect=moje-objednavky.php');
}

// Akceptujeme len POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Redirect::redirect('moje-objednavky.php');
}

$restauraciaId = (int)($_POST['restaurant_id'] ?? 0);
$stars         = (int)($_POST['stars']         ?? 0);
$comment       = trim($_POST['comment']        ?? '');

$redirectTo = trim($_POST['redirect_to'] ?? '');
if (!preg_match('/^restaurant-detail\.php\?id=\d+$/', $redirectTo)) {
    $redirectTo = 'moje-objednavky.php';
}

// Validácia vstupov
if ($restauraciaId <= 0 || $stars < 1 || $stars > 5) {
    Helper::flash('danger', 'Neplatné hodnotenie. Vyberte 1–5 hviezdičiek.');
    Redirect::redirect($redirectTo);
}

try {
    $db = (new Database())->getConnection();

    // Načítaj zakaznik_id pre aktuálneho používateľa
    $stmt = $db->prepare("SELECT id FROM zakaznici WHERE user_id = :uid");
    $stmt->execute(['uid' => Auth::id()]);
    $zakaznik = $stmt->fetch();

    if (!$zakaznik) {
        Helper::flash('danger', 'Zákaznícky profil nebol nájdený.');
        Redirect::redirect($redirectTo);
    }

    // Bezpečnostná kontrola: zákazník musí mať aspoň jednu doručenú objednávku
    // z tejto reštaurácie — zabraňuje hodnoteniu bez nákupu
    $checkStmt = $db->prepare("
        SELECT COUNT(*) FROM objednavky
        WHERE zakaznik_id = :zid
          AND restauracia_id = :rid
          AND stav = 'dorucena'
    ");
    $checkStmt->execute(['zid' => $zakaznik->id, 'rid' => $restauraciaId]);

    if ((int)$checkStmt->fetchColumn() === 0) {
        Helper::flash('danger', 'Môžete hodnotiť len reštaurácie, z ktorých bola objednávka doručená.');
        Redirect::redirect($redirectTo);
    }

    // INSERT alebo UPDATE (UNIQUE KEY customer_id + restaurant_id)
    // ON DUPLICATE KEY UPDATE umožňuje zákazníkovi aktualizovať hodnotenie
    $stmt = $db->prepare("
        INSERT INTO reviews (restaurant_id, customer_id, stars, comment)
        VALUES (:rid, :cid, :stars, :comment)
        ON DUPLICATE KEY UPDATE
            stars   = VALUES(stars),
            comment = VALUES(comment)
    ");
    $stmt->execute([
        'rid'     => $restauraciaId,
        'cid'     => $zakaznik->id,
        'stars'   => $stars,
        'comment' => $comment,
    ]);

    // Synchronizuj statický stĺpec hodnotenie s aktuálnym priemerom
    // — admin panel tak vidí vždy aktuálnu hodnotu bez JOIN
    $db->prepare("
        UPDATE restauracie
        SET hodnotenie = (
            SELECT ROUND(AVG(stars), 1) FROM reviews WHERE restaurant_id = :rid
        )
        WHERE id = :rid
    ")->execute(['rid' => $restauraciaId]);

    Helper::flash('success', 'Ďakujeme za hodnotenie! ⭐');

} catch (PDOException $e) {
    Helper::log("review-submit.php ERROR: " . $e->getMessage());
    Helper::flash('danger', 'Nastala chyba pri ukladaní hodnotenia. Skúste prosím znova.');
}

Redirect::redirect($redirectTo);
