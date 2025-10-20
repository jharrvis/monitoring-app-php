<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Fix Percentage Calculation</h2>";

// Get all monitoring data
$sql = "SELECT * FROM monitoring_data ORDER BY monitoring_id, year DESC, quarter DESC";
$allData = db()->fetchAll($sql);

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f3f4f6;'>
        <th>ID</th>
        <th>Monitoring</th>
        <th>Year</th>
        <th>Quarter</th>
        <th>Current</th>
        <th>Target</th>
        <th>DB %</th>
        <th>Calculated %</th>
        <th>Status</th>
      </tr>";

$updateCount = 0;

foreach ($allData as $data) {
    // Calculate correct percentage
    $calculatedPercentage = 0;
    if ($data['target_value'] > 0) {
        $calculatedPercentage = ($data['current_value'] / $data['target_value']) * 100;
    }

    $dbPercentage = (float)$data['percentage'];
    $diff = abs($calculatedPercentage - $dbPercentage);

    // If difference is more than 0.1%, mark for update
    $needsUpdate = $diff > 0.1;

    $rowStyle = $needsUpdate ? "style='background: #fee2e2;'" : "";

    echo "<tr {$rowStyle}>";
    echo "<td>{$data['id']}</td>";
    echo "<td>{$data['monitoring_id']}</td>";
    echo "<td>{$data['year']}</td>";
    echo "<td>Q{$data['quarter']}</td>";
    echo "<td>{$data['current_value']}</td>";
    echo "<td>{$data['target_value']}</td>";
    echo "<td><strong style='color: red;'>{$dbPercentage}%</strong></td>";
    echo "<td><strong style='color: green;'>" . round($calculatedPercentage, 2) . "%</strong></td>";

    if ($needsUpdate) {
        // Update the database
        $updateSql = "UPDATE monitoring_data SET percentage = ? WHERE id = ?";
        db()->execute($updateSql, [round($calculatedPercentage, 2), $data['id']]);
        echo "<td style='color: green; font-weight: bold;'>✅ UPDATED</td>";
        $updateCount++;
    } else {
        echo "<td>OK</td>";
    }

    echo "</tr>";
}

echo "</table>";

echo "<br><br>";
echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; border: 2px solid #10b981;'>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Total Records:</strong> " . count($allData) . "</p>";
echo "<p><strong>Updated:</strong> {$updateCount}</p>";
echo "<p><strong>Status:</strong> " . ($updateCount > 0 ? "✅ Percentage values have been corrected!" : "All percentages are correct.") . "</p>";
echo "</div>";

echo "<br>";
echo "<a href='index.php' style='display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>← Back to Dashboard</a>";
?>
