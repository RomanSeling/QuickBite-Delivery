<?php

class ZakaznikController
{
    public static function handle(): void
    {
        $model = new Zakaznik();

        if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
            $model->delete((int) $_GET['delete']);
            Helper::flash('success', 'Zakaznik bol vymazany.');
            Redirect::redirect('zakaznici.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $meno       = trim($_POST['meno'] ?? '');
        $priezvisko = trim($_POST['priezvisko'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $telefon    = trim($_POST['telefon'] ?? '');
        $adresa     = trim($_POST['adresa'] ?? '');
        $heslo      = trim($_POST['heslo'] ?? '');
        $id         = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $ok = $model->update($id, $meno, $priezvisko, $email, $telefon, $adresa, $heslo !== '' ? $heslo : null);
            Helper::flash($ok ? 'success' : 'error', $ok ? 'Zakaznik bol aktualizovany.' : 'Aktualizacia zlyhala. Skontrolujte e-mail.');
        } else {
            if (mb_strlen($heslo) < 8) {
                Helper::flash('error', 'Heslo pre noveho zakaznika musi mat aspon 8 znakov.');
                Redirect::redirect('zakaznici.php');
            }

            $ok = $model->create($meno, $priezvisko, $email, $telefon, $adresa, $heslo);
            Helper::flash($ok ? 'success' : 'error', $ok ? 'Zakaznik bol pridany.' : 'Vytvorenie zlyhalo. E-mail uz moze byt pouzity.');
        }

        Redirect::redirect('zakaznici.php');
    }

    public static function getData(): array
    {
        $model        = new Zakaznik();
        $statusCounts = (new Objednavka())->getStatusCounts();
        $search       = trim($_GET['search'] ?? '');

        $perPage = Helper::ITEMS_PER_PAGE;
        $total   = $model->count($search);
        $pages   = max(1, (int)ceil($total / $perPage));
        $page    = max(1, min($pages, (int)($_GET['page'] ?? 1)));
        $offset  = ($page - 1) * $perPage;

        $urlParams = array_filter(['search' => $search]);

        return [
            'zakaznici'        => $model->paginate($perPage, $offset, $search),
            'stats'            => $model->getStats(),
            'novychObjednavok' => $statusCounts['nova'] ?? 0,
            'pgSearch'    => $search,
            // stránkovanie
            'pgTotal'     => $total,
            'pgPage'      => $page,
            'pgPerPage'   => $perPage,
            'pgPages'     => $pages,
            'pgNav'       => Helper::paginator($page, $total, $perPage, $urlParams),
        ];
    }
}
