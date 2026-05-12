<?php

class NastaveniaController
{
    public static function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Systémové nastavenia
        $nastavenie = new Nastavenie();
        $nastavenie->setMultiple([
            'nazov_platformy'      => trim($_POST['nazov_platformy']      ?? ''),
            'kontaktny_email'      => trim($_POST['kontaktny_email']      ?? ''),
            'telefon_podpory'      => trim($_POST['telefon_podpory']      ?? ''),
            'adresa_prevadzky'     => trim($_POST['adresa_prevadzky']     ?? ''),
            'poplatok_dorucenie'   => trim($_POST['poplatok_dorucenie']   ?? ''),
            'min_dorucenie_zadarmo'=> trim($_POST['min_dorucenie_zadarmo']?? ''),
            'max_polomer'          => trim($_POST['max_polomer']          ?? ''),
            'cas_dorucenia'        => trim($_POST['cas_dorucenia']        ?? ''),
        ]);

        // Profil admina
        $userId = Auth::id();
        if ($userId) {
            $meno       = trim($_POST['prof_meno']       ?? '');
            $priezvisko = trim($_POST['prof_priezvisko'] ?? '');
            $menoFull   = trim($meno . ' ' . $priezvisko);
            $email      = trim($_POST['prof_email']      ?? '');
            $heslo      = $_POST['prof_heslo']           ?? '';
            (new User())->update($userId, $menoFull, $email, !empty($heslo) ? $heslo : null);

            // Aktualizácia session mena
            if ($menoFull) {
                $_SESSION['user_meno'] = $menoFull;
            }
        }

        Helper::flash('success', 'Nastavenia boli uložené.');
        Redirect::redirect('nastavenia.php');
    }

    public static function getData(): array
    {
        $statusCounts = (new Objednavka())->getStatusCounts();

        return [
            'settings'         => (new Nastavenie())->all(),
            'currentUser'      => (new User())->find(Auth::id()),
            'novychObjednavok' => $statusCounts['nova'] ?? 0,
        ];
    }
}
