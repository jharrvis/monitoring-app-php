// Admin Panel JavaScript
let currentChart = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMonitoringConfigs();
    loadMonitoringData();
    loadChartOptions();
    loadSettings();
});

// Tab switching
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');

    // Update button styles
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('button').classList.add('active');

    // Load data for specific tabs
    if (tabName === 'input-data') {
        loadMonitoringData();
    } else if (tabName === 'grafik') {
        loadChartOptions();
    }
}

// Load monitoring configs
async function loadMonitoringConfigs() {
    try {
        const response = await fetch('../api/monitoring-configs/index.php');
        const result = await response.json();

        const table = document.getElementById('monitoringConfigsTable');
        if (!result.data || result.data.length === 0) {
            table.innerHTML = '<p class="text-gray-500 text-center py-8">Belum ada konfigurasi sistem</p>';
            return;
        }

        let html = `
            <div class="overflow-x-auto">
                <table class="min-w-full" id="configsTable">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SISTEM</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">DESKRIPSI</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">NILAI<br>MAKSIMAL</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">UNIT</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">URUTAN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ICON</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
        `;

        result.data.forEach((config, index) => {
            const rowBg = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            const searchText = `${config.monitoring_key || ''} ${config.monitoring_name || ''} ${config.monitoring_description || ''}`.toLowerCase();

            html += `
                <tr class="${rowBg} hover:bg-gray-100 transition config-row" data-search="${searchText}">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">${config.monitoring_name || config.monitoring_key}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${config.monitoring_description || config.monitoring_name}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${config.max_value || '100.00'}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${config.unit || 'poin'}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${config.display_order || config.page_number}</td>
                    <td class="px-4 py-3 text-2xl">${config.icon}</td>
                    <td class="px-4 py-3 text-sm">
                        <button onclick="editMonitoringConfig(${config.id})" class="text-blue-600 hover:text-blue-800" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        table.innerHTML = html;

    } catch (error) {
        console.error('Failed to load configs:', error);
    }
}

// Load monitoring data - show configs with latest data
async function loadMonitoringData() {
    try {
        // Get all configs
        const configResponse = await fetch('../api/monitoring-configs/index.php');
        const configResult = await configResponse.json();

        if (!configResult.data || configResult.data.length === 0) {
            document.getElementById('monitoringDataTable').innerHTML = '<p class="text-gray-500 text-center py-8">Belum ada konfigurasi sistem</p>';
            return;
        }

        // Get current year/quarter
        const currentYear = new Date().getFullYear();
        const currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);

        // Get latest data for all configs
        const dataResponse = await fetch(`../api/monitoring-data/index.php?year=${currentYear}&quarter=${currentQuarter}`);
        const dataResult = await dataResponse.json();

        // Create data map by monitoring_id
        const dataMap = {};
        if (dataResult.data) {
            dataResult.data.forEach(d => {
                dataMap[d.monitoring_id] = d;
            });
        }

        const table = document.getElementById('monitoringDataTable');

        let html = `
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SISTEM</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">NILAI</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">PERSENTASE</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
        `;

        configResult.data.forEach((config, index) => {
            const rowBg = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            const data = dataMap[config.id];

            const nilai = data ? `${data.current_value} ${config.unit}` : '-';
            const persentase = data ? `${data.percentage}%` : '-';

            html += `
                <tr class="${rowBg} hover:bg-gray-100 transition">
                    <td class="px-4 py-3 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">${config.icon}</span>
                            <span class="font-medium text-gray-900">${config.monitoring_name}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">${nilai}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">${persentase}</td>
                    <td class="px-4 py-3 text-sm">
                        <button onclick="openManageDataModal(${config.id}, '${config.monitoring_name}')" class="text-green-600 hover:text-green-800 font-medium">
                            <i class="fas fa-chart-line mr-1"></i> Manage
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        table.innerHTML = html;

    } catch (error) {
        console.error('Failed to load data:', error);
    }
}

// Load chart options
async function loadChartOptions() {
    try {
        const response = await fetch('../api/monitoring-configs/index.php?is_active=1');
        const result = await response.json();

        const select = document.getElementById('chartMonitoringId');
        select.innerHTML = '<option value="">-- Pilih Sistem --</option>';

        result.data.forEach(config => {
            select.innerHTML += `<option value="${config.id}">${config.monitoring_name}</option>`;
        });

    } catch (error) {
        console.error('Failed to load chart options:', error);
    }
}

// Load chart
async function loadChart() {
    const monitoringId = document.getElementById('chartMonitoringId').value;

    if (!monitoringId) {
        if (currentChart) {
            currentChart.destroy();
            currentChart = null;
        }
        return;
    }

    try {
        const response = await fetch(`../api/monitoring-data/index.php?monitoring_id=${monitoringId}`);
        const result = await response.json();

        if (result.data && result.data.length > 0) {
            const data = result.data.slice(0, 12).reverse(); // Last 12 quarters

            const labels = data.map(d => `${d.year} Q${d.quarter}`);
            const percentages = data.map(d => parseFloat(d.percentage));

            const ctx = document.getElementById('performanceChart').getContext('2d');

            if (currentChart) {
                currentChart.destroy();
            }

            currentChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Persentase (%)',
                        data: percentages,
                        backgroundColor: percentages.map(p => {
                            if (p >= 100) return 'rgba(16, 185, 129, 0.6)';
                            if (p >= 50) return 'rgba(245, 158, 11, 0.6)';
                            return 'rgba(239, 68, 68, 0.6)';
                        }),
                        borderColor: percentages.map(p => {
                            if (p >= 100) return 'rgb(16, 185, 129)';
                            if (p >= 50) return 'rgb(245, 158, 11)';
                            return 'rgb(239, 68, 68)';
                        }),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

    } catch (error) {
        console.error('Failed to load chart:', error);
    }
}

// Load settings
async function loadSettings() {
    try {
        const response = await fetch('../api/settings/index.php');

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Settings API raw response:', text);

        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }

        const result = JSON.parse(text);
        console.log('Settings API parsed:', result);

        if (result.data) {
            const data = result.data;

            // Fill form fields
            if (data.app_name) document.getElementById('settingAppName').value = data.app_name;
            if (data.institution_name) document.getElementById('settingInstitutionName').value = data.institution_name;
            if (data.app_description) document.getElementById('settingAppDescription').value = data.app_description;
            if (data.update_interval) document.getElementById('settingUpdateInterval').value = data.update_interval;
            if (data.notification_email) document.getElementById('settingNotificationEmail').value = data.notification_email;
            if (data.slide_duration) document.getElementById('settingSlideDuration').value = data.slide_duration;

            // Set checkboxes
            if (data.auto_update !== undefined) document.getElementById('settingAutoUpdate').checked = data.auto_update;
            if (data.email_notification !== undefined) document.getElementById('settingEmailNotification').checked = data.email_notification;
            if (data.auto_slide !== undefined) document.getElementById('settingAutoSlide').checked = data.auto_slide;
        }

    } catch (error) {
        console.error('Failed to load settings:', error);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Memuat Pengaturan',
            text: error.message
        });
    }
}

