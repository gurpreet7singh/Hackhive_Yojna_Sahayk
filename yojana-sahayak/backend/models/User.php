<?php

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }


    // Find user using email

    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ":email" => $email
        ]);

        return $stmt->fetch();
    }


    // Find user using ID

    public function findById($id)
    {
        $sql = "
            SELECT id, full_name, email, preferred_language, role, status
            FROM users
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch();
    }


    // Create new user

    public function create($name, $email, $passwordHash)
    {
        $sql = "
            INSERT INTO users
            (full_name, email, password_hash)
            VALUES
            (:name, :email, :password_hash)
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":password_hash" => $passwordHash
        ]);

        return $this->findById($this->pdo->lastInsertId());
    }
}

?>