<?php
/**
 * Background Sync Service
 * Run this script via cron job every 30 minutes
 *
 * Cron example:
 * (star)/30 * * * * php /path/to/monitoring-app-php/cron/background-sync.php >> /path/to/logs/cron.log 2>&1
 * Replace (star) with actual asterisk symbol
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$logFile = __DIR__ . '/../logs/background-sync.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

writeLog("=== Background Sync Started ===");

try {
    // Get all active monitoring configs with API endpoints
    $configs = db()->fetchAll(
        "SELECT * FROM monitoring_configs
         WHERE is_active = 1 AND api_endpoint IS NOT NULL AND api_endpoint != ''"
    );

    if (empty($configs)) {
        writeLog("No monitoring configs found with API endpoints");
        writeLog("=== Background Sync Completed ===\n");
        exit(0);
    }

    writeLog("Found " . count($configs) . " monitoring configs to sync");

    $currentYear = getCurrentYear();
    $currentQuarter = getCurrentQuarter();

    foreach ($configs as $config) {
        writeLog("Syncing: {$config['monitoring_name']} ({$config['monitoring_key']})");

        // Sync current quarter
        try {
            $apiUrl = $config['api_endpoint'] . "?type=triwulan&triwulan={$currentQuarter}&year={$currentYear}&clear_cache=1";

            writeLog("  Calling API: {$apiUrl}");

            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents($apiUrl, false, $context);

            if ($response === false) {
                writeLog("  ERROR: Failed to call API");
                continue;
            }

            $data = json_decode($response, true);

            // Check if response has nested data structure or direct structure
            $responseData = null;

            // Try different response structures
            if (isset($data['data']['database_record']['current_value'])) {
                // Structure: {data: {database_record: {current_value, target_value}}}
                $responseData = $data['data']['database_record'];
            } elseif (isset($data['data']['current_value'])) {
                // Structure: {data: {current_value, target_value}}
                $responseData = $data['data'];
            } elseif (isset($data['current_value'])) {
                // Structure: {current_value, target_value}
                $responseData = $data;
            }

            if (!$responseData || !isset($responseData['current_value']) || !isset($responseData['target_value'])) {
                writeLog("  ERROR: Invalid data format - Response: " . substr(json_encode($data), 0, 500));
                continue;
            }

            $currentValue = floatval($responseData['current_value']);
            $targetValue = floatval($responseData['target_value']);
            $percentage = $targetValue > 0 ? ($currentValue / $targetValue) * 100 : 0;
            $percentage = min($percentage, $config['max_value']);

            // Check if data exists
            $existing = db()->fetchOne(
                "SELECT id FROM monitoring_data WHERE monitoring_id = ? AND year = ? AND quarter = ?",
                [$config['id'], $currentYear, $currentQuarter]
            );

            if ($existing) {
                // Update
                db()->execute(
                    "UPDATE monitoring_data
                     SET current_value = ?, target_value = ?, percentage = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$currentValue, $targetValue, round($percentage, 2), $existing['id']]
                );
                writeLog("  Updated: Current={$currentValue}, Target={$targetValue}, Percentage=" . round($percentage, 2) . "%");
            } else {
                // Insert
                db()->execute(
                    "INSERT INTO monitoring_data (monitoring_id, year, quarter, current_value, target_value, percentage, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [$config['id'], $currentYear, $currentQuarter, $currentValue, $targetValue, round($percentage, 2)]
                );
                writeLog("  Inserted: Current={$currentValue}, Target={$targetValue}, Percentage=" . round($percentage, 2) . "%");
            }

            // Log sync (optional - if table exists)
            try {
                db()->execute(
                    "INSERT INTO sync_logs (sync_type, year, quarter, status, message, synced_at)
                     VALUES (?, ?, ?, 'success', 'Background sync completed', NOW())",
                    [$config['monitoring_key'], $currentYear, $currentQuarter]
                );
            } catch (Exception $logErr) {
                // If sync_logs table structure is different or doesn't exist, just log it
                writeLog("  Note: Could not log to sync_logs table");
            }

            writeLog("  SUCCESS");

        } catch (Exception $e) {
            writeLog("  ERROR: " . $e->getMessage());

            // Log error (optional)
            try {
                db()->execute(
                    "INSERT INTO sync_logs (sync_type, year, quarter, status, message, synced_at)
                     VALUES (?, ?, ?, 'error', ?, NOW())",
                    [$config['monitoring_key'], $currentYear, $currentQuarter, $e->getMessage()]
                );
            } catch (Exception $logErr) {
                // Ignore if can't log
            }
        }

        // Small delay between requests
        usleep(500000); // 500ms
    }

    // Update sync settings (if table exists)
    try {
        db()->execute(
            "UPDATE sync_settings SET last_sync_time = NOW() WHERE id = 1"
        );
    } catch (Exception $e) {
        // Ignore if table doesn't exist
        writeLog("Note: sync_settings table not found (optional)");
    }

    writeLog("=== Background Sync Completed Successfully ===\n");

} catch (Exception $e) {
    writeLog("CRITICAL ERROR: " . $e->getMessage());
    writeLog("=== Background Sync Failed ===\n");
    exit(1);
}

exit(0);
?>
