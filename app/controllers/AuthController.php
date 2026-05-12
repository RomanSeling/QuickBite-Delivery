<?php

class AuthController
{
    public static function handleLogin(): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (Auth::login($email, $password, isset($_POST['remember']))) {
            if (Auth::isCustomer()) {
                $allowed  = ['cart.php', 'restaurants.php'];
                $redirect = trim($_POST['redirect'] ?? '');
                Redirect::redirect(in_array($redirect, $allowed, true) ? $redirect : 'restaurants.php');
            } else {
                Redirect::redirect('dashboard.php');
            }
        }

        return 'Nespravny e-mail alebo heslo.';
    }

    public static function handleRegister(): ?string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $meno            = trim($_POST['first_name'] ?? '');
        $priezvisko      = trim($_POST['last_name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $telefon         = trim($_POST['phone'] ?? '');
        $heslo           = trim($_POST['password'] ?? '');
        $hesloPotvrdenie = trim($_POST['password_confirm'] ?? '');
        $terms           = isset($_POST['terms']);

        if ($meno === '' || $priezvisko === '' || $email === '' || $heslo === '') {
            return 'Vyplnte prosim vsetky povinne polia.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Neplatny format e-mailu.';
        }
        if (mb_strlen($heslo) < 8) {
            return 'Heslo musi mat aspon 8 znakov.';
        }
        if ($heslo !== $hesloPotvrdenie) {
            return 'Hesla sa nezhoduju.';
        }
        if (!$terms) {
            return 'Pre registraciu je potrebne suhlasit s podmienkami.';
        }

        $zakaznik = new Zakaznik();
        if (!$zakaznik->create($meno, $priezvisko, $email, $telefon, '', $heslo)) {
            return 'Registracia zlyhala. E-mail uz moze byt pouzity.';
        }

        if (Auth::login($email, $heslo)) {
            Redirect::redirect('restaurants.php');
        }

        return 'Ucet bol vytvoreny, ale automaticke prihlasenie zlyhalo.';
    }
}

