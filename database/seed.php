<?php
/**
 * QuickBite setup script
 * Spustite raz: http://localhost/projekty/zaverecna_praca/database/seed.php
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'quickbite';

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/quickbite.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }

    $pdo->exec("USE {$dbname}");

    $adminEmail = 'admin@quickbite.sk';
    $adminMeno  = 'Jan Novak';
    $adminHeslo = password_hash('admin123', PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$adminEmail]);

    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO users (meno, email, heslo, rola) VALUES (?, ?, ?, 'admin')")
            ->execute([$adminMeno, $adminEmail, $adminHeslo]);
    } else {
        $pdo->prepare("UPDATE users SET meno = ?, heslo = ?, rola = 'admin' WHERE email = ?")
            ->execute([$adminMeno, $adminHeslo, $adminEmail]);
    }

    $demoCustomerHash = password_hash('test1234', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET heslo = ? WHERE rola = 'zakaznik'")->execute([$demoCustomerHash]);

    echo "<p>Admin login: <strong>{$adminEmail}</strong> / <strong>admin123</strong></p>";
    echo "<p>Demo zakaznici login: ich e-mail / <strong>test1234</strong></p>";
    echo "<p>Databaza <strong>{$dbname}</strong> a data su pripravene.</p>";
    echo "<p><a href='../public/templates/login.php'>Prejst na prihlasenie</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Chyba: " . htmlspecialchars($e->getMessage()) . "</p>";
}