// Save settings
async function saveSettings(event) {
    if (event) event.preventDefault();

    const settings = {
        app_name: document.getElementById('settingAppName').value,
        institution_name: document.getElementById('settingInstitutionName').value,
        app_description: document.getElementById('settingAppDescription').value,
        update_interval: parseInt(document.getElementById('settingUpdateInterval').value),
        notification_email: document.getElementById('settingNotificationEmail').value,
        slide_duration: parseInt(document.getElementById('settingSlideDuration').value),
        auto_update: document.getElementById('settingAutoUpdate').checked,
        email_notification: document.getElementById('settingEmailNotification').checked,
        auto_slide: document.getElementById('settingAutoSlide').checked
    };

    try {
        const response = await fetch('../api/settings/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings)
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Pengaturan berhasil disimpan',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: result.message
            });
        }

    } catch (error) {
        console.error('Failed to save settings:', error);
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: 'Terjadi kesalahan saat menyimpan pengaturan'
        });
    }
}

// Sync data
async function syncData(type) {
    const modal = document.getElementById('syncModal');
    const modalTitle = document.getElementById('syncModalTitle');
    const progress = document.getElementById('syncProgress');
    const status = document.getElementById('syncStatus');
    const results = document.getElementById('syncResults');

    const colors = {
        sipp: 'blue',
        mediasi: 'green',
        banding: 'purple',
        ecourt: 'orange',
        'gugatan-mandiri': 'indigo'
    };

    modalTitle.textContent = `Sinkronisasi ${type.toUpperCase()}`;
    progress.className = `sync-progress bg-${colors[type]}-500 h-4 rounded-full`;
    progress.style.width = '0%';
    status.textContent = 'Memulai sinkronisasi...';
    results.innerHTML = '';

    modal.classList.add('show');

    try {
        const eventSource = new EventSource(`api/sync/index.php?type=${type}`);

        eventSource.onmessage = function(event) {
            const data = JSON.parse(event.data);

            if (data.progress !== undefined) {
                progress.style.width = data.progress + '%';
            }

            if (data.message) {
                status.textContent = data.message;
            }

            if (data.results) {
                let resultsHtml = '<div class="space-y-2 mt-4">';
                data.results.forEach(r => {
                    const icon = r.status === 'success' ? '✅' : '❌';
                    const color = r.status === 'success' ? 'text-green-700' : 'text-red-700';
                    resultsHtml += `
                        <div class="text-sm ${color}">
                            ${icon} Tahun ${r.year} Q${r.quarter}: ${r.message || (r.percentage !== undefined ? r.percentage + '%' : 'OK')}
                        </div>
                    `;
                });
                resultsHtml += '</div>';
                results.innerHTML = resultsHtml;
            }

            if (data.status === 'completed' || data.status === 'error') {
                eventSource.close();
                loadMonitoringData();
            }
        };

        eventSource.onerror = function() {
            eventSource.close();
            status.textContent = 'Terjadi kesalahan saat sinkronisasi';
        };

    } catch (error) {
        console.error('Sync error:', error);
        status.textContent = 'Gagal memulai sinkronisasi';
    }
}

