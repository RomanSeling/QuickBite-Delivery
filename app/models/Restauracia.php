<?php

class Restauracia
{
    private PDO $db;
    private ?bool $reviewsTableExists = null;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function all(): array
    {
        try {
            $ratingExpr = $this->reviewsTableExists()
                ? "COALESCE((SELECT ROUND(AVG(rv.stars), 1) FROM reviews rv WHERE rv.restaurant_id = r.id), r.hodnotenie)"
                : "r.hodnotenie";
            $countExpr  = $this->reviewsTableExists()
                ? "(SELECT COUNT(*) FROM reviews rv WHERE rv.restaurant_id = r.id)"
                : "0";

            $sql = "SELECT r.id, r.nazov, r.kategoria, r.adresa, r.telefon, r.email,
                           r.popis, r.min_objednavka, r.stav, r.created_at,
                           {$ratingExpr} AS hodnotenie,
                           {$countExpr} AS pocet_hodnoteni,
                           (SELECT COUNT(*) FROM objednavky o WHERE o.restauracia_id = r.id AND DATE(o.created_at) = CURDATE()) AS objednavky_dnes
                    FROM restauracie r
                    ORDER BY r.nazov ASC";
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Restauracia::all ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fulltextové vyhľadávanie reštaurácií cez SQL LIKE.
     * Prehľadáva stĺpce nazov, kategoria a popis.
     * Výsledky sú zoradené: aktívne prvé, potom podľa hodnotenia.
     */
    public function search(string $q = '', string $sort = 'rating'): array
    {
        try {
            $ratingExpr = $this->reviewsTableExists()
                ? "COALESCE((SELECT ROUND(AVG(rv.stars), 1) FROM reviews rv WHERE rv.restaurant_id = r.id), r.hodnotenie)"
                : "r.hodnotenie";
            $countExpr  = $this->reviewsTableExists()
                ? "(SELECT COUNT(*) FROM reviews rv WHERE rv.restaurant_id = r.id)"
                : "0";

            $sql    = "SELECT r.id, r.nazov, r.kategoria, r.adresa, r.telefon, r.email,
                              r.popis, r.min_objednavka, r.stav, r.created_at,
                              {$ratingExpr} AS hodnotenie,
                              {$countExpr} AS pocet_hodnoteni,
                              (SELECT COUNT(*) FROM objednavky o WHERE o.restauracia_id = r.id AND DATE(o.created_at) = CURDATE()) AS objednavky_dnes
                       FROM restauracie r
                       WHERE 1=1";
            $params = [];

            if ($q !== '') {
                $sql .= " AND (r.nazov LIKE :q OR r.kategoria LIKE :q OR r.popis LIKE :q)";
                $params['q'] = '%' . $q . '%';
            }

            $orderBy = match ($sort) {
                'min'  => "r.min_objednavka ASC, r.nazov ASC",
                'name' => "r.nazov ASC",
                default => ($this->reviewsTableExists()
                    ? "COALESCE((SELECT AVG(rv2.stars) FROM reviews rv2 WHERE rv2.restaurant_id = r.id), r.hodnotenie) DESC, r.nazov ASC"
                    : "r.hodnotenie DESC, r.nazov ASC"),
            };

            $sql .= " ORDER BY FIELD(r.stav,'aktivna','neaktivna'), {$orderBy}";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            Helper::log("Restauracia::search ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): object|false
    {
        try {
            $ratingExpr = $this->reviewsTableExists()
                ? "COALESCE((SELECT ROUND(AVG(rv.stars), 1) FROM reviews rv WHERE rv.restaurant_id = r.id), r.hodnotenie)"
                : "r.hodnotenie";
            $countExpr  = $this->reviewsTableExists()
                ? "(SELECT COUNT(*) FROM reviews rv WHERE rv.restaurant_id = r.id)"
                : "0";

            $sql  = "SELECT r.id, r.nazov, r.kategoria, r.adresa, r.telefon, r.email,
                            r.popis, r.min_objednavka, r.stav, r.created_at,
                            {$ratingExpr} AS hodnotenie,
                            {$countExpr} AS pocet_hodnoteni
                     FROM restauracie r
                     WHERE r.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            Helper::log("Restauracia::find ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function create(
        string $nazov,
        string $kategoria,
        string $adresa,
        string $telefon,
        string $email,
        string $popis,
        float  $min_objednavka,
        string $stav
    ): bool {
        try {
            $sql = "INSERT INTO restauracie (nazov, kategoria, adresa, telefon, email, popis, min_objednavka, stav)
                    VALUES (:nazov, :kategoria, :adresa, :telefon, :email, :popis, :min_objednavka, :stav)";
            return $this->db->prepare($sql)->execute([
                'nazov'          => $nazov,
                'kategoria'      => $kategoria,
                'adresa'         => $adresa,
                'telefon'        => $telefon,
                'email'          => $email,
                'popis'          => $popis,
                'min_objednavka' => $min_objednavka,
                'stav'           => $stav,
            ]);
        } catch (PDOException $e) {
            Helper::log("Restauracia::create ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function update(
        int    $id,
        string $nazov,
        string $kategoria,
        string $adresa,
        string $telefon,
        string $email,
        string $popis,
        float  $min_objednavka,
        string $stav
    ): bool {
        try {
            $sql = "UPDATE restauracie
                    SET nazov=:nazov, kategoria=:kategoria, adresa=:adresa, telefon=:telefon,
                        email=:email, popis=:popis, min_objednavka=:min_objednavka, stav=:stav
                    WHERE id=:id";
            return $this->db->prepare($sql)->execute([
                'id'             => $id,
                'nazov'          => $nazov,
                'kategoria'      => $kategoria,
                'adresa'         => $adresa,
                'telefon'        => $telefon,
                'email'          => $email,
                'popis'          => $popis,
                'min_objednavka' => $min_objednavka,
                'stav'           => $stav,
            ]);
        } catch (PDOException $e) {
            Helper::log("Restauracia::update ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            return $this->db->prepare("DELETE FROM restauracie WHERE id = :id")->execute(['id' => $id]);
        } catch (PDOException $e) {
            Helper::log("Restauracia::delete ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function getStats(): object
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS celkove,
                        SUM(CASE WHEN stav='aktivna' THEN 1 ELSE 0 END) AS aktivne,
                        COALESCE(ROUND(AVG(hodnotenie), 1), 0) AS avg_hodnotenie,
                        (SELECT COUNT(*) FROM objednavky WHERE DATE(created_at)=CURDATE()) AS objednavky_dnes
                    FROM restauracie";
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            Helper::log("Restauracia::getStats ERROR: " . $e->getMessage());
            return (object)['celkove' => 0, 'aktivne' => 0, 'avg_hodnotenie' => 0, 'objednavky_dnes' => 0];
        }
    }

    public function allForSelect(): array
    {
        try {
            return $this->db->query(
                "SELECT id, nazov FROM restauracie WHERE stav='aktivna' ORDER BY nazov"
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    private function reviewsTableExists(): bool
    {
        if ($this->reviewsTableExists !== null) {
            return $this->reviewsTableExists;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'reviews'");
            $this->reviewsTableExists = (bool)$stmt->fetchColumn();
        } catch (PDOException) {
            $this->reviewsTableExists = false;
        }

        return $this->reviewsTableExists;
    }
}
