<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<h2>Perbandingan Data Monitoring: Terbaru vs Tahun 2025 Triwulan III</h2>\n";

// Get monitoring configs
$sql = "SELECT * FROM monitoring_configs WHERE is_active = 1 ORDER BY page_number, display_order";
$monitoringConfigs = db()->fetchAll($sql);

// Get latest data (most recent for each monitoring)
$latestDataMap = [];
foreach ($monitoringConfigs as $config) {
    $sql = "SELECT * FROM monitoring_data
            WHERE monitoring_id = ?
            ORDER BY year DESC, quarter DESC, created_at DESC LIMIT 1";
    $data = db()->fetchOne($sql, [$config['id']]);
    if ($data) {
        $latestDataMap[$config['id']] = $data;
    }
}

// Get data for year 2025, quarter 3
$specificDataMap = [];
foreach ($monitoringConfigs as $config) {
    $sql = "SELECT * FROM monitoring_data
            WHERE monitoring_id = ? AND year = ? AND quarter = ?
            ORDER BY created_at DESC LIMIT 1";
    $data = db()->fetchOne($sql, [$config['id'], 2025, 3]);
    if ($data) {
        $specificDataMap[$config['id']] = $data;
    }
}

// Get available years for reference
$yearsQuery = "SELECT DISTINCT year FROM monitoring_data ORDER BY year DESC";
$availableYears = db()->fetchAll($yearsQuery);

echo "<p style='background: #e0f7fa; padding: 10px; border-radius: 5px;'>";
echo "<strong>Penjelasan:</strong> Data dengan filter 'Terbaru' menampilkan nilai terakhir dari setiap sistem monitoring, ";
echo "sedangkan data dengan filter tahun/kuartal menampilkan nilai untuk periode yang spesifik.";
echo "</p>";

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>\n";
echo "<tr style='background-color: #f2f2f2;'>";
echo "<th>Nama Monitoring</th>";
echo "<th>Nilai Terbaru</th>";
echo "<th>Target Terbaru</th>";
echo "<th>Persen Terbaru</th>";
echo "<th>Nilai 2025 Q3</th>";
echo "<th>Target 2025 Q3</th>";
echo "<th>Persen 2025 Q3</th>";
echo "<th>Perubahan</th>";
echo "</tr>\n";

foreach ($monitoringConfigs as $config) {
    $latestData = $latestDataMap[$config['id']] ?? null;
    $specificData = $specificDataMap[$config['id']] ?? null;
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($config['monitoring_name']) . "</strong><br>";
    echo "<small>" . htmlspecialchars($config['monitoring_description']) . "</small></td>";
    
    // Latest data
    if ($latestData) {
        echo "<td style='text-align: center;'>" . $latestData['current_value'] . "</td>";
        echo "<td style='text-align: center;'>" . $latestData['target_value'] . "</td>";
        echo "<td style='text-align: center;'>" . number_format($latestData['percentage'], 2) . "%</td>";
    } else {
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
    }
    
    // 2025 Q3 data
    if ($specificData) {
        echo "<td style='text-align: center;'>" . $specificData['current_value'] . "</td>";
        echo "<td style='text-align: center;'>" . $specificData['target_value'] . "</td>";
        echo "<td style='text-align: center;'>" . number_format($specificData['percentage'], 2) . "%</td>";
    } else {
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
    }
    
    // Change indicator
    if ($latestData && $specificData) {
        if ($latestData['current_value'] != $specificData['current_value']) {
            $change = $latestData['current_value'] - $specificData['current_value'];
            if ($change > 0) {
                echo "<td style='text-align: center; color: green;'>+" . $change . "</td>";
            } else {
                echo "<td style='text-align: center; color: red;'>" . $change . "</td>";
            }
        } else {
            echo "<td style='text-align: center; color: #666;'>Sama</td>";
        }
    } else {
        echo "<td style='text-align: center; color: #666;'>-</td>";
    }
    
    echo "</tr>\n";
}

echo "</table>\n";

// Summary
$latestTotal = array_sum(array_column($latestDataMap, 'current_value'));
$specificTotal = array_sum(array_column($specificDataMap, 'current_value'));

echo "<br><h3>Ringkasan:</h3>\n";
echo "<ul>\n";
echo "<li>Total Nilai Terbaru: {$latestTotal}</li>\n";
echo "<li>Total Nilai 2025 Q3: {$specificTotal}</li>\n";
echo "<li>Selisih: " . ($latestTotal - $specificTotal) . "</li>\n";
echo "</ul>\n";

echo "<p style='margin-top: 20px; padding: 10px; background: #fff3e0; border-radius: 5px;'>";
echo "<strong>Informasi:</strong> Kedua nilai benar - mereka hanya merepresentasikan periode waktu yang berbeda. ";
echo "'Terbaru' menunjukkan data terkini dari setiap sistem monitoring, sedangkan '2025 Q3' ";
echo "menunjukkan data untuk kuartal ketiga tahun 2025. Perbedaan nilai menunjukkan ";
echo "perkembangan atau perubahan yang terjadi antara periode tersebut.";
echo "</p>\n";

echo "<a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>← Kembali ke Dashboard</a>\n";
?>