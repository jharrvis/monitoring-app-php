<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<h2>Cek Konfigurasi Monitoring - Kasasi dan PK</h2>\n";

// Search for monitoring configurations containing "Kasasi" or "PK"
$sql = "SELECT * FROM monitoring_configs WHERE monitoring_name LIKE '%Kasasi%' OR monitoring_name LIKE '%PK%' OR monitoring_name LIKE '%Kasasi dan PK%'";
$monitoringConfigs = db()->fetchAll($sql);

if (count($monitoringConfigs) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Nama Monitoring</th>";
    echo "<th>Deskripsi</th>";
    echo "<th>Icon</th>";
    echo "<th>Detail URL</th>";
    echo "<th>Max Value</th>";
    echo "<th>Is Active</th>";
    echo "<th>Page Number</th>";
    echo "<th>Display Order</th>";
    echo "<th>Created At</th>";
    echo "<th>Updated At</th>";
    echo "</tr>\n";

    foreach ($monitoringConfigs as $config) {
        echo "<tr>";
        echo "<td>" . $config['id'] . "</td>";
        echo "<td>" . htmlspecialchars($config['monitoring_name']) . "</td>";
        echo "<td>" . htmlspecialchars($config['monitoring_description']) . "</td>";
        echo "<td>" . htmlspecialchars($config['icon']) . "</td>";
        echo "<td>" . htmlspecialchars($config['detail_url']) . "</td>";
        echo "<td>" . $config['max_value'] . "</td>";
        echo "<td>" . $config['is_active'] . "</td>";
        echo "<td>" . $config['page_number'] . "</td>";
        echo "<td>" . $config['display_order'] . "</td>";
        echo "<td>" . $config['created_at'] . "</td>";
        echo "<td>" . $config['updated_at'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>Tidak ditemukan konfigurasi monitoring dengan nama mengandung 'Kasasi' atau 'PK'</p>\n";
}

echo "<br><h2>Cek Data Monitoring - Kasasi dan PK</h2>\n";

// Search for monitoring data related to "Kasasi" or "PK"
$sql = "SELECT m.monitoring_name, d.* FROM monitoring_data d 
        JOIN monitoring_configs m ON d.monitoring_id = m.id 
        WHERE m.monitoring_name LIKE '%Kasasi%' OR m.monitoring_name LIKE '%PK%' OR m.monitoring_name LIKE '%Kasasi dan PK%'
        ORDER BY d.year DESC, d.quarter DESC, d.created_at DESC
        LIMIT 20";
$monitoringData = db()->fetchAll($sql);

if (count($monitoringData) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Monitoring ID</th>";
    echo "<th>Monitoring Name</th>";
    echo "<th>Current Value</th>";
    echo "<th>Target Value</th>";
    echo "<th>Percentage</th>";
    echo "<th>Year</th>";
    echo "<th>Quarter</th>";
    echo "<th>Created At</th>";
    echo "<th>Updated At</th>";
    echo "</tr>\n";

    foreach ($monitoringData as $data) {
        echo "<tr>";
        echo "<td>" . $data['monitoring_id'] . "</td>";
        echo "<td>" . htmlspecialchars($data['monitoring_name']) . "</td>";
        echo "<td style='text-align: center;'>" . $data['current_value'] . "</td>";
        echo "<td style='text-align: center;'>" . $data['target_value'] . "</td>";
        echo "<td style='text-align: center;'>" . number_format($data['percentage'], 2) . "%</td>";
        echo "<td style='text-align: center;'>" . $data['year'] . "</td>";
        echo "<td style='text-align: center;'>" . $data['quarter'] . "</td>";
        echo "<td>" . $data['created_at'] . "</td>";
        echo "<td>" . $data['updated_at'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>Tidak ditemukan data monitoring dengan nama mengandung 'Kasasi' atau 'PK'</p>\n";
}

echo "<br><a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>← Kembali ke Dashboard</a>\n";
?>