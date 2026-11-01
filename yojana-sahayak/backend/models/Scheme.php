<?php

class Scheme
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }


    // Get all active schemes

    public function getAll()
    {
        $sql = "
            SELECT *
            FROM schemes
            WHERE is_active = 1
            ORDER BY created_at DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }


    // Get one scheme

    public function getById($id)
    {
        $sql = "
            SELECT *
            FROM schemes
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch();
    }


    // Create scheme

    public function create($data)
    {
        $sql = "
            INSERT INTO schemes (
                scheme_code,
                name_en,
                name_hi,
                description_en,
                description_hi,
                benefits_en,
                benefits_hi,
                department,
                scheme_level,
                state_code,
                category,
                official_url,
                application_url
            )
            VALUES (
                :scheme_code,
                :name_en,
                :name_hi,
                :description_en,
                :description_hi,
                :benefits_en,
                :benefits_hi,
                :department,
                :scheme_level,
                :state_code,
                :category,
                :official_url,
                :application_url
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($data);

        return $this->getById($this->pdo->lastInsertId());
    }


    // Update scheme

    public function update($id, $data)
    {
        $sql = "
            UPDATE schemes SET

                scheme_code = :scheme_code,
                name_en = :name_en,
                name_hi = :name_hi,
                description_en = :description_en,
                description_hi = :description_hi,
                benefits_en = :benefits_en,
                benefits_hi = :benefits_hi,
                department = :department,
                scheme_level = :scheme_level,
                state_code = :state_code,
                category = :category,
                official_url = :official_url,
                application_url = :application_url

            WHERE id = :id
        ";

        $data["id"] = $id;

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($data);

        return $this->getById($id);
    }


    // Delete scheme

    public function delete($id)
    {
        $sql = "DELETE FROM schemes WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->rowCount() > 0;
    }
}

?>