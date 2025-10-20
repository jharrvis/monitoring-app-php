<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/cache.php';
require_once __DIR__ . '/includes/helpers.php';

// Get app settings
$settings = getAppSettings();
$appName = $settings['app_name'] ?? APP_NAME;
$appDescription = $settings['app_description'] ?? 'Smart View Kinerja Satker PA Salatiga';

// Get monitoring configs
$sql = "SELECT * FROM monitoring_configs WHERE is_active = 1 ORDER BY page_number, display_order";
$monitoringConfigs = db()->fetchAll($sql);

// Get filter from URL or default to current year/quarter
// If no filter is provided, default to current year and quarter
if (!isset($_GET['year']) || !isset($_GET['quarter'])) {
    $filterYear = getCurrentYear();
    $filterQuarter = getCurrentQuarter();
} else {
    $filterYear = (int)$_GET['year'];
    $filterQuarter = (int)$_GET['quarter'];
}

$currentQuarter = getCurrentQuarter();
$currentYear = getCurrentYear();

// Get available years from database
$yearsQuery = "SELECT DISTINCT year FROM monitoring_data ORDER BY year DESC";
$availableYears = db()->fetchAll($yearsQuery);

// Get monitoring data
$dataMap = [];
$totalSystems = count($monitoringConfigs);
$goodCount = 0;
$warningCount = 0;
$criticalCount = 0;
$totalPercentage = 0;
$totalCurrentValues = 0;  // Added to calculate sum of current values
$displayYear = null;
$displayQuarter = null;

foreach ($monitoringConfigs as $config) {
    // If filter is set, use filter
    if ($filterYear && $filterQuarter) {
        $sql = "SELECT * FROM monitoring_data
                WHERE monitoring_id = ? AND year = ? AND quarter = ?
                ORDER BY created_at DESC LIMIT 1";
        $data = db()->fetchOne($sql, [$config['id'], $filterYear, $filterQuarter]);
    } else {
        // Get latest data (most recent quarter)
        $sql = "SELECT * FROM monitoring_data
                WHERE monitoring_id = ?
                ORDER BY year DESC, quarter DESC, created_at DESC LIMIT 1";
        $data = db()->fetchOne($sql, [$config['id']]);
    }

    // Track what year/quarter we're displaying
    if ($data && !$displayYear) {
        $displayYear = $data['year'];
        $displayQuarter = $data['quarter'];
    }

    if ($data) {
        // Use percentage from database directly
        $percentage = (float)$data['percentage'];

        // Determine status based on percentage
        if ($percentage >= 100) {
            $status = 'good';
        } elseif ($percentage >= 50) {
            $status = 'warning';
        } else {
            $status = 'critical';
        }

        $dataMap[$config['id']] = array_merge($data, [
            'percentage' => $percentage,
            'status' => $status
        ]);

        // Count status
        if ($status === 'good') $goodCount++;
        elseif ($status === 'warning') $warningCount++;
        else $criticalCount++;

        $totalPercentage += $percentage;
        $totalCurrentValues += (int)$data['current_value'];  // Add current_value to sum
    } else {
        $dataMap[$config['id']] = [
            'current_value' => 0,
            'target_value' => 0,
            'percentage' => 0,
            'status' => 'critical'
        ];
        $criticalCount++;
        $totalCurrentValues += 0;  // Add 0 to sum when no data
    }
}

$avgPercentage = $totalSystems > 0 ? round($totalPercentage / $totalSystems, 1) : 0;

// Group configs - 12 items per page based on display_order
$itemsPerPage = 12;
$allConfigs = $monitoringConfigs;
$totalPages = ceil(count($allConfigs) / $itemsPerPage);

$pages = [];
for ($i = 0; $i < $totalPages; $i++) {
    $pages[$i + 1] = array_slice($allConfigs, $i * $itemsPerPage, $itemsPerPage);
}

