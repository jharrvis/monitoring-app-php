<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<h2>Penjelasan Filter Dashboard</h2>\n";

// Get monitoring configs
$sql = "SELECT * FROM monitoring_configs WHERE is_active = 1 ORDER BY page_number, display_order";
$monitoringConfigs = db()->fetchAll($sql);

// Get latest data (most recent for each monitoring) - equivalent to "Terbaru" filter
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

// Get data for year 2025, quarter 1 - equivalent to selecting "2025" + "Q1"
$specificDataMap = [];
foreach ($monitoringConfigs as $config) {
    $sql = "SELECT * FROM monitoring_data
            WHERE monitoring_id = ? AND year = ? AND quarter = ?
            ORDER BY created_at DESC LIMIT 1";
    $data = db()->fetchOne($sql, [$config['id'], 2025, 1]);
    if ($data) {
        $specificDataMap[$config['id']] = $data;
    }
}

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Perbedaan Pengertian Filter</h3>";
echo "<p><strong>1. Filter 'Terbaru' (tanpa tahun/kuartal dipilih):</strong></p>";
echo "<ul>";
echo "<li>Sistem mengambil data <strong>terbaru</strong> dari <strong>setiap</strong> sistem monitoring</li>";
echo "<li>Artinya: Ambil entri data terakhir untuk setiap sistem, berapapun tahun dan kuartalnya</li>";
echo "<li>Jika hari ini adalah Oktober 2025, maka 'terbaru' bisa saja dari Q3 2025 atau Q4 2025</li>";
echo "</ul>";

echo "<p><strong>2. Filter 'Tahun 2025, Triwulan 1':</strong></p>";
echo "<ul>";
echo "<li>Sistem mengambil data <strong>spesifik</strong> untuk <strong>periode 2025 Q1</strong></li>";
echo "<li>Artinya: Hanya tampilkan data yang direkam untuk kuartal pertama tahun 2025</li>";
echo "<li>Tidak peduli apakah itu data terbaru atau bukan</li>";
echo "</ul>";

echo "<p><strong>Kenapa hasil berbeda?</strong> Karena dua filter ini mencari data dari <strong>periode waktu berbeda</strong>.</p>";
echo "</div>";

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>\n";
echo "<tr style='background-color: #f2f2f2;'>";
echo "<th>Nama Monitoring</th>";
echo "<th>Data Terbaru</th>";
echo "<th>Tahun/Kuartal (Terbaru)</th>";
echo "<th>Nilai Terbaru</th>";
echo "<th>Data 2025 Q1</th>";
echo "<th>Nilai 2025 Q1</th>";
echo "<th>Perbedaan</th>";
echo "</tr>\n";

foreach ($monitoringConfigs as $config) {
    $latestData = $latestDataMap[$config['id']] ?? null;
    $specificData = $specificDataMap[$config['id']] ?? null;
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($config['monitoring_name']) . "</strong><br>";
    echo "<small>" . htmlspecialchars($config['monitoring_description']) . "</small></td>";
    
    // Latest data info
    if ($latestData) {
        echo "<td style='text-align: center;'>YA</td>";
        echo "<td style='text-align: center;'>" . $latestData['year'] . " Q" . $latestData['quarter'] . "</td>";
        echo "<td style='text-align: center;'>" . $latestData['current_value'] . "</td>";
    } else {
        echo "<td style='text-align: center; color: red;'>TIDAK</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
    }
    
    // 2025 Q1 data info
    if ($specificData) {
        echo "<td style='text-align: center;'>YA</td>";
        echo "<td style='text-align: center;'>" . $specificData['current_value'] . "</td>";
    } else {
        echo "<td style='text-align: center; color: red;'>TIDAK</td>";
        echo "<td style='text-align: center; color: red;'>-</td>";
    }
    
    // Difference
    if ($latestData && $specificData) {
        $diff = $latestData['current_value'] - $specificData['current_value'];
        if ($diff != 0) {
            if ($diff > 0) {
                echo "<td style='text-align: center; color: green;'>+" . $diff . "</td>";
            } else {
                echo "<td style='text-align: center; color: red;'>" . $diff . "</td>";
            }
        } else {
            echo "<td style='text-align: center; color: #666;'>0</td>";
        }
    } elseif ($latestData) {
        echo "<td style='text-align: center; color: blue;'>Hanya ada data terbaru</td>";
    } elseif ($specificData) {
        echo "<td style='text-align: center; color: blue;'>Hanya ada data 2025 Q1</td>";
    } else {
        echo "<td style='text-align: center; color: red;'>-</td>";
    }
    
    echo "</tr>\n";
}

echo "</table>\n";

// Summary
$latestTotal = array_sum(array_column($latestDataMap, 'current_value'));
$specificTotal = array_sum(array_column($specificDataMap, 'current_value'));

echo "<br><h3>Perhitungan 'Total Nilai Saat Ini':</h3>\n";
echo "<div style='display: flex; gap: 30px;'>";
echo "<div style='flex: 1; padding: 15px; background: #e8f5e9; border-radius: 8px;'>";
echo "<h4>Filter 'Terbaru':</h4>";
echo "<p><strong>Total: {$latestTotal}</strong></p>";
echo "<p>Dihitung dari:</p>";
echo "<ul>";
foreach ($latestDataMap as $id => $data) {
    $config = array_filter($monitoringConfigs, function($c) use ($id) { return $c['id'] == $id; });
    $config = array_values($config)[0];
    echo "<li>" . $config['monitoring_name'] . ": " . $data['current_value'] . " (dari " . $data['year'] . " Q" . $data['quarter'] . ")</li>";
}
echo "</ul>";
echo "</div>";

echo "<div style='flex: 1; padding: 15px; background: #fff3e0; border-radius: 8px;'>";
echo "<h4>Filter '2025 Q1':</h4>";
echo "<p><strong>Total: {$specificTotal}</strong></p>";
echo "<p>Dihitung dari:</p>";
echo "<ul>";
foreach ($specificDataMap as $id => $data) {
    $config = array_filter($monitoringConfigs, function($c) use ($id) { return $c['id'] == $id; });
    $config = array_values($config)[0];
    echo "<li>" . $config['monitoring_name'] . ": " . $data['current_value'] . " (dari " . $data['year'] . " Q" . $data['quarter'] . ")</li>";
}
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div style='margin-top: 20px; padding: 15px; background: #f3e5f5; border-radius: 8px;'>";
echo "<h4>Kesimpulan:</h4>";
echo "<p>Perbedaan nilai terjadi karena <strong>'Filter Terbaru'</strong> dan <strong>'Filter Tahun/Kuartal'</strong> ";
echo "mengambil data dari periode waktu yang berbeda. Keduanya benar, hanya saja ";
echo "merepresentasikan data pada waktu yang berbeda.</p>";
echo "</div>";

echo "<a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>← Kembali ke Dashboard</a>\n";
echo "<a href='compare-data.php' style='display: inline-block; padding: 12px 24px; background: #4caf50; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; margin-left: 10px;'>→ Lihat Perbandingan Lain</a>\n";
?>