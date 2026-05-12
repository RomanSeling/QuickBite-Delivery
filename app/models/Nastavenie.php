<?php

class Nastavenie
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function all(): array
    {
        try {
            $rows   = $this->db->query("SELECT kluc, hodnota FROM nastavenia")->fetchAll();
            $result = [];
            foreach ($rows as $r) {
                $result[$r->kluc] = $r->hodnota;
            }
            return $result;
        } catch (PDOException $e) {
            Helper::log("Nastavenie::all ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function get(string $kluc, string $default = ''): string
    {
        try {
            $stmt = $this->db->prepare("SELECT hodnota FROM nastavenia WHERE kluc = :kluc");
            $stmt->execute(['kluc' => $kluc]);
            $row = $stmt->fetch();
            return $row ? (string) $row->hodnota : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }

    public function set(string $kluc, string $hodnota): bool
    {
        try {
            $sql = "INSERT INTO nastavenia (kluc, hodnota)
                    VALUES (:kluc, :h)
                    ON DUPLICATE KEY UPDATE hodnota = :h2";
            return $this->db->prepare($sql)->execute([
                'kluc' => $kluc,
                'h'    => $hodnota,
                'h2'   => $hodnota,
            ]);
        } catch (PDOException $e) {
            Helper::log("Nastavenie::set ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function setMultiple(array $data): bool
    {
        try {
            $this->db->beginTransaction();
            foreach ($data as $kluc => $hodnota) {
                $this->set($kluc, (string) $hodnota);
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Helper::log("Nastavenie::setMultiple ERROR: " . $e->getMessage());
            return false;
        }
    }
}