function closeSyncModal() {
    document.getElementById('syncModal').classList.remove('show');
}

// Logout
async function logout() {
    if (confirm('Apakah Anda yakin ingin logout?')) {
        try {
            const response = await fetch('../api/auth/logout.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                window.location.href = 'login.php';
            }
        } catch (error) {
            console.error('Logout error:', error);
        }
    }
}

// Placeholder functions (to be implemented)
function addMonitoringConfig() {
    alert('Feature coming soon: Add Monitoring Config');
}

async function editMonitoringConfig(id) {
    try {
        // Fetch config data
        const response = await fetch(`../api/monitoring-configs/index.php?id=${id}`);
        const result = await response.json();

        if (!result.data) {
            Swal.fire({
                icon: 'error',
                title: 'Tidak Ditemukan',
                text: 'Konfigurasi tidak ditemukan'
            });
            return;
        }

        const config = result.data;

        // Fill form
        document.getElementById('editConfigId').value = config.id;
        document.getElementById('editMonitoringName').value = config.monitoring_name || '';
        document.getElementById('editMonitoringDescription').value = config.monitoring_description || '';
        document.getElementById('editApiEndpoint').value = config.api_endpoint || '';
        document.getElementById('editDetailUrl').value = config.detail_url || '';
        document.getElementById('editMaxValue').value = config.max_value || 100;
        document.getElementById('editUnit').value = config.unit || 'poin';
        document.getElementById('editDisplayOrder').value = config.display_order || 1;
        document.getElementById('editIcon').value = config.icon || '📊';

        // Show modal
        document.getElementById('editConfigModal').classList.add('show');
    } catch (error) {
        console.error('Failed to load config:', error);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Memuat Data',
            text: 'Gagal memuat data konfigurasi'
        });
    }
}

async function deleteMonitoringConfig(id) {
    const result = await Swal.fire({
        title: 'Hapus Konfigurasi?',
        text: 'Data monitoring dan riwayat terkait akan ikut terhapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`../api/monitoring-configs/index.php?id=${id}`, {
                method: 'DELETE'
            });
            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Terhapus!',
                    text: 'Konfigurasi berhasil dihapus',
                    timer: 2000,
                    showConfirmButton: false
                });
                loadMonitoringConfigs();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menghapus',
                    text: data.message || data.error
                });
            }
        } catch (error) {
            console.error('Failed to delete config:', error);
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: 'Gagal menghapus konfigurasi'
            });
        }
    }
}

function addMonitoringData() {
    alert('Feature coming soon: Add Monitoring Data');
}

function editMonitoringData(id) {
    alert('Feature coming soon: Edit Data ID ' + id);
}

function deleteMonitoringData(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        // Implement delete
        alert('Delete Data ID ' + id);
    }
}

function syncConfig(id) {
    alert('Feature coming soon: Sync Config ID ' + id);
}

function viewSettings(id) {
    alert('Feature coming soon: View Settings for Config ID ' + id);
}

// Close edit modal
function closeEditModal() {
    document.getElementById('editConfigModal').classList.remove('show');
}

