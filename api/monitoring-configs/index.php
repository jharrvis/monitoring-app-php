<?php
// Clean any output buffer
while (ob_get_level()) {
    ob_end_clean();
}

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet();
            break;

        case 'POST':
            auth()->requireAuth();
            handlePost();
            break;

        case 'PUT':
            auth()->requireAuth();
            handlePut();
            break;

        case 'DELETE':
            auth()->requireAuth();
            handleDelete();
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
}

function handleGet() {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // Get single config
        $sql = "SELECT * FROM monitoring_configs WHERE id = ?";
        $config = db()->fetchOne($sql, [$id]);

        if (!$config) {
            jsonResponse(['error' => 'Config not found'], 404);
        }

        jsonResponse(['data' => $config]);
    } else {
        // Get all configs
        $pageNumber = $_GET['page_number'] ?? null;
        $isActive = $_GET['is_active'] ?? null;

        $sql = "SELECT * FROM monitoring_configs WHERE 1=1";
        $params = [];

        if ($pageNumber !== null) {
            $sql .= " AND page_number = ?";
            $params[] = $pageNumber;
        }

        if ($isActive !== null) {
            $sql .= " AND is_active = ?";
            $params[] = $isActive;
        }

        $sql .= " ORDER BY page_number, display_order";

        $configs = db()->fetchAll($sql, $params);
        jsonResponse(['data' => $configs]);
    }
}

function handlePost() {
    $input = getJsonInput();

    // Validate required fields
    $required = ['monitoring_key', 'monitoring_name', 'monitoring_description'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            jsonResponse(['error' => "Field {$field} is required"], 400);
        }
    }

    // Check if key already exists
    $existing = db()->fetchOne("SELECT id FROM monitoring_configs WHERE monitoring_key = ?", [$input['monitoring_key']]);
    if ($existing) {
        jsonResponse(['error' => 'Monitoring key already exists'], 400);
    }

    $sql = "INSERT INTO monitoring_configs (
                monitoring_key, monitoring_name, monitoring_description,
                max_value, unit, icon, page_number, display_order,
                is_active, is_realtime, api_endpoint, detail_url,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $params = [
        $input['monitoring_key'],
        $input['monitoring_name'],
        $input['monitoring_description'],
        $input['max_value'] ?? 100,
        $input['unit'] ?? '%',
        $input['icon'] ?? '📊',
        $input['page_number'] ?? 1,
        $input['display_order'] ?? 0,
        $input['is_active'] ?? 1,
        $input['is_realtime'] ?? 1,
        $input['api_endpoint'] ?? null,
        $input['detail_url'] ?? null
    ];

    db()->execute($sql, $params);
    $id = db()->lastInsertId();

    // Clear cache
    cache()->clear();

    jsonResponse([
        'success' => true,
        'message' => 'Config created successfully',
        'id' => $id
    ], 201);
}

function handlePut() {
    $input = getJsonInput();
    $id = $input['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }

    // Check if config exists
    $existing = db()->fetchOne("SELECT id FROM monitoring_configs WHERE id = ?", [$id]);
    if (!$existing) {
        jsonResponse(['error' => 'Config not found'], 404);
    }

    // Check if key is being changed and already exists
    if (isset($input['monitoring_key'])) {
        $keyCheck = db()->fetchOne(
            "SELECT id FROM monitoring_configs WHERE monitoring_key = ? AND id != ?",
            [$input['monitoring_key'], $id]
        );
        if ($keyCheck) {
            jsonResponse(['error' => 'Monitoring key already exists'], 400);
        }
    }

    $updates = [];
    $params = [];

    $allowedFields = [
        'monitoring_key', 'monitoring_name', 'monitoring_description',
        'max_value', 'unit', 'icon', 'page_number', 'display_order',
        'is_active', 'is_realtime', 'api_endpoint', 'detail_url'
    ];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = $input[$field];
        }
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $updates[] = "updated_at = NOW()";
    $params[] = $id;

    $sql = "UPDATE monitoring_configs SET " . implode(', ', $updates) . " WHERE id = ?";
    db()->execute($sql, $params);

    // Clear cache
    cache()->clear();

    jsonResponse([
        'success' => true,
        'message' => 'Config updated successfully'
    ]);
}

function handleDelete() {
    $id = $_GET['id'] ?? getJsonInput()['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }

    // Check if config exists
    $existing = db()->fetchOne("SELECT id FROM monitoring_configs WHERE id = ?", [$id]);
    if (!$existing) {
        jsonResponse(['error' => 'Config not found'], 404);
    }

    // Delete related monitoring data first
    db()->execute("DELETE FROM monitoring_data WHERE monitoring_id = ?", [$id]);

    // Delete config
    db()->execute("DELETE FROM monitoring_configs WHERE id = ?", [$id]);

    // Clear cache
    cache()->clear();

    jsonResponse([
        'success' => true,
        'message' => 'Config deleted successfully'
    ]);
}