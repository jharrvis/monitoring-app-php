<?php
// JSON Response Helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get JSON Input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// Sanitize Input
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get App Settings
function getAppSettings() {
    try {
        return cache()->getOrSet('app_settings', function() {
            try {
                $sql = "SELECT setting_key, setting_value, setting_type FROM app_settings";
                $rows = db()->fetchAll($sql);

                $settings = [];
                foreach ($rows as $row) {
                    $value = $row['setting_value'];

                    // Type casting
                    if ($row['setting_type'] === 'number') {
                        $value = (int)$value;
                    } elseif ($row['setting_type'] === 'boolean') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    }

                    $settings[$row['setting_key']] = $value;
                }

                return $settings;
            } catch (Exception $e) {
                // Table doesn't exist, return empty array
                return [];
            }
        }, 3600); // 1 hour cache
    } catch (Exception $e) {
        return [];
    }
}

// Get Single Setting
function getSetting($key, $default = null) {
    $settings = getAppSettings();
    return $settings[$key] ?? $default;
}

// Update Setting
function updateSetting($key, $value) {
    $sql = "UPDATE app_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
    $result = db()->execute($sql, [$value, $key]);

    if ($result) {
        cache()->delete('app_settings');
        return true;
    }
    return false;
}

// Calculate Percentage and Status
function calculateStatus($currentValue, $targetValue, $maxValue = 100) {
    $percentage = $targetValue > 0 ? ($currentValue / $targetValue) * 100 : 0;
    $percentage = min($percentage, $maxValue);

    $status = 'critical';
    if ($percentage >= 100) {
        $status = 'good';
    } elseif ($percentage >= 50) {
        $status = 'warning';
    }

    return [
        'percentage' => round($percentage, 2),
        'status' => $status
    ];
}

// Format Date
function formatDate($date, $format = 'd/m/Y H:i:s') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// Log to File
function logToFile($message, $filename = 'app.log') {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";

    file_put_contents("{$logDir}/{$filename}", $logMessage, FILE_APPEND);
}

// Get Current Quarter
function getCurrentQuarter() {
    $month = (int)date('n');
    return ceil($month / 3);
}

// Get Current Year
function getCurrentYear() {
    return (int)date('Y');
}

// Validate Quarter Data
function validateQuarterData($year, $quarter) {
    $currentYear = getCurrentYear();
    $currentQuarter = getCurrentQuarter();

    if ($year < 2020 || $year > $currentYear + 1) {
        return false;
    }

    if ($quarter < 1 || $quarter > 4) {
        return false;
    }

    return true;
}

// Convert to Roman Numeral
function toRoman($num) {
    $map = [
        4 => 'IV',
        3 => 'III',
        2 => 'II',
        1 => 'I'
    ];
    return $map[$num] ?? $num;
}

// Quarter Label
function getQuarterLabel($quarter) {
    return 'Triwulan ' . toRoman($quarter);
}
?>