// Save config
async function saveConfig(event) {
    event.preventDefault();

    const id = document.getElementById('editConfigId').value;
    const data = {
        id: parseInt(id),
        monitoring_name: document.getElementById('editMonitoringName').value.trim(),
        monitoring_description: document.getElementById('editMonitoringDescription').value.trim(),
        api_endpoint: document.getElementById('editApiEndpoint').value.trim() || null,
        detail_url: document.getElementById('editDetailUrl').value.trim() || null,
        max_value: parseFloat(document.getElementById('editMaxValue').value),
        unit: document.getElementById('editUnit').value.trim(),
        display_order: parseInt(document.getElementById('editDisplayOrder').value),
        icon: document.getElementById('editIcon').value
    };

    console.log('Saving config:', data);

    try {
        const response = await fetch('../api/monitoring-configs/index.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const text = await response.text();
        console.log('API Response:', text);

        const result = JSON.parse(text);

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Konfigurasi berhasil disimpan',
                timer: 2000,
                showConfirmButton: false
            });
            closeEditModal();
            loadMonitoringConfigs(); // Reload table
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: result.message || result.error
            });
        }
    } catch (error) {
        console.error('Failed to save config:', error);
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: 'Terjadi kesalahan saat menyimpan data'
        });
    }
}

// Filter configs table
function filterConfigs() {
    const searchInput = document.getElementById('searchConfig');
    const filter = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll('.config-row');

    let visibleCount = 0;

    rows.forEach(row => {
        const searchText = row.getAttribute('data-search');
        if (searchText.includes(filter)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show message if no results
    const table = document.getElementById('configsTable');
    let noResultMsg = document.getElementById('noResultMessage');

    if (visibleCount === 0 && filter !== '') {
        if (!noResultMsg) {
            noResultMsg = document.createElement('div');
            noResultMsg.id = 'noResultMessage';
            noResultMsg.className = 'text-center py-8 text-gray-500';
            noResultMsg.innerHTML = '<i class="fas fa-search text-3xl mb-2"></i><p>Tidak ada hasil yang ditemukan</p>';
            table.parentElement.appendChild(noResultMsg);
        }
        noResultMsg.style.display = 'block';
        table.style.display = 'none';
    } else {
        if (noResultMsg) {
            noResultMsg.style.display = 'none';
        }
        table.style.display = 'table';
    }
}

// ===== MANAGE DATA MODAL FUNCTIONS =====

// Open manage data modal
async function openManageDataModal(monitoringId, monitoringName) {
    document.getElementById('manageMonitoringId').value = monitoringId;
    document.getElementById('manageDataTitle').textContent = `Manage Data: ${monitoringName}`;
    document.getElementById('manageDataModal').classList.add('show');

    await loadManageData();
}

// Close manage data modal
function closeManageDataModal() {
    document.getElementById('manageDataModal').classList.remove('show');
}

// Switch data tab
function switchDataTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.data-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected tab
    document.getElementById('data-tab-' + tabName).classList.add('active');

    // Update button styles
    document.querySelectorAll('.data-tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.remove('text-blue-600', 'border-blue-600');
        btn.classList.add('text-gray-600', 'border-transparent');
    });
    event.target.classList.add('active', 'text-blue-600', 'border-blue-600');
    event.target.classList.remove('text-gray-600', 'border-transparent');

    // Load analytics if switched to analytics tab
    if (tabName === 'analytics') {
        loadAnalyticsData();
    }
}

// Load manage data
async function loadManageData() {
    const monitoringId = document.getElementById('manageMonitoringId').value;
    const year = document.getElementById('manageYear').value;

    try {
        const response = await fetch(`../api/monitoring-data/index.php?monitoring_id=${monitoringId}&year=${year}`);
        const result = await response.json();

        const tbody = document.getElementById('manageDataTableBody');

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data untuk tahun ini</td></tr>';
            return;
        }

        // Group by quarter
        const dataByQuarter = {1: null, 2: null, 3: null, 4: null};
        result.data.forEach(d => {
            dataByQuarter[d.quarter] = d;
        });

        let html = '';
        for (let q = 1; q <= 4; q++) {
            const data = dataByQuarter[q];
            const qLabel = `Triwulan ${q} (${getQuarterLabel(q)})`;

            if (data) {
                html += `
                    <tr class="${q % 2 === 0 ? 'bg-gray-50' : 'bg-white'} hover:bg-gray-100">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">${qLabel}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">${data.current_value} poin</td>
                        <td class="px-4 py-3 text-sm text-gray-700">${data.target_value} poin</td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">${data.percentage}%</td>
                        <td class="px-4 py-3 text-sm">
                            <button onclick="editData(${data.id})" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteData(${data.id})" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            } else {
                html += `
                    <tr class="${q % 2 === 0 ? 'bg-gray-50' : 'bg-white'}">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">${qLabel}</td>
                        <td colspan="4" class="px-4 py-3 text-sm text-gray-400">Belum ada data</td>
                    </tr>
                `;
            }
        }

        tbody.innerHTML = html;

    } catch (error) {
        console.error('Failed to load manage data:', error);
    }
}

function getQuarterLabel(q) {
    const labels = {1: 'Jan-Mar', 2: 'Apr-Jun', 3: 'Jul-Sep', 4: 'Okt-Des'};
    return labels[q] || '';
}

// Show add data form
async function showAddDataForm() {
    const monitoringId = document.getElementById('manageMonitoringId').value;

    // Fetch monitoring config to get max_value
    try {
        const response = await fetch(`../api/monitoring-configs/index.php?id=${monitoringId}`);
        const result = await response.json();

        if (!result.data) {
            Swal.fire({
                icon: 'error',
                title: 'Tidak Ditemukan',
                text: 'Konfigurasi monitoring tidak ditemukan'
            });
            return;
        }

        const maxValue = result.data.max_value || 100;

        document.getElementById('dataFormTitle').textContent = 'Tambah Data Triwulan';
        document.getElementById('dataFormDataId').value = '';
        document.getElementById('dataFormMonitoringId').value = monitoringId;
        document.getElementById('dataFormYear').value = document.getElementById('manageYear').value;
        document.getElementById('dataFormQuarter').value = '';
        document.getElementById('dataFormCurrentValue').value = '';
        document.getElementById('dataFormTargetValue').value = maxValue;

        document.getElementById('dataFormModal').classList.add('show');
    } catch (error) {
        console.error('Failed to load monitoring config:', error);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Memuat Data',
            text: 'Gagal memuat konfigurasi monitoring'
        });
    }
}

// Edit data
async function editData(dataId) {
    try {
        const response = await fetch(`../api/monitoring-data/index.php?id=${dataId}`);
        const result = await response.json();

        if (!result.data) {
            Swal.fire({
                icon: 'error',
                title: 'Tidak Ditemukan',
                text: 'Data tidak ditemukan'
            });
            return;
        }

        const data = result.data;

        // Fetch monitoring config to get max_value
        const configResponse = await fetch(`../api/monitoring-configs/index.php?id=${data.monitoring_id}`);
        const configResult = await configResponse.json();
        const maxValue = configResult.data ? (configResult.data.max_value || 100) : 100;

        document.getElementById('dataFormTitle').textContent = 'Edit Data Triwulan';
        document.getElementById('dataFormDataId').value = data.id;
        document.getElementById('dataFormMonitoringId').value = data.monitoring_id;
        document.getElementById('dataFormYear').value = data.year;
        document.getElementById('dataFormQuarter').value = data.quarter;
        document.getElementById('dataFormCurrentValue').value = data.current_value;
        document.getElementById('dataFormTargetValue').value = maxValue;

        document.getElementById('dataFormModal').classList.add('show');
    } catch (error) {
        console.error('Failed to load data:', error);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Memuat Data',
            text: 'Gagal memuat data'
        });
    }
}

// Close data form modal
function closeDataFormModal() {
    document.getElementById('dataFormModal').classList.remove('show');
}

// Save data
async function saveData(event) {
    event.preventDefault();

    const dataId = document.getElementById('dataFormDataId').value;
    const data = {
        monitoring_id: parseInt(document.getElementById('dataFormMonitoringId').value),
        year: parseInt(document.getElementById('dataFormYear').value),
        quarter: parseInt(document.getElementById('dataFormQuarter').value),
        current_value: parseFloat(document.getElementById('dataFormCurrentValue').value),
        target_value: parseFloat(document.getElementById('dataFormTargetValue').value)
    };

    if (dataId) {
        data.id = parseInt(dataId);
    }

    console.log('Saving data:', data);

    try {
        const url = '../api/monitoring-data/index.php';
        const method = dataId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const text = await response.text();
        console.log('API Response:', text);

        const result = JSON.parse(text);

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data berhasil disimpan',
                timer: 2000,
                showConfirmButton: false
            });
            closeDataFormModal();
            await loadManageData();
            await loadMonitoringData(); // Refresh main table
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: result.message || result.error
            });
        }
    } catch (error) {
        console.error('Failed to save data:', error);
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: 'Terjadi kesalahan saat menyimpan data'
        });
    }
}

// Delete data
async function deleteData(dataId) {
    const result = await Swal.fire({
        title: 'Hapus Data?',
        text: 'Data triwulan ini akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        const response = await fetch(`../api/monitoring-data/index.php?id=${dataId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: 'Data berhasil dihapus',
                timer: 2000,
                showConfirmButton: false
            });
            await loadManageData();
            await loadMonitoringData(); // Refresh main table
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menghapus',
                text: data.message || data.error
            });
        }
    } catch (error) {
        console.error('Failed to delete data:', error);
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan',
            text: 'Terjadi kesalahan saat menghapus data'
        });
    }
}

