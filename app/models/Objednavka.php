<?php

class Objednavka
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function all(string $stav = '', string $search = ''): array
    {
        try {
            $sql = "SELECT o.*,
                        CONCAT(z.meno, ' ', z.priezvisko) AS zakaznik_meno,
                        z.email AS zakaznik_email,
                        r.nazov AS restauracia_nazov,
                        CONCAT(k.meno, ' ', k.priezvisko) AS kurier_meno
                    FROM objednavky o
                    LEFT JOIN zakaznici    z ON z.id = o.zakaznik_id
                    LEFT JOIN restauracie  r ON r.id = o.restauracia_id
                    LEFT JOIN kurieri      k ON k.id = o.kurier_id
                    WHERE 1=1";
            $params = [];

            if ($stav !== '') {
                $sql .= " AND o.stav = :stav";
                $params['stav'] = $stav;
            }
            if ($search !== '') {
                $sql .= " AND (CONCAT(z.meno,' ',z.priezvisko) LIKE :s
                              OR r.nazov LIKE :s
                              OR o.polozky LIKE :s)";
                $params['s'] = '%' . $search . '%';
            }
            $sql .= " ORDER BY o.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            Helper::log("Objednavka::all ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vráti celkový počet objednávok zodpovedajúcich filtrom.
     * Rovnaká WHERE logika ako all() — musí byť synchrónna.
     */
    public function count(string $stav = '', string $search = ''): int
    {
        try {
            $sql    = "SELECT COUNT(*)
                       FROM objednavky o
                       LEFT JOIN zakaznici   z ON z.id = o.zakaznik_id
                       LEFT JOIN restauracie r ON r.id = o.restauracia_id
                       WHERE 1=1";
            $params = [];

            if ($stav !== '') {
                $sql .= " AND o.stav = :stav";
                $params['stav'] = $stav;
            }
            if ($search !== '') {
                $sql .= " AND (CONCAT(z.meno,' ',z.priezvisko) LIKE :s
                               OR r.nazov LIKE :s
                               OR o.polozky LIKE :s)";
                $params['s'] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {
            Helper::log("Objednavka::count ERROR: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Vráti stránkovaný zoznam objednávok (LIMIT + OFFSET).
     */
    public function paginate(int $limit, int $offset, string $stav = '', string $search = ''): array
    {
        try {
            $sql    = "SELECT o.*,
                           CONCAT(z.meno, ' ', z.priezvisko) AS zakaznik_meno,
                           z.email AS zakaznik_email,
                           r.nazov AS restauracia_nazov,
                           CONCAT(k.meno, ' ', k.priezvisko) AS kurier_meno
                       FROM objednavky o
                       LEFT JOIN zakaznici    z ON z.id = o.zakaznik_id
                       LEFT JOIN restauracie  r ON r.id = o.restauracia_id
                       LEFT JOIN kurieri      k ON k.id = o.kurier_id
                       WHERE 1=1";
            $params = [];

            if ($stav !== '') {
                $sql .= " AND o.stav = :stav";
                $params['stav'] = $stav;
            }
            if ($search !== '') {
                $sql .= " AND (CONCAT(z.meno,' ',z.priezvisko) LIKE :s
                               OR r.nazov LIKE :s
                               OR o.polozky LIKE :s)";
                $params['s'] = '%' . $search . '%';
            }

            $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            // LIMIT a OFFSET musia byť bindované ako INT — inak PDO obalí hodnotu do apostrofov
            foreach ($params as $k => $v) {
                $stmt->bindValue(":$k", $v);
            }
            $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            Helper::log("Objednavka::paginate ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): object|false
    {
        try {
            $sql  = "SELECT o.*,
                        CONCAT(z.meno, ' ', z.priezvisko) AS zakaznik_meno,
                        r.nazov AS restauracia_nazov,
                        CONCAT(k.meno, ' ', k.priezvisko) AS kurier_meno
                    FROM objednavky o
                    LEFT JOIN zakaznici    z ON z.id = o.zakaznik_id
                    LEFT JOIN restauracie  r ON r.id = o.restauracia_id
                    LEFT JOIN kurieri      k ON k.id = o.kurier_id
                    WHERE o.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            Helper::log("Objednavka::find ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function create(
        int    $zakaznik_id,
        int    $restauracia_id,
        ?int   $kurier_id,
        string $polozky,
        float  $suma,
        string $platba,
        string $stav,
        string $adresa,
        string $poznamka
    ): bool {
        try {
            $sql  = "INSERT INTO objednavky
                        (zakaznik_id, restauracia_id, kurier_id, polozky, suma, platba, stav, adresa_dorucenia, poznamka)
                     VALUES
                        (:zakaznik_id, :restauracia_id, :kurier_id, :polozky, :suma, :platba, :stav, :adresa, :poznamka)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'zakaznik_id'    => $zakaznik_id,
                'restauracia_id' => $restauracia_id,
                'kurier_id'      => $kurier_id,
                'polozky'        => $polozky,
                'suma'           => $suma,
                'platba'         => $platba,
                'stav'           => $stav,
                'adresa'         => $adresa,
                'poznamka'       => $poznamka,
            ]);
        } catch (PDOException $e) {
            Helper::log("Objednavka::create ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function createAndReturnId(
        int    $zakaznik_id,
        int    $restauracia_id,
        ?int   $kurier_id,
        string $polozky,
        float  $suma,
        string $platba,
        string $stav,
        string $adresa,
        string $poznamka
    ): int|false {
        try {
            $sql  = "INSERT INTO objednavky
                        (zakaznik_id, restauracia_id, kurier_id, polozky, suma, platba, stav, adresa_dorucenia, poznamka)
                     VALUES
                        (:zakaznik_id, :restauracia_id, :kurier_id, :polozky, :suma, :platba, :stav, :adresa, :poznamka)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'zakaznik_id'    => $zakaznik_id,
                'restauracia_id' => $restauracia_id,
                'kurier_id'      => $kurier_id,
                'polozky'        => $polozky,
                'suma'           => $suma,
                'platba'         => $platba,
                'stav'           => $stav,
                'adresa'         => $adresa,
                'poznamka'       => $poznamka,
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            Helper::log("Objednavka::createAndReturnId ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function createItems(int $objednavka_id, array $items): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO polozky_objednavky (objednavka_id, produkt_id, nazov, mnozstvo, cena)
                 VALUES (:objednavka_id, :produkt_id, :nazov, :mnozstvo, :cena)"
            );

            foreach ($items as $item) {
                $stmt->execute([
                    'objednavka_id' => $objednavka_id,
                    'produkt_id'    => (int)($item['id'] ?? 0) ?: null,
                    'nazov'         => $item['name'] ?? 'Polozka',
                    'mnozstvo'      => max(1, (int)($item['qty'] ?? 1)),
                    'cena'          => (float)($item['price'] ?? 0),
                ]);
            }

            return true;
        } catch (PDOException $e) {
            Helper::log("Objednavka::createItems ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function update(
        int    $id,
        int    $zakaznik_id,
        int    $restauracia_id,
        ?int   $kurier_id,
        string $polozky,
        float  $suma,
        string $platba,
        string $stav,
        string $adresa,
        string $poznamka
    ): bool {
        try {
            $sql  = "UPDATE objednavky
                     SET zakaznik_id=:zakaznik_id, restauracia_id=:restauracia_id, kurier_id=:kurier_id,
                         polozky=:polozky, suma=:suma, platba=:platba, stav=:stav,
                         adresa_dorucenia=:adresa, poznamka=:poznamka
                     WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id'             => $id,
                'zakaznik_id'    => $zakaznik_id,
                'restauracia_id' => $restauracia_id,
                'kurier_id'      => $kurier_id,
                'polozky'        => $polozky,
                'suma'           => $suma,
                'platba'         => $platba,
                'stav'           => $stav,
                'adresa'         => $adresa,
                'poznamka'       => $poznamka,
            ]);
        } catch (PDOException $e) {
            Helper::log("Objednavka::update ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            return $this->db->prepare("DELETE FROM objednavky WHERE id = :id")->execute(['id' => $id]);
        } catch (PDOException $e) {
            Helper::log("Objednavka::delete ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function getStatusCounts(): array
    {
        try {
            $rows   = $this->db->query("SELECT stav, COUNT(*) AS pocet FROM objednavky GROUP BY stav")->fetchAll();
            $counts = [];
            foreach ($rows as $r) {
                $counts[$r->stav] = (int) $r->pocet;
            }
            return $counts;
        } catch (PDOException $e) {
            Helper::log("Objednavka::getStatusCounts ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function getDashboardStats(): object
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS celkove,
                        SUM(CASE WHEN stav IN ('nova','pripravuje','ceste') THEN 1 ELSE 0 END) AS aktivne,
                        COALESCE(SUM(CASE WHEN stav='dorucena' AND DATE(created_at)=CURDATE() THEN suma END), 0) AS trzby_dnes,
                        SUM(CASE WHEN stav='nova' THEN 1 ELSE 0 END) AS nove
                    FROM objednavky";
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            Helper::log("Objednavka::getDashboardStats ERROR: " . $e->getMessage());
            return (object)['celkove' => 0, 'aktivne' => 0, 'trzby_dnes' => 0, 'nove' => 0];
        }
    }

    public function getTopRestauracie(int $limit = 4): array
    {
        try {
            $sql  = "SELECT r.nazov, COUNT(o.id) AS pocet_dnes
                     FROM objednavky o
                     JOIN restauracie r ON r.id = o.restauracia_id
                     WHERE DATE(o.created_at) = CURDATE()
                     GROUP BY r.id
                     ORDER BY pocet_dnes DESC
                     LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Objednavka::getTopRestauracie ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function getStatsByMonth(): array
    {
        try {
            $sql = "SELECT
                        DATE_FORMAT(created_at, '%Y-%m') AS mesiac,
                        COUNT(*) AS pocet,
                        COALESCE(SUM(CASE WHEN stav='dorucena' THEN suma END), 0) AS trzby
                    FROM objednavky
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY mesiac
                    ORDER BY mesiac ASC";
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Objednavka::getStatsByMonth ERROR: " . $e->getMessage());
            return [];
        }
    }
}
