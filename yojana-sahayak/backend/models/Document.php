<?php

class Document
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO scheme_documents
                (
                    scheme_id,
                    document_title,
                    source_url,
                    stored_file_path,
                    document_language,
                    document_type,
                    version_label,
                    file_sha256,
                    page_count
                )
                VALUES
                (
                    :scheme_id,
                    :document_title,
                    :source_url,
                    :stored_file_path,
                    :document_language,
                    :document_type,
                    :version_label,
                    :file_sha256,
                    :page_count
                )";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = "SELECT d.*, s.name_en, s.name_hi
                FROM scheme_documents d
                LEFT JOIN schemes s ON d.scheme_id = s.id
                ORDER BY d.created_at DESC";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM scheme_documents WHERE id = ?"
        );

        $stmt->execute([$id]);

        $document = $stmt->fetch();

        return $document ?: null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM scheme_documents WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }
}