// ===== ANALYTICS TAB FUNCTIONS =====

let analyticsChartInstance = null;

// Load analytics data
async function loadAnalyticsData() {
    const monitoringId = document.getElementById('manageMonitoringId').value;

    try {
        // Get config info
        const configResponse = await fetch(`../api/monitoring-configs/index.php?id=${monitoringId}`);
        const configResult = await configResponse.json();
        const config = configResult.data;

        // Get current year/quarter
        const currentYear = new Date().getFullYear();
        const currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);

        // Get latest data
        const dataResponse = await fetch(`../api/monitoring-data/index.php?monitoring_id=${monitoringId}&year=${currentYear}&quarter=${currentQuarter}`);
        const dataResult = await dataResponse.json();

        const currentData = dataResult.data && dataResult.data.length > 0 ? dataResult.data[0] : null;

        // Update info boxes
        if (currentData) {
            document.getElementById('analyticsCurrentValue').textContent = `${currentData.current_value} ${config.unit}`;
            document.getElementById('analyticsTargetValue').textContent = `${currentData.target_value} ${config.unit}`;
            document.getElementById('analyticsPercentage').textContent = `${currentData.percentage}%`;

            // Status badge
            const statusEl = document.getElementById('analyticsStatus');
            const percentage = parseFloat(currentData.percentage);
            if (percentage >= 100) {
                statusEl.textContent = 'Persentase Hasil';
                statusEl.className = 'px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800';
            } else if (percentage >= 50) {
                statusEl.textContent = 'Peringatan';
                statusEl.className = 'px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800';
            } else {
                statusEl.textContent = 'Kritis';
                statusEl.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800';
            }

            // Trend (placeholder)
            document.getElementById('analyticsTrend').innerHTML = '<span class="text-red-500">Turun ▼</span>';

            // Last update
            const date = new Date(currentData.updated_at || currentData.created_at);
            document.getElementById('analyticsLastUpdate').textContent = date.toLocaleString('id-ID');
        } else {
            document.getElementById('analyticsCurrentValue').textContent = '-';
            document.getElementById('analyticsTargetValue').textContent = '-';
            document.getElementById('analyticsPercentage').textContent = '-';
            document.getElementById('analyticsStatus').textContent = '-';
            document.getElementById('analyticsTrend').textContent = '-';
            document.getElementById('analyticsLastUpdate').textContent = '-';
        }

        // Show detail link if exists
        if (config.detail_url) {
            document.getElementById('analyticsDetailLink').href = config.detail_url;
            document.getElementById('analyticsDetailLink').style.display = 'flex';
        } else {
            document.getElementById('analyticsDetailLink').style.display = 'none';
        }

        // Load chart
        await loadAnalyticsChart('1year');

    } catch (error) {
        console.error('Failed to load analytics:', error);
    }
}

// Load analytics chart
async function loadAnalyticsChart(period) {
    const monitoringId = document.getElementById('manageMonitoringId').value;

    // Update button styles
    document.querySelectorAll('.analytics-chart-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event?.target?.classList.add('active');

    try {
        const currentYear = new Date().getFullYear();
        let url = `../api/monitoring-data/index.php?monitoring_id=${monitoringId}`;

        if (period === '1year') {
            url += `&year=${currentYear}`;
        }
        // For 'all', don't add year filter to get all data

        const response = await fetch(url);
        const result = await response.json();

        if (result.data && result.data.length > 0) {
            const data = result.data.reverse(); // Oldest first

            const labels = data.map(d => `${d.year} Q${d.quarter}`);
            const values = data.map(d => parseFloat(d.percentage));

            // Destroy existing chart
            if (analyticsChartInstance) {
                analyticsChartInstance.destroy();
            }

            // Create new chart
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            analyticsChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Persentase Capaian (%)',
                        data: values,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
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
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Failed to load chart:', error);
    }
}
