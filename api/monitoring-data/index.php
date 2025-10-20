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
        // Get single data
        $sql = "SELECT md.*, mc.monitoring_name, mc.monitoring_key, mc.max_value, mc.unit
                FROM monitoring_data md
                JOIN monitoring_configs mc ON md.monitoring_id = mc.id
                WHERE md.id = ?";

        $data = db()->fetchOne($sql, [$id]);

        if (!$data) {
            jsonResponse(['error' => 'Data not found'], 404);
        }

        jsonResponse(['data' => $data]);
    } else {
        // Get all data with filters
        $year = $_GET['year'] ?? null;
        $quarter = $_GET['quarter'] ?? null;
        $monitoringId = $_GET['monitoring_id'] ?? null;
        $search = $_GET['search'] ?? null;

        $sql = "SELECT md.*, mc.monitoring_name, mc.monitoring_key, mc.max_value, mc.unit
                FROM monitoring_data md
                JOIN monitoring_configs mc ON md.monitoring_id = mc.id
                WHERE 1=1";

        $params = [];

        // Add year filter if provided
        if ($year) {
            $sql .= " AND md.year = ?";
            $params[] = $year;
        }

        if ($quarter) {
            $sql .= " AND md.quarter = ?";
            $params[] = $quarter;
        }

        if ($monitoringId) {
            $sql .= " AND md.monitoring_id = ?";
            $params[] = $monitoringId;
        }

        if ($search) {
            $sql .= " AND (mc.monitoring_name LIKE ? OR mc.monitoring_key LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY md.year DESC, md.quarter DESC, mc.page_number, mc.display_order";

        $dataList = db()->fetchAll($sql, $params);

        // Calculate status based on percentage from database
        foreach ($dataList as &$data) {
            $percentage = (float)$data['percentage'];

            // Determine status based on percentage
            if ($percentage >= 100) {
                $data['status'] = 'good';
            } elseif ($percentage >= 50) {
                $data['status'] = 'warning';
            } else {
                $data['status'] = 'critical';
            }
        }

        jsonResponse(['data' => $dataList]);
    }
}

function handlePost() {
    $input = getJsonInput();

    // Validate required fields
    $required = ['monitoring_id', 'year', 'quarter', 'current_value', 'target_value'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            jsonResponse(['error' => "Field {$field} is required"], 400);
        }
    }

    // Validate quarter
    if (!validateQuarterData($input['year'], $input['quarter'])) {
        jsonResponse(['error' => 'Invalid year or quarter'], 400);
    }

    // Check if monitoring config exists
    $config = db()->fetchOne("SELECT id, max_value FROM monitoring_configs WHERE id = ?", [$input['monitoring_id']]);
    if (!$config) {
        jsonResponse(['error' => 'Monitoring config not found'], 404);
    }

    // Get config to check max_value for percentage cap
    $config = db()->fetchOne("SELECT max_value FROM monitoring_configs WHERE id = ?", [$input['monitoring_id']]);
    
    // Calculate percentage based on current_value / target_value
    $percentage = $input['target_value'] > 0
        ? ($input['current_value'] / $input['target_value']) * 100
        : 0;
    
    // Cap percentage to max_value if config exists
    if ($config) {
        $percentage = min($percentage, $config['max_value']);
    }

    // Check if data already exists
    $existing = db()->fetchOne(
        "SELECT id FROM monitoring_data WHERE monitoring_id = ? AND year = ? AND quarter = ?",
        [$input['monitoring_id'], $input['year'], $input['quarter']]
    );

    if ($existing) {
        // Update existing data
        $sql = "UPDATE monitoring_data
                SET current_value = ?, target_value = ?, percentage = ?, updated_at = NOW()
                WHERE id = ?";

        db()->execute($sql, [
            $input['current_value'],
            $input['target_value'],
            round($percentage, 2),
            $existing['id']
        ]);

        $id = $existing['id'];
        $message = 'Data updated successfully';
    } else {
        // Insert new data
        $sql = "INSERT INTO monitoring_data (
                    monitoring_id, year, quarter, current_value, target_value, percentage,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

        db()->execute($sql, [
            $input['monitoring_id'],
            $input['year'],
            $input['quarter'],
            $input['current_value'],
            $input['target_value'],
            round($percentage, 2)
        ]);

        $id = db()->lastInsertId();
        $message = 'Data created successfully';
    }

    // Clear cache
    cache()->clear();

    jsonResponse([
        'success' => true,
        'message' => $message,
        'id' => $id
    ], 201);
}

function handlePut() {
    $input = getJsonInput();
    $id = $input['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }

    // Check if data exists
    $existing = db()->fetchOne("SELECT monitoring_id FROM monitoring_data WHERE id = ?", [$id]);
    if (!$existing) {
        jsonResponse(['error' => 'Data not found'], 404);
    }

    // Get monitoring config for max_value
    $config = db()->fetchOne("SELECT max_value FROM monitoring_configs WHERE id = ?", [$existing['monitoring_id']]);

    $updates = [];
    $params = [];

    if (isset($input['current_value'])) {
        $updates[] = "current_value = ?";
        $params[] = $input['current_value'];
    }

    if (isset($input['target_value'])) {
        $updates[] = "target_value = ?";
        $params[] = $input['target_value'];
    }

    // Recalculate percentage if values changed
    if (isset($input['current_value']) || isset($input['target_value'])) {
        $data = db()->fetchOne("SELECT current_value, target_value FROM monitoring_data WHERE id = ?", [$id]);

        $currentValue = $input['current_value'] ?? $data['current_value'];

        // Get config to check max_value for percentage cap
        $config = db()->fetchOne("SELECT max_value FROM monitoring_configs WHERE id = ?", [$existing['monitoring_id']]);
        
        // Calculate percentage based on current_value / target_value
        $targetValue = $input['target_value'] ?? $data['target_value'];
        $percentage = $targetValue > 0 ? ($currentValue / $targetValue) * 100 : 0;
        
        // Cap percentage to max_value if config exists
        if ($config) {
            $percentage = min($percentage, $config['max_value']);
        }

        $updates[] = "percentage = ?";
        $params[] = round($percentage, 2);
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $updates[] = "updated_at = NOW()";
    $params[] = $id;

    $sql = "UPDATE monitoring_data SET " . implode(', ', $updates) . " WHERE id = ?";
    db()->execute($sql, $params);

    // Clear cache
    cache()->clear();

    jsonResponse([
        'success' => true,
        'message' => 'Data updated successfully'
    ]);
}

function handleDelete() {
    $id = $_GET['id'] ?? getJsonInput()['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'ID is required'], 400);
    }

    // Check if data exists
    $existing = db()->fetchOne("SELECT id FROM monitoring_data WHERE id = ?", [$id]);
    if (!$existing) {
        jsonResponse(['error' => 'Data not found'], 404);
    }

    db()->execute("DELETE FROM monitoring_data WHERE id = ?", [$id]);

    // Clear cache
    cache()->clear();

    jsonResponse([
        'success' => true,
        'message' => 'Data deleted successfully'
    ]);
}