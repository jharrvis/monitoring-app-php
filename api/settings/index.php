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

        case 'PUT':
            auth()->requireAuth();
            handlePut();
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
}

function handleGet() {
    try {
        $settings = getAppSettings();
        jsonResponse(['data' => $settings]);
    } catch (Exception $e) {
        // If settings table doesn't exist, return empty
        jsonResponse(['data' => []]);
    }
}

function handlePut() {
    $input = getJsonInput();

    if (empty($input)) {
        jsonResponse(['error' => 'No settings provided'], 400);
    }

    db()->beginTransaction();

    try {
        foreach ($input as $key => $value) {
            // Get setting type
            $setting = db()->fetchOne(
                "SELECT setting_type FROM app_settings WHERE setting_key = ?",
                [$key]
            );

            if ($setting) {
                // Type conversion
                if ($setting['setting_type'] === 'number') {
                    $value = (int)$value;
                } elseif ($setting['setting_type'] === 'boolean') {
                    $value = $value ? 'true' : 'false';
                }

                // Update setting
                db()->execute(
                    "UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?",
                    [$value, $key]
                );
            }
        }

        db()->commit();

        // Clear cache
        cache()->delete('app_settings');

        jsonResponse([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);

    } catch (Exception $e) {
        db()->rollback();
        throw $e;
    }
}