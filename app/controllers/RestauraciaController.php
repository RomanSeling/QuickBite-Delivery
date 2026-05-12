<?php

class RestauraciaController
{
    public static function handle(): void
    {
        $model = new Restauracia();

        if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
            $model->delete((int) $_GET['delete']);
            Helper::flash('success', 'Reštaurácia bola vymazaná.');
            Redirect::redirect('restauracie.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $nazov          = trim($_POST['nazov']          ?? '');
        $kategoria      = trim($_POST['kategoria']      ?? '');
        $adresa         = trim($_POST['adresa']         ?? '');
        $telefon        = trim($_POST['telefon']        ?? '');
        $email          = trim($_POST['email']          ?? '');
        $popis          = trim($_POST['popis']          ?? '');
        $min_objednavka = (float)($_POST['min_objednavka'] ?? 5.00);
        $stav           = $_POST['stav'] ?? 'aktivna';
        $id             = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $model->update($id, $nazov, $kategoria, $adresa, $telefon, $email, $popis, $min_objednavka, $stav);
            Helper::flash('success', 'Reštaurácia bola aktualizovaná.');
        } else {
            $model->create($nazov, $kategoria, $adresa, $telefon, $email, $popis, $min_objednavka, $stav);
            Helper::flash('success', 'Reštaurácia bola pridaná.');
        }

        Redirect::redirect('restauracie.php');
    }

    public static function getData(): array
    {
        $model        = new Restauracia();
        $statusCounts = (new Objednavka())->getStatusCounts();

        return [
            'restauracie'      => $model->all(),
            'stats'            => $model->getStats(),
            'novychObjednavok' => $statusCounts['nova'] ?? 0,
        ];
    }
}
