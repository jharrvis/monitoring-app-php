// ===== SYNC LOGS FUNCTIONS =====

let logPaginationOffset = 0;
let logPaginationHasMore = false;

// Load sync logs
async function loadSyncLogs(offset = 0) {
    if (offset < 0) offset = 0;
    logPaginationOffset = offset;

    const statusFilter = document.getElementById('logFilterStatus')?.value || '';
    const typeFilter = document.getElementById('logFilterType')?.value || '';

    try {
        let url = `../api/sync-logs/index.php?limit=50&offset=${offset}`;
        if (statusFilter) url += `&status=${statusFilter}`;
        if (typeFilter) url += `&sync_type=${typeFilter}`;

        const response = await fetch(url);
        const result = await response.json();

        if (!result.data) {
            throw new Error('No data received');
        }

        renderSyncLogs(result.data);
        updateLogPagination(result.pagination);
    } catch (error) {
        console.error('Failed to load sync logs:', error);
        document.getElementById('syncLogsTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-red-600">
                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                    <p>Gagal memuat data log: ${error.message}</p>
                </td>
            </tr>
        `;
    }
}

// Render sync logs table
function renderSyncLogs(logs) {
    const tbody = document.getElementById('syncLogsTableBody');

    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2"></i>
                    <p>Tidak ada data log</p>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    logs.forEach(log => {
        const statusClass = getStatusClass(log.status);
        const statusIcon = getStatusIcon(log.status);
        const typeLabel = log.sync_type === 'manual' ? 'Manual' : 'Terjadwal';
        const typeBadge = log.sync_type === 'manual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';

        html += `
            <tr class="hover:bg-gray-50 border-b border-gray-200">
                <td class="px-4 py-3 text-sm text-gray-900">#${log.id}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900">${log.monitoring_key}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${typeBadge}">
                        ${typeLabel}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                        <i class="${statusIcon} mr-1"></i>${log.status}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate" title="${log.message}">
                    ${log.message || '-'}
                </td>
                <td class="px-4 py-3 text-center text-sm">
                    <span class="text-green-600 font-semibold">${log.successful_periods || 0}</span> /
                    <span class="text-red-600 font-semibold">${log.failed_periods || 0}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                    ${log.started_at_formatted || log.started_at}
                </td>
                <td class="px-4 py-3 text-sm text-gray-700">
                    ${log.duration_formatted || (log.duration_seconds ? log.duration_seconds + 's' : '-')}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// Get status CSS class
function getStatusClass(status) {
    switch (status) {
        case 'success':
            return 'bg-green-100 text-green-800';
        case 'error':
            return 'bg-red-100 text-red-800';
        case 'running':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

// Get status icon
function getStatusIcon(status) {
    switch (status) {
        case 'success':
            return 'fas fa-check-circle';
        case 'error':
            return 'fas fa-times-circle';
        case 'running':
            return 'fas fa-spinner fa-spin';
        default:
            return 'fas fa-question-circle';
    }
}

// Update pagination controls
function updateLogPagination(pagination) {
    const logInfo = document.getElementById('logInfo');
    const prevBtn = document.getElementById('logPrevBtn');
    const nextBtn = document.getElementById('logNextBtn');

    logPaginationHasMore = pagination.has_more;

    const showing = Math.min(pagination.offset + pagination.limit, pagination.total);
    logInfo.textContent = `Menampilkan ${pagination.offset + 1}-${showing} dari ${pagination.total} log`;

    // Update buttons
    prevBtn.disabled = pagination.offset === 0;
    nextBtn.disabled = !pagination.has_more;
}

// Refresh logs
function refreshSyncLogs() {
    loadSyncLogs(0);
}

// Clear old logs
async function clearOldLogs() {
    const result = await Swal.fire({
        title: 'Bersihkan Log Lama?',
        text: 'Ini akan menghapus semua log kecuali 100 log terbaru. Lanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('../api/sync-logs/index.php?clear_all=true', {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: data.message,
                timer: 2000
            });
            refreshSyncLogs();
        } else {
            throw new Error(data.error || 'Failed to clear logs');
        }
    } catch (error) {
        console.error('Failed to clear logs:', error);
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Gagal menghapus log: ' + error.message
        });
    }
}

// Auto-load logs when tab is activated
document.addEventListener('DOMContentLoaded', () => {
    // Load logs when Log tab is clicked
    const logTab = document.querySelector('[onclick="switchTab(\'log\')"]');
    if (logTab) {
        logTab.addEventListener('click', () => {
            setTimeout(() => loadSyncLogs(0), 100);
        });
    }
});
