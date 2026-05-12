<?php

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    public function find(int $id): object|false
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create(string $meno, string $email, string $heslo, string $rola = 'admin'): bool
    {
        $sql  = "INSERT INTO users (meno, email, heslo, rola) VALUES (:meno, :email, :heslo, :rola)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'meno'  => $meno,
            'email' => $email,
            'heslo' => password_hash($heslo, PASSWORD_DEFAULT),
            'rola'  => $rola,
        ]);
    }

    public function update(int $id, string $meno, string $email, ?string $heslo = null): bool
    {
        if (!empty($heslo)) {
            $stmt = $this->db->prepare(
                "UPDATE users SET meno=:meno, email=:email, heslo=:heslo WHERE id=:id"
            );
            return $stmt->execute([
                'id'    => $id,
                'meno'  => $meno,
                'email' => $email,
                'heslo' => password_hash($heslo, PASSWORD_DEFAULT),
            ]);
        }

        $stmt = $this->db->prepare(
            "UPDATE users SET meno=:meno, email=:email WHERE id=:id"
        );
        return $stmt->execute(['id' => $id, 'meno' => $meno, 'email' => $email]);
    }
}
