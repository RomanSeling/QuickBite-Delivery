<?php

class Zakaznik
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function all(): array
    {
        try {
            $sql = "SELECT z.*,
                        COUNT(o.id) AS pocet_objednavok,
                        COALESCE(SUM(CASE WHEN o.stav='dorucena' THEN o.suma END), 0) AS utratene
                    FROM zakaznici z
                    LEFT JOIN objednavky o ON o.zakaznik_id = z.id
                    GROUP BY z.id
                    ORDER BY z.created_at DESC";
            return $this->db->query($sql)->fetchAll();
        } catch (PDOException $e) {
            Helper::log("Zakaznik::all ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vráti celkový počet zákazníkov (voliteľne filtrovaný fulltext vyhľadávaním).
     */
    public function count(string $search = ''): int
    {
        try {
            if ($search !== '') {
                $stmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM zakaznici z
                     WHERE CONCAT(z.meno,' ',z.priezvisko) LIKE :s OR z.email LIKE :s"
                );
                $stmt->execute(['s' => '%' . $search . '%']);
            } else {
                $stmt = $this->db->query("SELECT COUNT(*) FROM zakaznici");
            }
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            Helper::log("Zakaznik::count ERROR: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Vráti stránkovaný zoznam zákazníkov (LIMIT + OFFSET).
     */
    public function paginate(int $limit, int $offset, string $search = ''): array
    {
        try {
            $sql    = "SELECT z.*,
                           COUNT(o.id) AS pocet_objednavok,
                           COALESCE(SUM(CASE WHEN o.stav='dorucena' THEN o.suma END), 0) AS utratene
                       FROM zakaznici z
                       LEFT JOIN objednavky o ON o.zakaznik_id = z.id
                       WHERE 1=1";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (CONCAT(z.meno,' ',z.priezvisko) LIKE :s OR z.email LIKE :s)";
                $params['s'] = '%' . $search . '%';
            }

            $sql .= " GROUP BY z.id ORDER BY z.created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue(":$k", $v);
            }
            $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            Helper::log("Zakaznik::paginate ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): object|false
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM zakaznici WHERE id = :id");
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
        string $adresa,
        string $heslo
    ): bool {
        try {
            $this->db->beginTransaction();

            $userSql = "INSERT INTO users (meno, email, heslo, rola) VALUES (:meno, :email, :heslo, 'zakaznik')";
            $this->db->prepare($userSql)->execute([
                'meno'  => trim($meno . ' ' . $priezvisko),
                'email' => $email,
                'heslo' => password_hash($heslo, PASSWORD_DEFAULT),
            ]);

            $userId = (int)$this->db->lastInsertId();

            $sql = "INSERT INTO zakaznici (user_id, meno, priezvisko, email, telefon, adresa)
                    VALUES (:user_id, :meno, :priezvisko, :email, :telefon, :adresa)";
            $this->db->prepare($sql)->execute([
                'user_id'     => $userId,
                'meno'       => $meno,
                'priezvisko' => $priezvisko,
                'email'      => $email,
                'telefon'    => $telefon,
                'adresa'     => $adresa,
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Helper::log("Zakaznik::create ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function update(
        int    $id,
        string $meno,
        string $priezvisko,
        string $email,
        string $telefon,
        string $adresa,
        ?string $heslo = null
    ): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT user_id FROM zakaznici WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $zakaznik = $stmt->fetch();

            if (!$zakaznik) {
                $this->db->rollBack();
                return false;
            }

            $userParams = [
                'id' => (int)$zakaznik->user_id,
                'meno' => trim($meno . ' ' . $priezvisko),
                'email' => $email,
            ];

            if (!empty($heslo)) {
                $userParams['heslo'] = password_hash($heslo, PASSWORD_DEFAULT);
                $userSql = "UPDATE users SET meno=:meno, email=:email, heslo=:heslo WHERE id=:id";
            } else {
                $userSql = "UPDATE users SET meno=:meno, email=:email WHERE id=:id";
            }
            $this->db->prepare($userSql)->execute($userParams);

            $sql = "UPDATE zakaznici
                    SET meno=:meno, priezvisko=:priezvisko, email=:email, telefon=:telefon, adresa=:adresa
                    WHERE id=:id";
            $this->db->prepare($sql)->execute([
                'id'         => $id,
                'meno'       => $meno,
                'priezvisko' => $priezvisko,
                'email'      => $email,
                'telefon'    => $telefon,
                'adresa'     => $adresa,
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            Helper::log("Zakaznik::update ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM zakaznici WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $zakaznik = $stmt->fetch();

            if (!$zakaznik) {
                return false;
            }

            return $this->db->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => (int)$zakaznik->user_id]);
        } catch (PDOException $e) {
            Helper::log("Zakaznik::delete ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function getStats(): object
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS celkove,
                        (SELECT COUNT(DISTINCT zakaznik_id) FROM objednavky
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS aktivni,
                        COALESCE(ROUND(AVG(o.suma), 2), 0) AS avg_hodnota
                    FROM zakaznici z
                    LEFT JOIN objednavky o ON o.zakaznik_id = z.id AND o.stav = 'dorucena'";
            return $this->db->query($sql)->fetch();
        } catch (PDOException $e) {
            Helper::log("Zakaznik::getStats ERROR: " . $e->getMessage());
            return (object)['celkove' => 0, 'aktivni' => 0, 'avg_hodnota' => 0];
        }
    }

    public function allForSelect(): array
    {
        try {
            return $this->db->query(
                "SELECT id, CONCAT(meno, ' ', priezvisko) AS meno_cele FROM zakaznici ORDER BY meno"
            )->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
