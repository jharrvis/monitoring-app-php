<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Get monitoring configs to have the names
$sql = "SELECT * FROM monitoring_configs WHERE is_active = 1 ORDER BY page_number, display_order";
$monitoringConfigs = db()->fetchAll($sql);

// Get monitoring data for year 2024, quarter 1
$sql = "SELECT * FROM monitoring_data 
        WHERE year = 2024 AND quarter = 1 
        ORDER BY monitoring_id, created_at DESC";
$monitoringData = db()->fetchAll($sql);

// Group data by monitoring_id to get latest data for each monitoring system
$dataMap = [];
foreach ($monitoringData as $data) {
    $monitoringId = $data['monitoring_id'];
    // Only store if we don't have data for this monitoring_id yet (latest entry)
    if (!isset($dataMap[$monitoringId])) {
        $dataMap[$monitoringId] = $data;
    }
}

echo "<h2>Breakdown Nilai Per Item - Tahun 2024 Triwulan 1</h2>\n";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background-color: #f2f2f2;'>";
echo "<th>No</th>";
echo "<th>Nama Monitoring</th>";
echo "<th>Nilai Saat Ini</th>";
echo "<th>Nilai Target</th>";
echo "<th>Persentase</th>";
echo "<th>Deskripsi</th>";
echo "</tr>\n";

$counter = 1;

foreach ($monitoringConfigs as $config) {
    $data = $dataMap[$config['id']] ?? null;
    
    echo "<tr>";
    echo "<td>{$counter}</td>";
    echo "<td>" . htmlspecialchars($config['monitoring_name']) . "</td>";
    
    if ($data) {
        echo "<td style='text-align: center;'>" . $data['current_value'] . "</td>";
        echo "<td style='text-align: center;'>" . $data['target_value'] . "</td>";
        echo "<td style='text-align: center;'>" . number_format($data['percentage'], 2) . "%</td>";
    } else {
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
    }
    
    echo "<td>" . htmlspecialchars($config['monitoring_description']) . "</td>";
    echo "</tr>\n";
    
    $counter++;
}

echo "</table>\n";

// Also show summary information
$totalSystems = count($monitoringConfigs);
$dataAvailable = count($dataMap);
$dataMissing = $totalSystems - $dataAvailable;

echo "<br><h3>Ringkasan:</h3>\n";
echo "<ul>\n";
echo "<li>Total Sistem Monitoring: {$totalSystems}</li>\n";
echo "<li>Data Tersedia: {$dataAvailable}</li>\n";
echo "<li>Data Tidak Tersedia: {$dataMissing}</li>\n";

if ($dataAvailable > 0) {
    $totalCurrentValues = array_sum(array_column($dataMap, 'current_value'));
    echo "<li>Total Nilai Saat Ini: {$totalCurrentValues}</li>\n";
}

echo "</ul>\n";

echo "<a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>← Kembali ke Dashboard</a>\n";
?>