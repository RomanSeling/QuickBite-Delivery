<?php

class App
{
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
    }
}
