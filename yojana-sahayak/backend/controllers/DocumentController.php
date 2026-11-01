<?php

require_once __DIR__ . "/../models/Document.php";

class DocumentController
{
    private Document $document;

    private string $uploadDirectory =
        "C:/xampp/yojana-sahayak-private/documents/";

    public function __construct(PDO $pdo)
    {
        $this->document = new Document($pdo);
    }

    public function upload(): void
    {
        if (!isset($_FILES["document"])) {
            jsonResponse(false, "Please select a PDF file.", null, 400);
        }

        $file = $_FILES["document"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            jsonResponse(false, "File upload failed.", null, 400);
        }

        if ($file["size"] > 10 * 1024 * 1024) {
            jsonResponse(false, "Maximum file size is 10 MB.", null, 400);
        }

        $extension = strtolower(
            pathinfo($file["name"], PATHINFO_EXTENSION)
        );

        if ($extension !== "pdf") {
            jsonResponse(false, "Only PDF files are allowed.", null, 400);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);

        if ($mimeType !== "application/pdf") {
            jsonResponse(false, "Invalid PDF file.", null, 400);
        }

        $schemeId = filter_var(
            $_POST["scheme_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $title = trim($_POST["document_title"] ?? "");

        if (!$schemeId || $title === "") {
            jsonResponse(
                false,
                "Scheme ID and document title are required.",
                null,
                400
            );
        }

        $language = $_POST["document_language"] ?? "en";

        if (!in_array($language, ["en", "hi"], true)) {
            jsonResponse(false, "Invalid document language.", null, 400);
        }

        $sourceUrl = trim($_POST["source_url"] ?? "");
        $documentType = trim($_POST["document_type"] ?? "guideline");
        $version = trim($_POST["version_label"] ?? "v1");

        if (
            $sourceUrl !== "" &&
            !filter_var($sourceUrl, FILTER_VALIDATE_URL)
        ) {
            jsonResponse(false, "Invalid source URL.", null, 400);
        }

        if (!is_dir($this->uploadDirectory)) {
            if (!mkdir($this->uploadDirectory, 0750, true)) {
                jsonResponse(
                    false,
                    "Could not create document storage directory.",
                    null,
                    500
                );
            }
        }

        $hash = hash_file("sha256", $file["tmp_name"]);

        $filename = $hash . "_" . bin2hex(random_bytes(8)) . ".pdf";

        $destination = $this->uploadDirectory . $filename;

        if (!move_uploaded_file($file["tmp_name"], $destination)) {
            jsonResponse(false, "Could not save uploaded PDF.", null, 500);
        }

        try {
            $storedPath = $destination;

            $documentId = $this->document->create([
                "scheme_id" => $schemeId,
                "document_title" => $title,
                "source_url" => $sourceUrl ?: null,
                "stored_file_path" => $storedPath,
                "document_language" => $language,
                "document_type" => $documentType ?: null,
                "version_label" => $version ?: null,
                "file_sha256" => $hash,
                "page_count" => null
            ]);

            jsonResponse(
                true,
                "Document uploaded successfully.",
                [
                    "document_id" => $documentId,
                    "file_name" => $filename,
                    "sha256" => $hash
                ],
                201
            );

        } catch (PDOException $e) {

            if (file_exists($destination)) {
                unlink($destination);
            }

            error_log($e->getMessage());

            jsonResponse(
                false,
                "Could not save document information.",
                null,
                500
            );
        }
    }

    public function getAll(): void
    {
        jsonResponse(
            true,
            "Documents retrieved successfully.",
            ["documents" => $this->document->getAll()]
        );
    }

    public function getOne(int $id): void
    {
        $document = $this->document->getById($id);

        if (!$document) {
            jsonResponse(false, "Document not found.", null, 404);
        }

        // Never expose the private filesystem path through the API.
        unset($document["stored_file_path"]);

        jsonResponse(
            true,
            "Document retrieved successfully.",
            ["document" => $document]
        );
    }

    public function delete(int $id): void
    {
        $document = $this->document->getById($id);

        if (!$document) {
            jsonResponse(false, "Document not found.", null, 404);
        }

        $filePath = $document["stored_file_path"];

        if (!$this->document->delete($id)) {
            jsonResponse(false, "Could not delete document.", null, 500);
        }

        if ($filePath && is_file($filePath)) {
            unlink($filePath);
        }

        jsonResponse(true, "Document deleted successfully.");
    }
}