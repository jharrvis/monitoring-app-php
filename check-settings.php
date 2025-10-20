<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<h2>Check App Settings</h2>";

// Get all settings
$sql = "SELECT * FROM app_settings ORDER BY setting_key";
$settings = db()->fetchAll($sql);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Setting Key</th><th>Setting Value</th><th>Setting Type</th></tr>";

foreach ($settings as $setting) {
    echo "<tr>";
    echo "<td>{$setting['id']}</td>";
    echo "<td><strong>{$setting['setting_key']}</strong></td>";
    echo "<td>{$setting['setting_value']}</td>";
    echo "<td>{$setting['setting_type']}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><br>";
echo "<h3>Settings yang digunakan di header:</h3>";
$appSettings = getAppSettings();
echo "<pre>";
print_r($appSettings);
echo "</pre>";
?>
