<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$quarter = isset($_GET['quarter']) ? (int)$_GET['quarter'] : null;

if ($year && $quarter) {
    echo "<h2>Perbandingan Data Monitoring: Terbaru vs Tahun {$year} Triwulan {$quarter}</h2>\n";
} else {
    echo "<h2>Perbandingan Data Monitoring: Terbaru vs Periode Tertentu</h2>\n";
}

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

// Get data for specific period if provided
$specificDataMap = [];
if ($year && $quarter) {
    foreach ($monitoringConfigs as $config) {
        $sql = "SELECT * FROM monitoring_data
                WHERE monitoring_id = ? AND year = ? AND quarter = ?
                ORDER BY created_at DESC LIMIT 1";
        $data = db()->fetchOne($sql, [$config['id'], $year, $quarter]);
        if ($data) {
            $specificDataMap[$config['id']] = $data;
        }
    }
}

// Get available years for reference
$yearsQuery = "SELECT DISTINCT year FROM monitoring_data ORDER BY year DESC";
$availableYears = db()->fetchAll($yearsQuery);

echo "<p style='background: #e0f7fa; padding: 10px; border-radius: 5px;'>";
echo "<strong>Penjelasan:</strong> Card 'Total Nilai Saat Ini' menghitung jumlah dari semua nilai current_value. ";
echo "Ketika filter berubah, maka data yang digunakan untuk perhitungan juga berbeda.";
echo "</p>";

// Form for selection
echo "<div style='margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px;'>";
echo "<form method='GET' style='display: flex; gap: 10px; align-items: center;'>";
echo "Lihat perbandingan untuk: ";
echo "<select name='year' onchange='this.form.submit()' style='padding: 5px;'>";
echo "<option value=''>Pilih Tahun</option>";
foreach ($availableYears as $yearRow) {
    $selected = (isset($_GET['year']) && $_GET['year'] == $yearRow['year']) ? 'selected' : '';
    echo "<option value='{$yearRow['year']}' {$selected}>{$yearRow['year']}</option>";
}
echo "</select>";

echo "<select name='quarter' onchange='this.form.submit()' style='padding: 5px;'>";
echo "<option value=''>Pilih Triwulan</option>";
for ($q = 1; $q <= 4; $q++) {
    $selected = (isset($_GET['quarter']) && $_GET['quarter'] == $q) ? 'selected' : '';
    $quarterName = 'I';
    if ($q == 2) $quarterName = 'II';
    if ($q == 3) $quarterName = 'III';
    if ($q == 4) $quarterName = 'IV';
    echo "<option value='{$q}' {$selected}>{$quarterName}</option>";
}
echo "</select>";

echo "<input type='submit' value='Lihat' style='padding: 5px 10px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
echo "</form>";
echo "</div>";

if ($year && $quarter) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>\n";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>Nama Monitoring</th>";
    echo "<th>Nilai Terbaru</th>";
    echo "<th>Target Terbaru</th>";
    echo "<th>Persen Terbaru</th>";
    echo "<th>Nilai {$year} Q{$quarter}</th>";
    echo "<th>Target {$year} Q{$quarter}</th>";
    echo "<th>Persen {$year} Q{$quarter}</th>";
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
        
        // Specific period data
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

    echo "<br><h3>Ringkasan Perhitungan 'Total Nilai Saat Ini':</h3>\n";
    echo "<ul>\n";
    echo "<li>Total Nilai Saat Ini (Data Terbaru): <strong>{$latestTotal}</strong></li>\n";
    echo "<li>Total Nilai Saat Ini ({$year} Q{$quarter}): <strong>{$specificTotal}</strong></li>\n";
    echo "<li>Selisih: <strong>" . ($latestTotal - $specificTotal) . "</strong></li>\n";
    echo "</ul>\n";

    echo "<p style='margin-top: 20px; padding: 15px; background: #fff3e0; border-radius: 5px;'>";
    echo "<strong>Penjelasan:</strong> Perbedaan nilai pada card 'Total Nilai Saat Ini' terjadi karena data ";
    echo "yang dijumlahkan berbeda. Data terbaru adalah nilai terkini dari setiap sistem monitoring, ";
    echo "sedangkan data {$year} Q{$quarter} adalah nilai untuk periode tersebut. ";
    echo "Perubahan nilai dari waktu ke waktu menunjukkan perkembangan atau perubahan ";
    echo "yang terjadi dalam sistem monitoring.";
    echo "</p>\n";
} else {
    echo "<div style='text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px;'>";
    echo "<p>Silakan pilih tahun dan triwulan di atas untuk melihat perbandingan data</p>";
    echo "</div>";
}

echo "<a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>← Kembali ke Dashboard</a>\n";
?>