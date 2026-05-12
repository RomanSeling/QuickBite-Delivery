<?php

class Produkt
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function byRestauracia(int $restauracia_id): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM produkty WHERE restauracia_id = :rid AND dostupny = 1 ORDER BY kategoria, id"
            );
            $stmt->execute(['rid' => $restauracia_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Produkt::byRestauracia ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function all(): array
    {
        try {
            $sql = "SELECT p.*, r.nazov AS restauracia_nazov
                    FROM produkty p
                    JOIN restauracie r ON r.id = p.restauracia_id
                    ORDER BY r.nazov, p.kategoria, p.nazov";
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Produkt::all ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function create(
        int    $restauracia_id,
        string $nazov,
        string $popis,
        float  $cena,
        string $kategoria,
        string $emoji,
        int    $dostupny
    ): bool {
        try {
            $sql = "INSERT INTO produkty (restauracia_id, nazov, popis, cena, kategoria, emoji, dostupny)
                    VALUES (:restauracia_id, :nazov, :popis, :cena, :kategoria, :emoji, :dostupny)";
            return $this->db->prepare($sql)->execute([
                'restauracia_id' => $restauracia_id,
                'nazov'          => $nazov,
                'popis'          => $popis,
                'cena'           => $cena,
                'kategoria'      => $kategoria,
                'emoji'          => $emoji,
                'dostupny'       => $dostupny,
            ]);
        } catch (PDOException $e) {
            Helper::log("Produkt::create ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function update(
        int    $id,
        int    $restauracia_id,
        string $nazov,
        string $popis,
        float  $cena,
        string $kategoria,
        string $emoji,
        int    $dostupny
    ): bool {
        try {
            $sql = "UPDATE produkty
                    SET restauracia_id=:restauracia_id, nazov=:nazov, popis=:popis,
                        cena=:cena, kategoria=:kategoria, emoji=:emoji, dostupny=:dostupny
                    WHERE id=:id";
            return $this->db->prepare($sql)->execute([
                'id'             => $id,
                'restauracia_id' => $restauracia_id,
                'nazov'          => $nazov,
                'popis'          => $popis,
                'cena'           => $cena,
                'kategoria'      => $kategoria,
                'emoji'          => $emoji,
                'dostupny'       => $dostupny,
            ]);
        } catch (PDOException $e) {
            Helper::log("Produkt::update ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            return $this->db->prepare("DELETE FROM produkty WHERE id = :id")->execute(['id' => $id]);
        } catch (PDOException $e) {
            Helper::log("Produkt::delete ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function getStats(): object
    {
        try {
            $sql = "SELECT
                        COUNT(*)                                            AS celkove,
                        SUM(CASE WHEN dostupny = 1 THEN 1 ELSE 0 END)     AS dostupne,
                        COUNT(DISTINCT restauracia_id)                     AS restauracii,
                        COUNT(DISTINCT kategoria)                          AS kategorii
                    FROM produkty";
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            Helper::log("Produkt::getStats ERROR: " . $e->getMessage());
            return (object)['celkove' => 0, 'dostupne' => 0, 'restauracii' => 0, 'kategorii' => 0];
        }
    }
}