$currentPage = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> - Dashboard Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        body {
            background: #f5f7fa;
        }

        /* Dark Mode */
        body.dark-mode {
            background: #0f172a;
            color: #e2e8f0;
        }

        body.dark-mode .bg-white {
            background: #1e293b !important;
        }

        body.dark-mode .text-gray-900 {
            color: #e2e8f0 !important;
        }

        body.dark-mode .text-gray-600 {
            color: #94a3b8 !important;
        }

        body.dark-mode .text-gray-700 {
            color: #cbd5e1 !important;
        }

        body.dark-mode .text-gray-800 {
            color: #e2e8f0 !important;
        }

        body.dark-mode .border-gray-200 {
            border-color: #334155 !important;
        }

        body.dark-mode .border-gray-300 {
            border-color: #475569 !important;
        }

        body.dark-mode .bg-gray-50 {
            background: #0f172a !important;
        }

        body.dark-mode .bg-gray-100 {
            background: #1e293b !important;
        }

        body.dark-mode .bg-gray-200 {
            background: #334155 !important;
        }

        body.dark-mode .bg-blue-50 {
            background: #1e3a5f !important;
        }

        body.dark-mode .text-blue-800 {
            color: #93c5fd !important;
        }

        body.dark-mode .border-blue-500 {
            border-color: #3b82f6 !important;
        }

        /* Dark mode for filter selects */
        body.dark-mode select {
            background: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }

        body.dark-mode select option {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }

        /* Dark mode for labels */
        body.dark-mode label {
            color: #cbd5e1 !important;
        }

        /* Dark mode for stat card borders */
        body.dark-mode .stat-card {
            background: #1e293b !important;
            border-color: #334155 !important;
        }

        body.dark-mode .stat-card.active {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }

        /* Dark mode for icon containers */
        body.dark-mode .bg-blue-100 {
            background: #1e3a5f !important;
        }

        body.dark-mode .text-blue-600 {
            color: #60a5fa !important;
        }

        body.dark-mode .bg-green-100 {
            background: #064e3b !important;
        }

        body.dark-mode .text-green-600 {
            color: #34d399 !important;
        }

        body.dark-mode .bg-yellow-100 {
            background: #78350f !important;
        }

        body.dark-mode .text-yellow-600 {
            color: #fbbf24 !important;
        }

        body.dark-mode .bg-red-100 {
            background: #7f1d1d !important;
        }

        body.dark-mode .text-red-600 {
            color: #f87171 !important;
        }

        /* Compact Stats */
        .stat-card {
            border-radius: 12px;
            padding: 12px 16px;
            background: white;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .stat-card.active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .stat-card.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: #3b82f6;
            border-radius: 2px 2px 0 0;
        }

        /* Compact System Cards */
        .system-card,
        .system-card-filtered {
            border-radius: 10px;
            border: 2px solid;
            padding: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .system-card:hover,
        .system-card-filtered:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        /* Card backgrounds and borders based on status */
        .system-card.status-good,
        .system-card-filtered.status-good {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-color: #10b981;
        }

        .system-card.status-warning,
        .system-card-filtered.status-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-color: #f59e0b;
        }

        .system-card.status-critical,
        .system-card-filtered.status-critical {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-color: #ef4444;
        }

        /* Dark mode card backgrounds */
        body.dark-mode .system-card.status-good,
        body.dark-mode .system-card-filtered.status-good {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            border-color: #10b981;
        }

        body.dark-mode .system-card.status-warning,
        body.dark-mode .system-card-filtered.status-warning {
            background: linear-gradient(135deg, #78350f 0%, #92400e 100%);
            border-color: #f59e0b;
        }

        body.dark-mode .system-card.status-critical,
        body.dark-mode .system-card-filtered.status-critical {
            background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%);
            border-color: #ef4444;
        }

        .progress-bar-container {
            background: #e5e7eb;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }

        body.dark-mode .progress-bar-container {
            background: #334155;
        }

        .progress-bar {
            height: 100%;
            transition: width 1s ease-out;
            border-radius: 3px;
        }

        .progress-bar.good {
            background: #10b981;
        }

        .progress-bar.warning {
            background: #f59e0b;
        }

        .progress-bar.critical {
            background: #ef4444;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.good { background: #10b981; }
        .status-dot.warning { background: #f59e0b; }
        .status-dot.critical { background: #ef4444; }

        .page-indicator {
            display: inline-flex;
            gap: 6px;
            background: white;
            padding: 4px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }

        .page-indicator button {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .page-indicator button.active {
            background: #3b82f6;
            color: white;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .dark-mode-toggle:hover {
            background: #f3f4f6;
            border-color: #3b82f6;
            color: #3b82f6;
        }

        body.dark-mode .dark-mode-toggle {
            background: #1e293b;
            border-color: #475569;
            color: #fbbf24;
        }

        body.dark-mode .dark-mode-toggle:hover {
            background: #334155;
            border-color: #fbbf24;
        }

        body.dark-mode .page-indicator {
            background: #1e293b;
            border-color: #334155;
        }

        body.dark-mode .page-indicator button {
            background: #334155;
            color: #94a3b8;
        }

        body.dark-mode .page-indicator button.active {
            background: #3b82f6;
            color: white;
        }

        .slide-page {
            display: none;
            animation: fadeIn 0.4s ease-in;
        }

        .slide-page.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-container {
            cursor: pointer;
            position: relative;
        }

        .update-indicator {
            display: inline-block;
            text-align: right;
            background: #ecfdf5;
            color: #059669;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.4;
        }

        body.dark-mode .update-indicator {
            background: #064e3b;
            color: #34d399;
        }

        .pulse {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .external-link {
            color: #3b82f6;
            font-size: 12px;
            transition: color 0.2s;
        }

        .external-link:hover {
            color: #2563eb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.2s ease-in;
        }

        body.dark-mode .modal {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 90%;
            max-height: 85vh;
            overflow: hidden;
            animation: slideUp 0.3s ease-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-body {
            overflow-y: auto;
            max-height: calc(85vh - 0px);
            padding: 0;
        }

        /* Custom Scrollbar */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        body.dark-mode .modal-body::-webkit-scrollbar-track {
            background: #1e293b;
        }

        body.dark-mode .modal-body::-webkit-scrollbar-thumb {
            background: #475569;
        }

        body.dark-mode .modal-body::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Dark mode for modal content */
        body.dark-mode .modal-content {
            background: linear-gradient(to bottom, #1e293b 0%, #0f172a 100%) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            border: 1px solid #334155;
        }

        body.dark-mode .border-b {
            border-color: #334155 !important;
        }

        /* Info boxes styling */
        .space-y-2.bg-gray-50 {
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .space-y-2.bg-gray-50:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Dark mode for modal info boxes */
        body.dark-mode .space-y-2 {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid #334155 !important;
            backdrop-filter: blur(10px);
        }

        body.dark-mode .space-y-2:hover {
            background: rgba(15, 23, 42, 0.8) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        /* Dark mode for buttons in modal */
        body.dark-mode button.bg-gray-200 {
            background: #334155 !important;
            color: #cbd5e1 !important;
        }

        body.dark-mode button.bg-gray-200:hover {
            background: #475569 !important;
        }

        /* Dark mode for blue button */
        body.dark-mode button.bg-blue-500 {
            background: #2563eb !important;
        }

        body.dark-mode button.bg-blue-500:hover {
            background: #1d4ed8 !important;
        }

        /* Dark mode for green button */
        body.dark-mode .bg-green-500 {
            background: #059669 !important;
        }

        body.dark-mode .bg-green-500:hover {
            background: #047857 !important;
        }

        /* Dark mode for status badges */
        body.dark-mode .bg-green-100.text-green-800 {
            background: #065f46 !important;
            color: #d1fae5 !important;
        }

        body.dark-mode .bg-yellow-100.text-yellow-800 {
            background: #92400e !important;
            color: #fef3c7 !important;
        }

        body.dark-mode .bg-red-100.text-red-800 {
            background: #991b1b !important;
            color: #fecaca !important;
        }

        /* Dark mode for external link */
        body.dark-mode .external-link {
            color: #60a5fa !important;
        }

        body.dark-mode .external-link:hover {
            color: #93c5fd !important;
        }

        /* Dark mode for chart container */
        body.dark-mode .bg-gray-50.p-3 {
            background: #0f172a !important;
            border: 1px solid #334155;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }
        .trend-neutral { color: #6b7280; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-[1600px] mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="logo-container" onclick="handleLogoClick()">
                        <img src="https://pa-salatiga.go.id/wp-content/uploads/2024/11/logo-pa-salatiga.webp" alt="Logo PA Salatiga" class="h-12 w-12 rounded-full object-cover" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%2334a853%22/%3E%3Ctext x=%2250%22 y=%2265%22 font-size=%2245%22 font-weight=%22bold%22 text-anchor=%22middle%22 fill=%22white%22%3EPA%3C/text%3E%3C/svg%3E'">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($appName) ?></h1>
                        <p class="text-xs text-gray-600"><?= htmlspecialchars($appDescription) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Filter Tahun dan Quarter -->
                    <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-lg border-2 border-gray-200">
                        <label class="text-sm font-semibold text-gray-700">Tahun:</label>
                        <select id="filterYear" onchange="applyFilter()" class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($availableYears as $yearRow): ?>
                                <option value="<?= $yearRow['year'] ?>" <?= ($filterYear == $yearRow['year']) ? 'selected' : '' ?>>
                                    <?= $yearRow['year'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="text-sm font-semibold text-gray-700 ml-2">Triwulan:</label>
                        <select id="filterQuarter" onchange="applyFilter()" class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1" <?= ($filterQuarter == 1) ? 'selected' : '' ?>>I</option>
                            <option value="2" <?= ($filterQuarter == 2) ? 'selected' : '' ?>>II</option>
                            <option value="3" <?= ($filterQuarter == 3) ? 'selected' : '' ?>>III</option>
                            <option value="4" <?= ($filterQuarter == 4) ? 'selected' : '' ?>>IV</option>
                        </select>
                    </div>

                    <div class="page-indicator">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <button onclick="switchPage(<?= $i ?>)" class="page-btn <?= $i == 1 ? 'active' : '' ?>"><?= $i ?></button>
                        <?php endfor; ?>
                    </div>
                    <button onclick="toggleDarkMode()" class="dark-mode-toggle" title="Toggle Dark Mode">
                        <i class="fas fa-moon" id="darkModeIcon"></i>
                    </button>
                    <div class="update-indicator">
                        <span class="pulse"></span>Last Updated<br>
                        <strong id="lastUpdated" style="font-size: 13px;">--:--:--</strong>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-[1600px] mx-auto px-6 py-4">
        <!-- Period Info -->
        <?php if ($displayYear && $displayQuarter): ?>
            <div class="bg-blue-50 border-l-4 border-blue-500 px-4 py-3 mb-4 rounded">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Menampilkan data:</strong> Triwulan <?= toRoman($displayQuarter) ?> Tahun <?= $displayYear ?>
                    <?php if (!$filterYear && !$filterQuarter): ?>
                        <span class="ml-2 text-xs bg-blue-200 px-2 py-1 rounded">Data Terbaru</span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-4 gap-3 mb-4">
            <!-- Total Sistem -->
            <div class="stat-card active" onclick="filterByStatus('all')" id="statAll">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Total Sistem</p>
                        <p class="text-3xl font-bold text-gray-900"><?= $totalSystems ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cube text-blue-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Total Current Value -->
            <div class="stat-card" onclick="filterByStatus('good')" id="statGood">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Total Nilai Saat Ini</p>
                        <p class="text-3xl font-bold text-green-600"><?= $totalCurrentValues ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Peringatan -->
            <div class="stat-card" onclick="filterByStatus('warning')" id="statWarning">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Peringatan</p>
                        <p class="text-3xl font-bold text-yellow-600"><?= $warningCount ?></p>
                    </div>
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-lg"></i>
                    </div>
                </div>
            </div>

            <!-- Kritis -->
            <div class="stat-card" onclick="filterByStatus('critical')" id="statCritical">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-1">Kritis</p>
                        <p class="text-3xl font-bold text-red-600"><?= $criticalCount ?></p>
                    </div>
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pages Container -->
        <div id="pagesContainer">
            <?php foreach ($pages as $pageNum => $pageConfigs): ?>
                <!-- Page <?= $pageNum ?> -->
                <div class="slide-page <?= $pageNum === 1 ? 'active' : '' ?>" id="page<?= $pageNum ?>" data-page="<?= $pageNum ?>">
                    <div class="grid grid-cols-4 gap-3">
                        <?php foreach ($pageConfigs as $config): ?>
                            <?php $data = $dataMap[$config['id']]; ?>
                            <div class="system-card status-<?= $data['status'] ?>" data-status="<?= $data['status'] ?>" onclick="showDetail(<?= $config['id'] ?>)">
                                <!-- Header -->
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-1.5 flex-1">
                                        <span class="text-lg"><?= $config['icon'] ?></span>
                                        <span class="status-dot <?= $data['status'] ?>"></span>
                                        <span class="text-sm font-bold text-gray-900 leading-tight"><?= htmlspecialchars($config['monitoring_name']) ?></span>
                                    </div>
                                    <?php if ($config['detail_url']): ?>
                                        <a href="<?= $config['detail_url'] ?>" target="_blank" class="external-link" onclick="event.stopPropagation()">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Percentage -->
                                <div class="mb-1.5">
                                    <p class="text-2xl font-bold leading-none <?= $data['status'] === 'good' ? 'text-green-600' : ($data['status'] === 'warning' ? 'text-yellow-600' : 'text-red-600') ?>">
                                        <?= number_format($data['percentage'], 1) ?>%
                                        <span class="text-xs text-gray-600 font-normal ml-1">(<?= $data['current_value'] ?>/<?= $data['target_value'] ?>)</span>
                                    </p>
                                </div>

                                <!-- Description -->
                                <p class="text-xs text-gray-700 mb-2 leading-snug line-clamp-2" style="min-height: 28px;"><?= htmlspecialchars($config['monitoring_description']) ?></p>

                                <!-- Progress Bar -->
                                <div class="progress-bar-container">
                                    <div class="progress-bar <?= $data['status'] ?>" style="width: 0%" data-width="<?= min($data['percentage'], 100) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Filtered View (Hidden by default) -->
        <div id="filteredContainer" style="display: none;">
            <div class="grid grid-cols-4 gap-3">
                <?php foreach ($allConfigs as $config): ?>
                    <?php $data = $dataMap[$config['id']]; ?>
                    <div class="system-card-filtered status-<?= $data['status'] ?>" data-status="<?= $data['status'] ?>" onclick="showDetail(<?= $config['id'] ?>)" style="display: none;">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-1.5 flex-1">
                                <span class="text-lg"><?= $config['icon'] ?></span>
                                <span class="status-dot <?= $data['status'] ?>"></span>
                                <span class="text-sm font-bold text-gray-900 leading-tight"><?= htmlspecialchars($config['monitoring_name']) ?></span>
                            </div>
                            <?php if ($config['detail_url']): ?>
                                <a href="<?= $config['detail_url'] ?>" target="_blank" class="external-link" onclick="event.stopPropagation()">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Percentage -->
                        <div class="mb-1.5">
                            <p class="text-2xl font-bold leading-none <?= $data['status'] === 'good' ? 'text-green-600' : ($data['status'] === 'warning' ? 'text-yellow-600' : 'text-red-600') ?>">
                                <?= number_format($data['percentage'], 1) ?>%
                                <span class="text-xs text-gray-600 font-normal ml-1">(<?= $data['current_value'] ?>/<?= $data['target_value'] ?>)</span>
                            </p>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-gray-700 mb-2 leading-snug line-clamp-2" style="min-height: 28px;"><?= htmlspecialchars($config['monitoring_description']) ?></p>

                        <!-- Progress Bar -->
                        <div class="progress-bar-container">
                            <div class="progress-bar <?= $data['status'] ?>" style="width: 0%" data-width="<?= min($data['percentage'], 100) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <!-- Header (Fixed) -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <span id="modalIcon" class="text-3xl"></span>
                    <h2 id="modalTitle" class="text-xl font-bold text-gray-900"></h2>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body (Scrollable) -->
            <div class="modal-body">
                <div class="p-5">
                    <!-- Content -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Left: Info -->
                        <div>
                            <h3 class="text-base font-bold text-gray-800 mb-3">Informasi Kinerja</h3>
                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Nilai Saat Ini:</span>
                                    <strong id="modalCurrentValue" class="text-lg text-gray-900">-</strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Nilai Maksimal:</span>
                                    <strong id="modalTargetValue" class="text-lg text-gray-900">-</strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Persentase:</span>
                                    <strong id="modalPercentage" class="text-lg text-blue-600">-</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Status -->
                        <div>
                            <h3 class="text-base font-bold text-gray-800 mb-3">Status Sistem</h3>
                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Status:</span>
                                    <span id="modalStatus" class="px-3 py-1 rounded-full text-xs font-bold">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Trend:</span>
                                    <span id="modalTrend" class="text-sm font-bold">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Last Update:</span>
                                    <strong id="modalLastUpdate" class="text-sm text-gray-900">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-bold text-gray-800">Grafik Performa</h3>
                            <div class="flex gap-2">
                                <button id="chartBtn1Year" onclick="loadChartData(1)" class="px-3 py-1.5 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-semibold">
                                    1 Tahun Terakhir
                                </button>
                                <button id="chartBtnAll" onclick="loadChartData('all')" class="px-3 py-1.5 text-xs bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold">
                                    Keseluruhan
                                </button>
                                <a id="modalDetailLink" href="#" target="_blank" class="px-3 py-1.5 text-xs bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-semibold flex items-center gap-1">
                                    <i class="fas fa-external-link-alt"></i>
                                    Detail
                                </a>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg" style="height: 280px;">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dark Mode Toggle
        function toggleDarkMode() {
            const body = document.body;
            const icon = document.getElementById('darkModeIcon');

            body.classList.toggle('dark-mode');

            if (body.classList.contains('dark-mode')) {
                icon.className = 'fas fa-sun';
                localStorage.setItem('darkMode', 'enabled');
            } else {
                icon.className = 'fas fa-moon';
                localStorage.setItem('darkMode', 'disabled');
            }
        }

        // Load dark mode preference
        window.addEventListener('DOMContentLoaded', () => {
            const darkMode = localStorage.getItem('darkMode');
            const icon = document.getElementById('darkModeIcon');

            if (darkMode === 'enabled') {
                document.body.classList.add('dark-mode');
                icon.className = 'fas fa-sun';
            }
        });

        // Filter by status
        let currentFilter = 'all';

        function filterByStatus(status) {
            currentFilter = status;

            // Update active stat card
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });

            if (status === 'all') {
                document.getElementById('statAll').classList.add('active');
            } else if (status === 'good') {
                document.getElementById('statGood').classList.add('active');
            } else if (status === 'warning') {
                document.getElementById('statWarning').classList.add('active');
            } else if (status === 'critical') {
                document.getElementById('statCritical').classList.add('active');
            }

            // Toggle between paged view and filtered view
            const pagesContainer = document.getElementById('pagesContainer');
            const filteredContainer = document.getElementById('filteredContainer');
            const pageIndicator = document.querySelector('.page-indicator');

            if (status === 'all') {
                // Show paged view, hide filtered view
                pagesContainer.style.display = 'block';
                filteredContainer.style.display = 'none';
                if (pageIndicator) pageIndicator.style.display = 'flex';

                // Re-enable auto-slide
                startAutoSlide();
            } else {
                // Hide paged view, show filtered view
                pagesContainer.style.display = 'none';
                filteredContainer.style.display = 'block';
                if (pageIndicator) pageIndicator.style.display = 'none';

                // Stop auto-slide
                stopAutoSlide();

                // Show only matching status cards in filtered view
                document.querySelectorAll('.system-card-filtered').forEach(card => {
                    if (card.getAttribute('data-status') === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
        }

        // Apply Filter
        function applyFilter() {
            const year = document.getElementById('filterYear').value;
            const quarter = document.getElementById('filterQuarter').value;

            let url = window.location.pathname;
            const params = new URLSearchParams();

            // Always include both year and quarter
            params.append('year', year);
            params.append('quarter', quarter);

            url += '?' + params.toString();

            window.location.href = url;
        }

        // Update time
        function updateTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('lastUpdated').textContent = `${hours}.${minutes}.${seconds}`;
        }

        updateTime();
        setInterval(updateTime, 1000);

        // Animate progress bars
        setTimeout(() => {
            document.querySelectorAll('.progress-bar').forEach(bar => {
                bar.style.width = bar.dataset.width;
            });
        }, 300);

        // Switch page
        function switchPage(page) {
            document.querySelectorAll('.slide-page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));

            document.getElementById('page' + page).classList.add('active');
            document.querySelectorAll('.page-btn')[page - 1].classList.add('active');
        }

        // Auto slide
        let currentPage = 1;
        let autoSlideInterval = null;

        function startAutoSlide() {
            // Clear any existing interval
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
            }

            // Start new interval
            autoSlideInterval = setInterval(() => {
                const totalPages = document.querySelectorAll('.slide-page').length;
                currentPage = currentPage >= totalPages ? 1 : currentPage + 1;
                switchPage(currentPage);
            }, 10000); // 10 seconds
        }

        function stopAutoSlide() {
            if (autoSlideInterval) {
                clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
        }

        // Start auto-slide on page load
        startAutoSlide();

        // Logo click (5x to admin)
        let logoClicks = 0;
        let logoTimer = null;

        function handleLogoClick() {
            logoClicks++;
            if (logoTimer) clearTimeout(logoTimer);

            if (logoClicks >= 5) {
                window.location.href = 'admin/login.php';
            }

            logoTimer = setTimeout(() => {
                logoClicks = 0;
            }, 2000);
        }

        // Global variables
        let currentMonitoringId = null;
        let performanceChart = null;

        // Show detail modal
        async function showDetail(id) {
            currentMonitoringId = id;

            try {
                // Get current filter
                const filterYear = new URLSearchParams(window.location.search).get('year');
                const filterQuarter = new URLSearchParams(window.location.search).get('quarter');

                // Fetch monitoring config
                const configResponse = await fetch(`api/monitoring-configs/index.php?id=${id}`);
                const configResult = await configResponse.json();
                const config = configResult.data;

                // Fetch all data for this monitoring
                const response = await fetch(`api/monitoring-data/index.php?monitoring_id=${id}`);
                const result = await response.json();

                if (!result.data || result.data.length === 0) {
                    alert('Data tidak ditemukan');
                    return;
                }

                // Find data matching current filter or get latest
                let currentData;
                if (filterYear && filterQuarter) {
                    currentData = result.data.find(d => d.year == filterYear && d.quarter == filterQuarter);
                    if (!currentData) {
                        alert('Data untuk periode yang dipilih tidak ditemukan');
                        return;
                    }
                } else {
                    // Get latest data
                    currentData = result.data[0];
                }

                // Calculate trend (compare with previous period)
                let trendText = 'Stabil';
                let trendClass = 'trend-neutral';
                if (result.data.length > 1) {
                    // Find previous data
                    const currentIndex = result.data.findIndex(d =>
                        d.year == currentData.year && d.quarter == currentData.quarter
                    );

                    if (currentIndex >= 0 && currentIndex < result.data.length - 1) {
                        const previous = result.data[currentIndex + 1];
                        const diff = parseFloat(currentData.percentage) - parseFloat(previous.percentage);
                        if (diff > 0) {
                            trendText = 'Naik ▲';
                            trendClass = 'trend-up';
                        } else if (diff < 0) {
                            trendText = 'Turun ▼';
                            trendClass = 'trend-down';
                        }
                    }
                }

                // Determine status based on percentage
                const percentage = parseFloat(currentData.percentage);
                let statusBadge = '';
                if (percentage >= 100) {
                    statusBadge = '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-bold">Good</span>';
                } else if (percentage >= 50) {
                    statusBadge = '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-bold">Peringatan</span>';
                } else {
                    statusBadge = '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-bold">Kritis</span>';
                }

                // Fill modal data
                document.getElementById('modalIcon').textContent = config.icon || '📊';
                document.getElementById('modalTitle').textContent = config.monitoring_name;
                document.getElementById('modalCurrentValue').textContent = currentData.current_value;
                document.getElementById('modalTargetValue').textContent = currentData.target_value;
                document.getElementById('modalPercentage').textContent = `${parseFloat(currentData.percentage).toFixed(2)}%`;
                document.getElementById('modalStatus').innerHTML = statusBadge;
                document.getElementById('modalTrend').innerHTML = `<span class="${trendClass}">${trendText}</span>`;
                document.getElementById('modalLastUpdate').textContent = formatDateTime(currentData.updated_at || currentData.created_at);

                // Set detail link
                const detailLink = document.getElementById('modalDetailLink');
                if (config.detail_url) {
                    detailLink.href = config.detail_url;
                    detailLink.style.display = 'flex';
                } else {
                    detailLink.style.display = 'none';
                }

                // Show modal
                document.getElementById('detailModal').classList.add('show');

                // Load chart
                loadChartData(1);

            } catch (error) {
                console.error('Error loading detail:', error);
                alert('Gagal memuat detail data');
            }
        }

        // Close modal
        function closeModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('detailModal').classList.remove('show');
            if (performanceChart) {
                performanceChart.destroy();
                performanceChart = null;
            }
        }

        // Load chart data
        async function loadChartData(years) {
            try {
                // Update button states
                const btn1Year = document.getElementById('chartBtn1Year');
                const btnAll = document.getElementById('chartBtnAll');

                if (years === 1) {
                    btn1Year.className = 'px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-semibold';
                    btnAll.className = 'px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold';
                } else {
                    btn1Year.className = 'px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-semibold';
                    btnAll.className = 'px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-semibold';
                }

                const response = await fetch(`api/monitoring-data/index.php?monitoring_id=${currentMonitoringId}`);
                const result = await response.json();

                if (!result.data || result.data.length === 0) {
                    console.log('No data available for chart');
                    return;
                }

                // Sort data by year and quarter (oldest first)
                let chartData = [...result.data].sort((a, b) => {
                    if (a.year !== b.year) return a.year - b.year;
                    return a.quarter - b.quarter;
                });

                // Filter by years
                if (years === 1) {
                    const currentYear = new Date().getFullYear();
                    chartData = chartData.filter(d => d.year >= currentYear - 1);
                }

                console.log('Chart data:', chartData);

                // Prepare chart data
                const labels = chartData.map(d => `${d.year} Q${d.quarter}`);
                const percentages = chartData.map(d => parseFloat(d.percentage));

                // Destroy previous chart
                if (performanceChart) {
                    performanceChart.destroy();
                }

                // Create new chart
                const ctx = document.getElementById('performanceChart').getContext('2d');
                performanceChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Persentase Capaian (%)',
                            data: percentages,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7,
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                },
                                grid: {
                                    color: '#e5e7eb'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Error loading chart:', error);
            }
        }

        // Format date time
        function formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            const seconds = String(date.getSeconds()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
