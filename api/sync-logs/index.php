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
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $status = $_GET['status'] ?? null;
    $syncType = $_GET['sync_type'] ?? null;
    $monitoringKey = $_GET['monitoring_key'] ?? null;

    $sql = "SELECT * FROM sync_logs WHERE 1=1";
    $params = [];

    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    if ($syncType) {
        $sql .= " AND sync_type = ?";
        $params[] = $syncType;
    }

    if ($monitoringKey) {
        $sql .= " AND monitoring_key = ?";
        $params[] = $monitoringKey;
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM sync_logs WHERE 1=1";
    if (!empty($params)) {
        $countSql .= " AND status = ?";
    }
    $countResult = db()->fetchOne($countSql, array_slice($params, 0, 1));
    $total = $countResult['total'] ?? 0;

    // Get paginated results
    $sql .= " ORDER BY started_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $logs = db()->fetchAll($sql, $params);

    // Format timestamps and duration
    foreach ($logs as &$log) {
        if ($log['started_at']) {
            $log['started_at_formatted'] = date('d M Y H:i:s', strtotime($log['started_at']));
        }
        if ($log['completed_at']) {
            $log['completed_at_formatted'] = date('d M Y H:i:s', strtotime($log['completed_at']));
        }
        if ($log['duration_seconds']) {
            $log['duration_formatted'] = formatDuration($log['duration_seconds']);
        }
    }

    jsonResponse([
        'data' => $logs,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
}

function handlePost() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON input'], 400);
    }

    // Validate required fields
    $required = ['monitoring_key', 'sync_type', 'status', 'message'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            jsonResponse(['error' => "Field {$field} is required"], 400);
        }
    }

    try {
        $sql = "INSERT INTO sync_logs (
                    monitoring_key, sync_type, status, message,
                    total_periods, successful_periods, failed_periods,
                    started_at, completed_at, duration_seconds
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";

        db()->execute($sql, [
            $input['monitoring_key'],
            $input['sync_type'],
            $input['status'],
            $input['message'],
            $input['total_periods'] ?? 0,
            $input['successful'] ?? $input['successful_periods'] ?? 0,
            $input['failed'] ?? $input['failed_periods'] ?? 0,
            $input['duration'] ?? $input['duration_seconds'] ?? 0
        ]);

        jsonResponse([
            'success' => true,
            'message' => 'Log saved successfully',
            'id' => db()->lastInsertId()
        ], 201);
    } catch (Exception $e) {
        error_log("Failed to save log: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to save log', 'message' => $e->getMessage()], 500);
    }
}

function handleDelete() {
    $id = $_GET['id'] ?? null;
    $clearAll = $_GET['clear_all'] ?? false;

    if ($clearAll) {
        // Keep only last 100 logs
        db()->execute("DELETE FROM sync_logs WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM sync_logs ORDER BY started_at DESC LIMIT 100
            ) tmp
        )");

        jsonResponse([
            'success' => true,
            'message' => 'Old logs cleared, kept last 100 entries'
        ]);
    } elseif ($id) {
        $existing = db()->fetchOne("SELECT id FROM sync_logs WHERE id = ?", [$id]);
        if (!$existing) {
            jsonResponse(['error' => 'Log not found'], 404);
        }

        db()->execute("DELETE FROM sync_logs WHERE id = ?", [$id]);

        jsonResponse([
            'success' => true,
            'message' => 'Log deleted successfully'
        ]);
    } else {
        jsonResponse(['error' => 'ID or clear_all parameter required'], 400);
    }
}

function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . 'm ' . $secs . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'm';
    }
}
?>
