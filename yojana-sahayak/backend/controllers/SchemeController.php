<?php

require_once __DIR__ . "/../models/Scheme.php";
require_once __DIR__ . "/../utils/response.php";

class SchemeController
{
    private $schemeModel;

    public function __construct($pdo)
    {
        $this->schemeModel = new Scheme($pdo);
    }


    // Validate scheme input

    private function validateScheme($data)
    {
        $name = trim($data["name_en"] ?? "");

        if ($name === "") {
            jsonResponse(false, "English scheme name is required.", null, 422);
        }

        $level = $data["scheme_level"] ?? "central";

        if (!in_array($level, ["central", "state"], true)) {
            jsonResponse(false, "Invalid scheme level.", null, 422);
        }

        $urlFields = ["official_url", "application_url"];

        foreach ($urlFields as $field) {
            $value = trim($data[$field] ?? "");

            if ($value !== "" && !filter_var($value, FILTER_VALIDATE_URL)) {
                jsonResponse(false, "Invalid URL in " . $field, null, 422);
            }
        }

        $stateCode = trim($data["state_code"] ?? "");

        if ($level === "state" && $stateCode === "") {
            jsonResponse(false, "State code is required for state schemes.", null, 422);
        }

        return [
            "scheme_code" => $this->nullable($data["scheme_code"] ?? null),
            "name_en" => $name,
            "name_hi" => $this->nullable($data["name_hi"] ?? null),
            "description_en" => $this->nullable($data["description_en"] ?? null),
            "description_hi" => $this->nullable($data["description_hi"] ?? null),
            "benefits_en" => $this->nullable($data["benefits_en"] ?? null),
            "benefits_hi" => $this->nullable($data["benefits_hi"] ?? null),
            "department" => $this->nullable($data["department"] ?? null),
            "scheme_level" => $level,
            "state_code" => $level === "state" ? $stateCode : null,
            "category" => $this->nullable($data["category"] ?? null),
            "official_url" => $this->nullable($data["official_url"] ?? null),
            "application_url" => $this->nullable($data["application_url"] ?? null)
        ];
    }


    private function nullable($value)
    {
        if ($value === null || trim((string)$value) === "") {
            return null;
        }

        return trim((string)$value);
    }


    // GET all schemes

    public function getAll()
    {
        $schemes = $this->schemeModel->getAll();

        jsonResponse(true, "Schemes retrieved successfully.", [
            "schemes" => $schemes
        ]);
    }


    // GET individual scheme

    public function getOne($id)
    {
        $scheme = $this->schemeModel->getById($id);

        if (!$scheme) {
            jsonResponse(false, "Scheme not found.", null, 404);
        }

        jsonResponse(true, "Scheme retrieved successfully.", [
            "scheme" => $scheme
        ]);
    }


    // CREATE scheme

    public function create($data)
    {
        $schemeData = $this->validateScheme($data);

        try {
            $scheme = $this->schemeModel->create($schemeData);

            jsonResponse(true, "Scheme created successfully.", [
                "scheme" => $scheme
            ], 201);

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                jsonResponse(false, "Scheme code already exists.", null, 409);
            }

            error_log($e->getMessage());

            jsonResponse(false, "Unable to create scheme.", null, 500);
        }
    }


    // UPDATE scheme

    public function update($id, $data)
    {
        if (!$this->schemeModel->getById($id)) {
            jsonResponse(false, "Scheme not found.", null, 404);
        }

        $schemeData = $this->validateScheme($data);

        try {
            $scheme = $this->schemeModel->update($id, $schemeData);

            jsonResponse(true, "Scheme updated successfully.", [
                "scheme" => $scheme
            ]);

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                jsonResponse(false, "Scheme code already exists.", null, 409);
            }

            error_log($e->getMessage());

            jsonResponse(false, "Unable to update scheme.", null, 500);
        }
    }


    // DELETE scheme

    public function delete($id)
    {
        if (!$this->schemeModel->getById($id)) {
            jsonResponse(false, "Scheme not found.", null, 404);
        }

        $this->schemeModel->delete($id);

        jsonResponse(true, "Scheme deleted successfully.");
    }
}

?>