<?php

class ObjednavkaController
{
    public static function handle(): void
    {
        $model = new Objednavka();

        // DELETE cez GET parameter
        if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
            $model->delete((int) $_GET['delete']);
            Helper::flash('success', 'Objednávka bola vymazaná.');
            Redirect::redirect('objednavky.php');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $zakaznik_id    = (int)  ($_POST['zakaznik_id']    ?? 0);
        $restauracia_id = (int)  ($_POST['restauracia_id'] ?? 0);
        $kurier_id      = !empty($_POST['kurier_id']) ? (int) $_POST['kurier_id'] : null;
        $polozky        = trim   ($_POST['polozky']        ?? '');
        $suma           = (float)($_POST['suma']           ?? 0);
        $platba         = $_POST['platba'] ?? 'karta';
        $stav           = $_POST['stav']   ?? 'nova';
        $adresa         = trim($_POST['adresa_dorucenia'] ?? '');
        $poznamka       = trim($_POST['poznamka']         ?? '');
        $id             = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $model->update($id, $zakaznik_id, $restauracia_id, $kurier_id, $polozky, $suma, $platba, $stav, $adresa, $poznamka);
            Helper::flash('success', 'Objednávka bola aktualizovaná.');
        } else {
            $model->create($zakaznik_id, $restauracia_id, $kurier_id, $polozky, $suma, $platba, $stav, $adresa, $poznamka);
            Helper::flash('success', 'Nová objednávka bola pridaná.');
        }

        Redirect::redirect('objednavky.php');
    }

    public static function getData(): array
    {
        $model        = new Objednavka();
        $stav         = $_GET['stav']   ?? '';
        $search       = $_GET['search'] ?? '';
        $statusCounts = $model->getStatusCounts();

        $perPage = Helper::ITEMS_PER_PAGE;
        $total   = $model->count($stav, $search);
        $pages   = max(1, (int)ceil($total / $perPage));
        $page    = max(1, min($pages, (int)($_GET['page'] ?? 1)));
        $offset  = ($page - 1) * $perPage;

        // GET parametre, ktoré sa zachovajú v URL pri kliknutí na stránky
        $urlParams = array_filter(['stav' => $stav, 'search' => $search]);

        return [
            'objednavky'       => $model->paginate($perPage, $offset, $stav, $search),
            'statusCounts'     => $statusCounts,
            'novychObjednavok' => $statusCounts['nova'] ?? 0,
            'zakaznici'        => (new Zakaznik())->allForSelect(),
            'restauracie'      => (new Restauracia())->allForSelect(),
            'kurieri'          => (new Kurier())->allForSelect(),
            'aktualnyStav'     => $stav,
            // stránkovanie
            'pgTotal'     => $total,
            'pgPage'      => $page,
            'pgPerPage'   => $perPage,
            'pgPages'     => $pages,
            'pgNav'       => Helper::paginator($page, $total, $perPage, $urlParams),
        ];
    }
}
