<?php

class KurierController
{
    public static function handle(): void
    {
        $model = new Kurier();

        if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
            $model->delete((int) $_GET['delete']);
            Helper::flash('success', 'Kuriér bol vymazaný.');
            Redirect::redirect('kurieri.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $meno       = trim($_POST['meno']       ?? '');
        $priezvisko = trim($_POST['priezvisko'] ?? '');
        $email      = trim($_POST['email']      ?? '');
        $telefon    = trim($_POST['telefon']    ?? '');
        $vozidlo    = $_POST['vozidlo'] ?? 'skuter';
        $stav       = $_POST['stav']    ?? 'offline';
        $id         = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $model->update($id, $meno, $priezvisko, $email, $telefon, $vozidlo, $stav);
            Helper::flash('success', 'Kuriér bol aktualizovaný.');
        } else {
            $model->create($meno, $priezvisko, $email, $telefon, $vozidlo, $stav);
            Helper::flash('success', 'Kuriér bol pridaný.');
        }

        Redirect::redirect('kurieri.php');
    }

    public static function getData(): array
    {
        $model        = new Kurier();
        $statusCounts = (new Objednavka())->getStatusCounts();

        return [
            'kurieri'          => $model->all(),
            'stats'            => $model->getStats(),
            'novychObjednavok' => $statusCounts['nova'] ?? 0,
        ];
    }
}
