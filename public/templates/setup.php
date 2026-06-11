<?php
require_once __DIR__ . '/../../app/core/App.php';
App::init();

// Len admin môže spustiť setup
if (!Auth::check() || !Auth::isAdmin()) {
    die('Prístup zamietnutý. Prihláste sa ako admin.');
}

$db = (new Database())->getConnection();
$results = [];

$migrations = [
    'polozky_objednavky' => "CREATE TABLE IF NOT EXISTS polozky_objednavky (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        objednavka_id INT          NOT NULL,
        produkt_id    INT          DEFAULT NULL,
        nazov         VARCHAR(200) NOT NULL,
        mnozstvo      INT          NOT NULL DEFAULT 1,
        cena          DECIMAL(8,2) NOT NULL,
        FOREIGN KEY (objednavka_id) REFERENCES objednavky(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        restaurant_id INT       NOT NULL,
        customer_id   INT       NOT NULL,
        stars         TINYINT   NOT NULL,
        comment       TEXT,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_customer_restaurant (customer_id, restaurant_id),
        FOREIGN KEY (restaurant_id) REFERENCES restauracie(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id)   REFERENCES zakaznici(id)   ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($migrations as $table => $sql) {
    try {
        $db->exec($sql);
        $exists = (bool)$db->query("SHOW TABLES LIKE '$table'")->fetchColumn();
        $results[$table] = $exists ? '✅ OK – tabuľka existuje' : '❌ Vytvorenie zlyhalo';
    } catch (PDOException $e) {
        $results[$table] = '❌ Chyba: ' . $e->getMessage();
    }
}

// Štatistika DB
$stats = [];
foreach (['objednavky', 'polozky_objednavky', 'zakaznici', 'restauracie', 'reviews'] as $t) {
    try {
        $stats[$t] = (int)$db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    } catch (PDOException $e) {
        $stats[$t] = 'chyba';
    }
}
?><!DOCTYPE html>
<html lang="sk">
<head>
  <meta charset="UTF-8">
  <title>Setup – QuickBite</title>
  <style>
    body { font-family: monospace; padding: 2rem; background: #f9f9f9; }
    h2 { color: #333; }
    .ok  { color: green; }
    .err { color: red; }
    table { border-collapse: collapse; margin-top: 1rem; }
    td, th { border: 1px solid #ccc; padding: .4rem .9rem; }
    th { background: #eee; }
  </style>
</head>
<body>
<h2>QuickBite – DB Setup</h2>
<h3>Migrácie</h3>
<ul>
<?php foreach ($results as $table => $msg): ?>
  <li><strong><?= $table ?></strong>: <?= $msg ?></li>
<?php endforeach; ?>
</ul>

<h3>Počty záznamov</h3>
<table>
  <tr><th>Tabuľka</th><th>Počet riadkov</th></tr>
<?php foreach ($stats as $t => $cnt): ?>
  <tr><td><?= $t ?></td><td><?= $cnt ?></td></tr>
<?php endforeach; ?>
</table>

<p style="margin-top:2rem;"><a href="dashboard.php">← Späť na dashboard</a></p>
</body>
</html>
