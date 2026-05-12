<?php

class Kurier
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function all(): array
    {
        try {
            $sql = "SELECT k.*,
                        COALESCE(COUNT(CASE WHEN o.stav='dorucena' AND DATE(o.created_at)=CURDATE() THEN 1 END), 0) AS doruceni_dnes,
                        (SELECT COUNT(*) FROM objednavky WHERE kurier_id=k.id AND stav='ceste') AS aktivna_objednavka
                    FROM kurieri k
                    LEFT JOIN objednavky o ON o.kurier_id = k.id
                    GROUP BY k.id
                    ORDER BY FIELD(k.stav,'online','zaneprazdneny','offline'), k.meno ASC";
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Kurier::all ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): object|false
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM kurieri WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function create(
        string $meno,
        string $priezvisko,
        string $email,
        string $telefon,
        string $vozidlo,
        string $stav
    ): bool {
        try {
            $sql = "INSERT INTO kurieri (meno, priezvisko, email, telefon, vozidlo, stav)
                    VALUES (:meno, :priezvisko, :email, :telefon, :vozidlo, :stav)";
            return $this->db->prepare($sql)->execute([
                'meno'       => $meno,
                'priezvisko' => $priezvisko,
                'email'      => $email,
                'telefon'    => $telefon,
                'vozidlo'    => $vozidlo,
                'stav'       => $stav,
            ]);
        } catch (PDOException $e) {
            Helper::log("Kurier::create ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function update(
        int    $id,
        string $meno,
        string $priezvisko,
        string $email,
        string $telefon,
        string $vozidlo,
        string $stav
    ): bool {
        try {
            $sql = "UPDATE kurieri
                    SET meno=:meno, priezvisko=:priezvisko, email=:email,
                        telefon=:telefon, vozidlo=:vozidlo, stav=:stav
                    WHERE id=:id";
            return $this->db->prepare($sql)->execute([
                'id'         => $id,
                'meno'       => $meno,
                'priezvisko' => $priezvisko,
                'email'      => $email,
                'telefon'    => $telefon,
                'vozidlo'    => $vozidlo,
                'stav'       => $stav,
            ]);
        } catch (PDOException $e) {
            Helper::log("Kurier::update ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            return $this->db->prepare("DELETE FROM kurieri WHERE id = :id")->execute(['id' => $id]);
        } catch (PDOException $e) {
            Helper::log("Kurier::delete ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function getStats(): object
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS celkove,
                        SUM(CASE WHEN stav='online' THEN 1 ELSE 0 END) AS online,
                        COALESCE(ROUND(AVG(hodnotenie), 1), 0) AS avg_hodnotenie,
                        (SELECT COUNT(*) FROM objednavky WHERE stav='ceste') AS aktivne_dorucenia,
                        (SELECT COUNT(*) FROM objednavky WHERE stav='dorucena' AND DATE(created_at)=CURDATE()) AS doruceni_dnes
                    FROM kurieri";
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            Helper::log("Kurier::getStats ERROR: " . $e->getMessage());
            return (object)['celkove'=>0,'online'=>0,'avg_hodnotenie'=>0,'aktivne_dorucenia'=>0,'doruceni_dnes'=>0];
        }
    }

    public function allForSelect(): array
    {
        try {
            return $this->db->query(
                "SELECT id, CONCAT(meno, ' ', priezvisko) AS meno_cele FROM kurieri ORDER BY meno"
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
