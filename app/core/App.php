<?php

class App
{
    private static bool $migrationsRun = false;

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Core
        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/Helper.php';
        require_once __DIR__ . '/Redirect.php';
        require_once __DIR__ . '/Auth.php';

        // Models
        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../models/Objednavka.php';
        require_once __DIR__ . '/../models/Restauracia.php';
        require_once __DIR__ . '/../models/Zakaznik.php';
        require_once __DIR__ . '/../models/Kurier.php';
        require_once __DIR__ . '/../models/Nastavenie.php';
        require_once __DIR__ . '/../models/Produkt.php';

        // Auto-prihlásenie cez remember-me cookie (ak session expirovala)
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['qb_remember'])) {
            Auth::loginWithCookie();
        }

        // Auto-migrácia: spustí sa raz za PHP request (statický flag, nie session)
        if (!self::$migrationsRun) {
            self::$migrationsRun = true;
            self::runMigrations();
        }
    }

    private static function runMigrations(): void
    {
        try {
            $db = (new Database())->getConnection();

            // polozky_objednavky – bez FK na produkty (produkty môžu byť vymazané)
            $db->exec("CREATE TABLE IF NOT EXISTS polozky_objednavky (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                objednavka_id INT          NOT NULL,
                produkt_id    INT          DEFAULT NULL,
                nazov         VARCHAR(200) NOT NULL,
                mnozstvo      INT          NOT NULL DEFAULT 1,
                cena          DECIMAL(8,2) NOT NULL,
                FOREIGN KEY (objednavka_id) REFERENCES objednavky(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS reviews (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT       NOT NULL,
                customer_id   INT       NOT NULL,
                stars         TINYINT   NOT NULL,
                comment       TEXT,
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_customer_restaurant (customer_id, restaurant_id),
                FOREIGN KEY (restaurant_id) REFERENCES restauracie(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id)   REFERENCES zakaznici(id)   ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        } catch (PDOException $e) {
            Helper::log('App::runMigrations ERROR: ' . $e->getMessage());
        }
    }
}
