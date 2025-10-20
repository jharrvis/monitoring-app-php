<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require authentication with redirect to login page
auth()->requireAuth(true);

$user = auth()->getUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * {
            font-family: 'Inter', sans-serif;
        }

        .tab-button {
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6 !important;
            background: transparent;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sync-progress {
            transition: width 0.3s ease;
        }

        .data-tab-btn {
            transition: all 0.3s ease;
        }

        .data-tab-btn.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6 !important;
        }

        .data-tab-content {
            display: none;
        }

        .data-tab-content.active {
            display: flex;
            flex-direction: column;
        }

        .analytics-chart-btn.active {
            background: #3b82f6 !important;
            color: white !important;
            border-color: #3b82f6 !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-gradient-to-r from-slate-600 to-slate-700 shadow-lg">
        <div class="max-w-full mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center">
                        <i class="fas fa-gavel text-2xl text-slate-600"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white">Panel Administrator</h1>
                        <p class="text-sm text-slate-300">Kelola Data Monitoring Kinerja</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right mr-2">
                        <p class="text-sm font-semibold text-white">Admin: <span class="font-bold"><?= htmlspecialchars($user['full_name']) ?></span></p>
                    </div>
                    <a href="../index.php" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-chart-line"></i>
                        Dashboard
                        <i class="fas fa-external-link-alt text-xs"></i>
                    </a>
                    <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i>
                        Keluar
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-full mx-auto px-6 py-6">
        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="flex flex-wrap border-b border-gray-200">
                <button onclick="switchTab('kelola-sistem')" class="tab-button active px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-blue-500">
                    <i class="fas fa-layer-group mr-2"></i>Kelola Sistem
                </button>
                <button onclick="switchTab('input-data')" class="tab-button px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-blue-500">
                    <i class="fas fa-chart-bar mr-2"></i>Input Data
                </button>
                <button onclick="switchTab('pengaturan')" class="tab-button px-6 py-3 font-semibold text-gray-700 border-b-2 border-transparent hover:border-blue-500">
                    <i class="fas fa-cog mr-2"></i>Pengaturan
                </button>
            </div>
        </div>

        <!-- Tab Content: Kelola Sistem -->
        <div id="tab-kelola-sistem" class="tab-content active">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-900">Konfigurasi Card Monitoring</h2>
                </div>
                <div class="p-6">
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="relative">
                            <input
                                type="text"
                                id="searchConfig"
                                placeholder="Cari sistem monitoring..."
                                onkeyup="filterConfigs()"
                                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>

                    <div id="monitoringConfigsTable"></div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Input Data -->
        <div id="tab-input-data" class="tab-content">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-900">Input Data Triwulanan</h2>
                    <button type="button" onclick="showSyncOptions()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-sync"></i>
                        Sync Data
                    </button>
                </div>
                <div class="p-6">
                    <div id="monitoringDataTable"></div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Pengaturan -->
        <div id="tab-pengaturan" class="tab-content">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-900">Pengaturan Sistem</h2>
                    <button type="button" onclick="saveSettings()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        Simpan Pengaturan
                    </button>
                </div>
                <div class="p-6">
                    <form id="settingsForm" onsubmit="saveSettings(event)">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column: Informasi Aplikasi -->
                            <div>
                                <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-200">Informasi Aplikasi</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Aplikasi</label>
                                    <input type="text" id="settingAppName" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Instansi</label>
                                    <input type="text" id="settingInstitutionName" required
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Aplikasi</label>
                                    <textarea id="settingAppDescription" rows="4"
                                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>
                            </div>

                            <!-- Right Column: Pengaturan Sistem -->
                            <div>
                                <h3 class="text-base font-bold text-gray-900 mb-4 pb-2 border-b border-gray-200">Pengaturan Sistem</h3>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Interval Update (menit)</label>
                                    <input type="number" id="settingUpdateInterval" required min="1" max="60"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Durasi Slide Halaman (detik)</label>
                                    <input type="number" id="settingSlideDuration" required min="10" max="300"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Waktu dalam detik untuk perpindahan otomatis antar halaman</p>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Notifikasi</label>
                                    <input type="email" id="settingNotificationEmail"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="settingAutoUpdate" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-gray-700">Aktifkan update otomatis</span>
                                    </label>

                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="settingEmailNotification" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-gray-700">Kirim notifikasi email untuk status kritis</span>
                                    </label>

                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="settingAutoSlide" class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm text-gray-700">Aktifkan slide otomatis antar halaman</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Config Modal -->
    <div id="editConfigModal" class="modal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col" style="max-height: 85vh;">
            <div class="bg-white px-5 py-3 border-b border-gray-200 flex justify-between items-center rounded-t-2xl flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-900">Edit Konfigurasi Card</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form id="editConfigForm" onsubmit="saveConfig(event)" class="flex flex-col flex-1 overflow-hidden">
                <div class="overflow-y-auto px-5 py-4 flex-1">
                    <input type="hidden" id="editConfigId">

                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Sistem</label>
                        <input type="text" id="editMonitoringName" required
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deskripsi</label>
                        <textarea id="editMonitoringDescription" rows="2" required
                                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">API Endpoint <span class="text-gray-500 text-xs">(URL untuk sync data)</span></label>
                        <input type="url" id="editApiEndpoint"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">URL API untuk sinkronisasi data otomatis dari sistem eksternal</p>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Detail URL <span class="text-gray-500 text-xs">(Link untuk tombol Detail Data)</span></label>
                        <input type="url" id="editDetailUrl"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">URL yang akan dibuka saat tombol "Detail Data" diklik pada modal chart</p>
                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nilai Maksimal</label>
                            <input type="number" id="editMaxValue" step="0.01" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Unit</label>
                            <input type="text" id="editUnit" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Urutan <span class="text-gray-500 text-xs">(1-99)</span></label>
                            <input type="number" id="editDisplayOrder" min="1" max="99" required
                                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Icon</label>
                        <select id="editIcon" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-2xl">
                            <option value="⚖️">⚖️ Hukum</option>
                            <option value="📊">📊 Grafik</option>
                            <option value="💻">💻 Komputer</option>
                            <option value="📝">📝 Dokumen</option>
                            <option value="🏛️">🏛️ Gedung Pengadilan</option>
                            <option value="📋">📋 Clipboard</option>
                            <option value="🔍">🔍 Pencarian</option>
                            <option value="✅">✅ Checklist</option>
                            <option value="📈">📈 Trend Naik</option>
                            <option value="💰">💰 Uang</option>
                            <option value="🎯">🎯 Target</option>
                            <option value="📑">📑 Berkas</option>
                            <option value="🔔">🔔 Notifikasi</option>
                            <option value="📞">📞 Telepon</option>
                            <option value="🌐">🌐 Website</option>
                            <option value="🎓">🎓 Edukasi</option>
                            <option value="🏆">🏆 Prestasi</option>
                            <option value="💡">💡 Inovasi</option>
                            <option value="🤝">🤝 Mediasi</option>
                            <option value="📹">📹 CCTV</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-5 py-3 border-t border-gray-200 flex-shrink-0 bg-gray-50 rounded-b-2xl">
                    <button type="button" onclick="closeEditModal()"
                            class="px-5 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Data Modal -->
    <div id="manageDataModal" class="modal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl flex flex-col" style="max-height: 85vh;">
            <div class="bg-white px-5 py-3 border-b border-gray-200 flex justify-between items-center rounded-t-2xl flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-900" id="manageDataTitle">Manage Data</h3>
                <button type="button" onclick="closeManageDataModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-hidden flex flex-col">
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 px-5">
                    <button onclick="switchDataTab('triwulan')" class="data-tab-btn active px-4 py-2 text-sm font-semibold text-blue-600 border-b-2 border-blue-600">
                        Data Triwulan
                    </button>
                    <button onclick="switchDataTab('analytics')" class="data-tab-btn px-4 py-2 text-sm font-semibold text-gray-600 border-b-2 border-transparent">
                        Analytics
                    </button>
                </div>

                <!-- Tab Content: Data Triwulan -->
                <div id="data-tab-triwulan" class="data-tab-content active flex-1 overflow-y-auto px-5 py-4">
                    <input type="hidden" id="manageMonitoringId">

                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun</label>
                        <select id="manageYear" onchange="loadManageData()" class="w-48 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <?php for($y = getCurrentYear(); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == getCurrentYear() ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">TRIWULAN</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">NILAI</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">TARGET</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">PERSENTASE</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="manageDataTableBody">
                                <!-- Will be populated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm font-semibold text-gray-700 mb-3">Aksi Cepat</p>
                        <div class="flex gap-2">
                            <button onclick="showAddDataForm()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Tambah Data Triwulan
                            </button>
                            <button onclick="closeManageDataModal()" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Analytics -->
                <div id="data-tab-analytics" class="data-tab-content flex-1 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <!-- Left: Info Kinerja -->
                        <div>
                            <h3 class="text-base font-bold text-gray-800 mb-3">Informasi Kinerja</h3>
                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Nilai Saat Ini:</span>
                                    <strong id="analyticsCurrentValue" class="text-lg text-gray-900">-</strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Nilai Maksimal:</span>
                                    <strong id="analyticsTargetValue" class="text-lg text-gray-900">-</strong>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Persentase:</span>
                                    <strong id="analyticsPercentage" class="text-lg text-blue-600">-</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Status Sistem -->
                        <div>
                            <h3 class="text-base font-bold text-gray-800 mb-3">Status Sistem</h3>
                            <div class="space-y-2 bg-gray-50 p-3 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Status:</span>
                                    <span id="analyticsStatus" class="px-3 py-1 rounded-full text-xs font-bold">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Trend:</span>
                                    <span id="analyticsTrend" class="text-sm font-bold">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Last Update:</span>
                                    <strong id="analyticsLastUpdate" class="text-sm text-gray-900">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Section -->
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-base font-bold text-gray-800">Grafik Performa</h3>
                            <div class="flex gap-2">
                                <button onclick="loadAnalyticsChart('1year')" class="analytics-chart-btn active px-3 py-1 text-sm rounded-lg bg-blue-600 text-white">
                                    1 Tahun Terakhir
                                </button>
                                <button onclick="loadAnalyticsChart('all')" class="analytics-chart-btn px-3 py-1 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                    Keseluruhan
                                </button>
                                <a id="analyticsDetailLink" href="#" target="_blank" class="px-3 py-1 text-sm rounded-lg bg-green-600 text-white hover:bg-green-700 flex items-center gap-1" style="display: none;">
                                    <i class="fas fa-external-link-alt"></i> Detail
                                </a>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <canvas id="analyticsChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Data Form Modal -->
    <div id="dataFormModal" class="modal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="bg-white px-5 py-3 border-b border-gray-200 flex justify-between items-center rounded-t-2xl">
                <h3 class="text-lg font-bold text-gray-900" id="dataFormTitle">Tambah Data Triwulan</h3>
                <button type="button" onclick="closeDataFormModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form id="dataForm" onsubmit="saveData(event)" class="p-5">
                <input type="hidden" id="dataFormMonitoringId">
                <input type="hidden" id="dataFormDataId">

                <div class="mb-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tahun</label>
                    <input type="number" id="dataFormYear" required min="2020" max="2100"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Triwulan</label>
                    <select id="dataFormQuarter" required
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Triwulan --</option>
                        <option value="1">Triwulan 1 (Jan-Mar)</option>
                        <option value="2">Triwulan 2 (Apr-Jun)</option>
                        <option value="3">Triwulan 3 (Jul-Sep)</option>
                        <option value="4">Triwulan 4 (Okt-Des)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nilai Saat Ini</label>
                    <input type="number" id="dataFormCurrentValue" required step="0.01"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Target</label>
                    <input type="number" id="dataFormTargetValue" required step="0.01" disabled
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeDataFormModal()"
                            class="px-5 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sync Progress Modal -->
    <div id="syncModal" class="modal">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4" id="syncModalTitle">Sinkronisasi Data</h3>
            <div class="mb-4">
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div id="syncProgress" class="sync-progress bg-blue-500 h-4 rounded-full" style="width: 0%"></div>
                </div>
                <p class="text-sm text-gray-600 mt-2" id="syncStatus">Memulai sinkronisasi...</p>
            </div>
            <div id="syncResults" class="max-h-64 overflow-y-auto"></div>
            <div class="mt-4 text-right">
                <button onclick="closeSyncModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/admin-sync.js"></script>
</body>
</html>
