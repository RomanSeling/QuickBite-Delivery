<?php

class Auth
{
    public static function login(string $email, string $password, bool $remember = false): bool
    {
        try {
            $db   = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user->heslo)) {
                return false;
            }

            session_regenerate_id(true);

            $_SESSION['user_id']   = $user->id;
            $_SESSION['user_rola'] = $user->rola;
            $_SESSION['user_meno'] = $user->meno;

            if ($remember) {
                self::setRememberCookie($db, $user->id);
            }

            return true;

        } catch (PDOException $e) {
            Helper::log('Auth::login ERROR: ' . $e->getMessage());
            return false;
        }
    }

    private static function setRememberCookie(PDO $db, int $userId): void
    {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        $db->prepare("UPDATE users SET remember_token=:t, remember_expires=:e WHERE id=:id")
           ->execute(['t' => hash('sha256', $token), 'e' => $expires, 'id' => $userId]);

        setcookie('qb_remember', $token, [
            'expires'  => strtotime('+30 days'),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function loginWithCookie(): bool
    {
        $token = $_COOKIE['qb_remember'] ?? '';
        if (!$token) return false;

        try {
            $db   = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE remember_token=:t AND remember_expires > NOW() LIMIT 1");
            $stmt->execute(['t' => hash('sha256', $token)]);
            $user = $stmt->fetch();

            if (!$user) {
                setcookie('qb_remember', '', time() - 3600, '/');
                return false;
            }

            session_regenerate_id(true);
            $_SESSION['user_id']   = $user->id;
            $_SESSION['user_rola'] = $user->rola;
            $_SESSION['user_meno'] = $user->meno;

            self::setRememberCookie($db, $user->id);
            return true;

        } catch (PDOException $e) {
            Helper::log('Auth::loginWithCookie ERROR: ' . $e->getMessage());
            return false;
        }
    }

    public static function logout(): void
    {
        if (isset($_COOKIE['qb_remember'])) {
            try {
                $uid = self::id();
                if ($uid) {
                    (new Database())->getConnection()
                        ->prepare("UPDATE users SET remember_token=NULL, remember_expires=NULL WHERE id=:id")
                        ->execute(['id' => $uid]);
                }
            } catch (\Exception $e) {}
            setcookie('qb_remember', '', time() - 3600, '/');
        }

        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Redirect::redirect('login.php');
        }
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function meno(): string
    {
        return $_SESSION['user_meno'] ?? 'Admin';
    }

    public static function initials(): string
    {
        $parts = explode(' ', self::meno());
        $init  = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $init .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $init ?: 'A';
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_rola'] ?? '') === 'admin';
    }

    public static function role(): string
    {
        return $_SESSION['user_rola'] ?? '';
    }

    public static function isCustomer(): bool
    {
        return self::role() === 'zakaznik';
    }
}
