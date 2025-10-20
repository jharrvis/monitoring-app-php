<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Check SIPP Data (ID 45)</h2>";

// Get config
$sql = "SELECT * FROM monitoring_configs WHERE id = 45";
$config = db()->fetchOne($sql);

echo "<h3>Config:</h3>";
echo "<pre>";
print_r($config);
echo "</pre>";

// Get recent data
$sql = "SELECT * FROM monitoring_data WHERE monitoring_id = 45 ORDER BY year DESC, quarter DESC LIMIT 5";
$data = db()->fetchAll($sql);

echo "<h3>Recent Data:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f3f4f6;'>
        <th>Year</th>
        <th>Quarter</th>
        <th>Current</th>
        <th>Target</th>
        <th>DB %</th>
        <th>Calculated %</th>
        <th>Max Value (config)</th>
        <th>Capped %</th>
      </tr>";

foreach ($data as $d) {
    $calculated = ($d['target_value'] > 0) ? ($d['current_value'] / $d['target_value']) * 100 : 0;
    $capped = min($calculated, $config['max_value']);

    echo "<tr>";
    echo "<td>{$d['year']}</td>";
    echo "<td>Q{$d['quarter']}</td>";
    echo "<td>{$d['current_value']}</td>";
    echo "<td>{$d['target_value']}</td>";
    echo "<td><strong style='color: red;'>{$d['percentage']}%</strong></td>";
    echo "<td><strong style='color: green;'>" . round($calculated, 2) . "%</strong></td>";
    echo "<td>{$config['max_value']}</td>";
    echo "<td><strong style='color: blue;'>" . round($capped, 2) . "%</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><h3>Analysis:</h3>";
echo "<p>Current Value: {$data[0]['current_value']}</p>";
echo "<p>Target Value: {$data[0]['target_value']}</p>";
echo "<p>Correct Percentage: " . round(($data[0]['current_value'] / $data[0]['target_value']) * 100, 2) . "%</p>";
echo "<p>DB Percentage: {$data[0]['percentage']}%</p>";
echo "<p>Max Value: {$config['max_value']}</p>";

if ($data[0]['percentage'] == $config['max_value']) {
    echo "<p style='background: #fee2e2; padding: 15px; border-radius: 8px; border: 2px solid #ef4444;'>";
    echo "<strong>⚠️ MASALAH DITEMUKAN!</strong><br>";
    echo "Persentase di database ({$data[0]['percentage']}%) sama dengan max_value ({$config['max_value']}).<br>";
    echo "Kemungkinan perhitungan persentase salah atau max_value terlalu rendah.<br><br>";
    echo "<strong>Solusi:</strong><br>";
    echo "1. Max value seharusnya 100 (atau 12 untuk sistem berbasis poin)<br>";
    echo "2. Persentase seharusnya: (" . $data[0]['current_value'] . " / " . $data[0]['target_value'] . ") * 100 = " . round(($data[0]['current_value'] / $data[0]['target_value']) * 100, 2) . "%";
    echo "</p>";
}
?>
