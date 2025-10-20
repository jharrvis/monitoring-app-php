<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/cache.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

auth()->requireAuth();

$syncType = $_GET['type'] ?? 'sipp';
$monitoringKey = strtoupper($syncType);

try {
    // Get monitoring config
    $config = db()->fetchOne(
        "SELECT * FROM monitoring_configs WHERE monitoring_key = ? AND is_active = 1",
        [$monitoringKey]
    );

    if (!$config) {
        sendProgress(['error' => 'Monitoring config not found']);
        exit;
    }

    if (!$config['api_endpoint']) {
        sendProgress(['error' => 'API endpoint not configured']);
        exit;
    }

    $currentYear = getCurrentYear();
    $currentQuarter = getCurrentQuarter();

    $totalSteps = 0;
    $completedSteps = 0;
    $results = [];

    // Determine years and quarters to sync
    $yearsToSync = [$currentYear];
    if ($currentQuarter < 4) {
        $yearsToSync[] = $currentYear - 1;
    }

    $totalSteps = count($yearsToSync) * 4; // 4 quarters per year

    sendProgress([
        'status' => 'started',
        'message' => "Memulai sinkronisasi {$config['monitoring_name']}...",
        'progress' => 0
    ]);

    foreach ($yearsToSync as $year) {
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            // Skip future quarters
            if ($year == $currentYear && $quarter > $currentQuarter) {
                $totalSteps--;
                continue;
            }

            $completedSteps++;
            $progress = round(($completedSteps / $totalSteps) * 100);

            sendProgress([
                'status' => 'progress',
                'message' => "Mengambil data Tahun {$year} Triwulan " . toRoman($quarter) . "...",
                'progress' => $progress
            ]);

            try {
                // Call external API
                $apiUrl = $config['api_endpoint'] . "?type=triwulan&triwulan={$quarter}&year={$year}&clear_cache=1";

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 30,
                        'ignore_errors' => true
                    ]
                ]);

                $response = @file_get_contents($apiUrl, false, $context);

                if ($response === false) {
                    $results[] = [
                        'year' => $year,
                        'quarter' => $quarter,
                        'status' => 'error',
                        'message' => 'Gagal menghubungi API'
                    ];
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
                    $results[] = [
                        'year' => $year,
                        'quarter' => $quarter,
                        'status' => 'error',
                        'message' => 'Format data tidak valid: ' . substr(json_encode($data), 0, 200)
                    ];
                    continue;
                }

                // Calculate percentage
                $currentValue = floatval($responseData['current_value']);
                $targetValue = floatval($responseData['target_value']);
                $percentage = $targetValue > 0 ? ($currentValue / $targetValue) * 100 : 0;
                $percentage = min($percentage, $config['max_value']);

                // Check if data exists
                $existing = db()->fetchOne(
                    "SELECT id FROM monitoring_data WHERE monitoring_id = ? AND year = ? AND quarter = ?",
                    [$config['id'], $year, $quarter]
                );

                if ($existing) {
                    // Update
                    db()->execute(
                        "UPDATE monitoring_data
                         SET current_value = ?, target_value = ?, percentage = ?, updated_at = NOW()
                         WHERE id = ?",
                        [$currentValue, $targetValue, round($percentage, 2), $existing['id']]
                    );
                } else {
                    // Insert
                    db()->execute(
                        "INSERT INTO monitoring_data (monitoring_id, year, quarter, current_value, target_value, percentage, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                        [$config['id'], $year, $quarter, $currentValue, $targetValue, round($percentage, 2)]
                    );
                }

                $results[] = [
                    'year' => $year,
                    'quarter' => $quarter,
                    'status' => 'success',
                    'current_value' => $currentValue,
                    'target_value' => $targetValue,
                    'percentage' => round($percentage, 2)
                ];

                // Log sync (optional)
                try {
                    db()->execute(
                        "INSERT INTO sync_logs (sync_type, year, quarter, status, message, synced_at)
                         VALUES (?, ?, ?, 'success', 'Data berhasil disinkronkan', NOW())",
                        [$monitoringKey, $year, $quarter]
                    );
                } catch (Exception $logErr) {
                    // Ignore if can't log
                }

            } catch (Exception $e) {
                $results[] = [
                    'year' => $year,
                    'quarter' => $quarter,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }

            usleep(200000); // 200ms delay
        }
    }

    // Clear cache
    cache()->clear();

    // Send completion
    sendProgress([
        'status' => 'completed',
        'message' => 'Sinkronisasi selesai',
        'progress' => 100,
        'results' => $results
    ]);

} catch (Exception $e) {
    error_log("Sync error: " . $e->getMessage());
    sendProgress([
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'progress' => 0
    ]);
}

function sendProgress($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}
?>
