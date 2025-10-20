// ===== SYNC DATA FUNCTIONS =====

// Show sync options
async function showSyncOptions() {
    const result = await Swal.fire({
        title: 'Pilih Jenis Sinkronisasi',
        html: `
            <div class="text-left space-y-3">
                <div class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer" onclick="syncAllData()">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-database text-blue-500 text-2xl mt-1"></i>
                        <div>
                            <h3 class="font-bold text-gray-900">Sync All Data</h3>
                            <p class="text-sm text-gray-600">Sinkronisasi semua data dari 2023 sampai data terakhir</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 cursor-pointer" onclick="syncLatestData()">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-sync-alt text-green-500 text-2xl mt-1"></i>
                        <div>
                            <h3 class="font-bold text-gray-900">Sync Latest Data</h3>
                            <p class="text-sm text-gray-600">Sinkronisasi data triwulan berjalan saja</p>
                        </div>
                    </div>
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Batal',
        width: '600px'
    });
}

// Sync all data (from 2023 to latest)
async function syncAllData() {
    Swal.close();

    const confirmResult = await Swal.fire({
        title: 'Konfirmasi Sync All Data',
        text: 'Proses ini akan mengambil semua data dari 2023 hingga sekarang. Lanjutkan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Sync!',
        cancelButtonText: 'Batal'
    });

    if (!confirmResult.isConfirmed) return;

    Swal.fire({
        title: 'Syncing Data...',
        html: '<div id="syncProgress">Memulai sinkronisasi...</div>',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const configResponse = await fetch('../api/monitoring-configs/index.php');
        const configResult = await configResponse.json();

        if (!configResult.data || configResult.data.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Tidak Ada Data', text: 'Tidak ada konfigurasi monitoring untuk di-sync' });
            return;
        }

        const configs = configResult.data.filter(c => c.api_endpoint);
        if (configs.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Tidak Ada API', text: 'Tidak ada konfigurasi dengan API endpoint' });
            return;
        }

        const currentYear = new Date().getFullYear();
        const startYear = 2023;
        let totalSynced = 0;
        let totalFailed = 0;

        for (const config of configs) {
            document.getElementById('syncProgress').innerHTML = `Syncing ${config.monitoring_name}...<br>Berhasil: ${totalSynced} | Gagal: ${totalFailed}`;

            for (let year = startYear; year <= currentYear; year++) {
                for (let quarter = 1; quarter <= 4; quarter++) {
                    const now = new Date();
                    const currentQ = Math.ceil((now.getMonth() + 1) / 3);
                    if (year === currentYear && quarter > currentQ) continue;

                    try {
                        const apiUrl = `${config.api_endpoint}?type=triwulan&triwulan=${quarter}&year=${year}&clear_cache=1`;
                        console.log(`[Sync] Calling API: ${apiUrl}`);

                        const dataResponse = await fetch(apiUrl);
                        const apiData = await dataResponse.json();
                        console.log(`[Sync] API Response for ${config.monitoring_name}:`, apiData);

                        // Check if response has nested data structure or direct structure
                        let responseData = null;

                        // Try different response structures
                        if (apiData.data?.database_record?.current_value !== undefined) {
                            // Structure: {data: {database_record: {current_value, target_value}}}
                            responseData = apiData.data.database_record;
                            console.log(`[Sync] Using database_record structure`);
                        } else if (apiData.data?.current_value !== undefined) {
                            // Structure: {data: {current_value, target_value}}
                            responseData = apiData.data;
                            console.log(`[Sync] Using data structure`);
                        } else if (apiData.current_value !== undefined) {
                            // Structure: {current_value, target_value}
                            responseData = apiData;
                            console.log(`[Sync] Using direct structure`);
                        }

                        if (responseData && responseData.current_value !== undefined) {
                            console.log(`[Sync] Extracted data:`, responseData);
                            // Use target_value from API response if available, otherwise default to config.max_value
                            const targetValue = responseData.target_value !== undefined ? responseData.target_value : config.max_value;

                            const saveResponse = await fetch('../api/monitoring-data/index.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    monitoring_id: config.id,
                                    year: year,
                                    quarter: quarter,
                                    current_value: responseData.current_value,
                                    target_value: targetValue
                                })
                            });

                            const saveResult = await saveResponse.json();
                            console.log(`[Sync] Save result for ${config.monitoring_name}:`, saveResult);
                            if (saveResult.success) {
                                totalSynced++;
                                console.log(`[Sync] ✓ Successfully synced ${config.monitoring_name} ${year}-Q${quarter}`);
                            } else {
                                totalFailed++;
                                console.error(`[Sync] ✗ Failed to save ${config.monitoring_name} ${year}-Q${quarter}:`, saveResult);
                            }
                        } else {
                            console.warn(`[Sync] ✗ Invalid data structure for ${config.monitoring_name} ${year}-Q${quarter}:`, apiData);
                            totalFailed++;
                        }
                    } catch (error) {
                        console.error(`[Sync] ✗ Exception for ${config.monitoring_name} ${year}-Q${quarter}:`, error);
                        totalFailed++;
                    }
                }
            }
        }

        Swal.fire({
            icon: 'success',
            title: 'Sync Selesai!',
            html: `<p>Berhasil: <strong>${totalSynced}</strong></p><p>Gagal: <strong>${totalFailed}</strong></p>`,
            timer: 3000
        });

        await loadMonitoringData();
    } catch (error) {
        console.error('Sync error:', error);
        Swal.fire({ icon: 'error', title: 'Sync Gagal', text: error.message });
    }
}

// Sync latest data (current quarter only)
async function syncLatestData() {
    Swal.close();

    const confirmResult = await Swal.fire({
        title: 'Konfirmasi Sync Latest Data',
        text: 'Proses ini akan mengambil data triwulan berjalan saja. Lanjutkan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Sync!',
        cancelButtonText: 'Batal'
    });

    if (!confirmResult.isConfirmed) return;

    Swal.fire({
        title: 'Syncing Data...',
        html: '<div id="syncProgress">Memulai sinkronisasi...</div>',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentQuarter = Math.ceil((now.getMonth() + 1) / 3);

        const configResponse = await fetch('../api/monitoring-configs/index.php');
        const configResult = await configResponse.json();

        if (!configResult.data || configResult.data.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Tidak Ada Data', text: 'Tidak ada konfigurasi monitoring untuk di-sync' });
            return;
        }

        const configs = configResult.data.filter(c => c.api_endpoint);
        if (configs.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Tidak Ada API', text: 'Tidak ada konfigurasi dengan API endpoint' });
            return;
        }

        let totalSynced = 0;
        let totalFailed = 0;

        for (const config of configs) {
            document.getElementById('syncProgress').innerHTML = `Syncing ${config.monitoring_name}...<br>Berhasil: ${totalSynced} | Gagal: ${totalFailed}`;

            try {
                const apiUrl = `${config.api_endpoint}?type=triwulan&triwulan=${currentQuarter}&year=${currentYear}&clear_cache=1`;
                console.log(`[Sync Latest] Calling API: ${apiUrl}`);

                const dataResponse = await fetch(apiUrl);
                const apiData = await dataResponse.json();
                console.log(`[Sync Latest] API Response for ${config.monitoring_name}:`, apiData);

                // Check if response has nested data structure or direct structure
                let responseData = null;

                // Try different response structures
                if (apiData.data?.database_record?.current_value !== undefined) {
                    // Structure: {data: {database_record: {current_value, target_value}}}
                    responseData = apiData.data.database_record;
                    console.log(`[Sync Latest] Using database_record structure`);
                } else if (apiData.data?.current_value !== undefined) {
                    // Structure: {data: {current_value, target_value}}
                    responseData = apiData.data;
                    console.log(`[Sync Latest] Using data structure`);
                } else if (apiData.current_value !== undefined) {
                    // Structure: {current_value, target_value}
                    responseData = apiData;
                    console.log(`[Sync Latest] Using direct structure`);
                }

                if (responseData && responseData.current_value !== undefined) {
                    console.log(`[Sync Latest] Extracted data:`, responseData);
                    // Use target_value from API response if available, otherwise default to config.max_value
                    const targetValue = responseData.target_value !== undefined ? responseData.target_value : config.max_value;

                    const saveResponse = await fetch('../api/monitoring-data/index.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            monitoring_id: config.id,
                            year: currentYear,
                            quarter: currentQuarter,
                            current_value: responseData.current_value,
                            target_value: targetValue
                        })
                    });

                    const saveResult = await saveResponse.json();
                    console.log(`[Sync Latest] Save result for ${config.monitoring_name}:`, saveResult);
                    if (saveResult.success) {
                        totalSynced++;
                        console.log(`[Sync Latest] ✓ Successfully synced ${config.monitoring_name}`);
                    } else {
                        totalFailed++;
                        console.error(`[Sync Latest] ✗ Failed to save ${config.monitoring_name}:`, saveResult);
                    }
                } else {
                    console.warn(`[Sync Latest] ✗ Invalid data structure for ${config.monitoring_name}:`, apiData);
                    totalFailed++;
                }
            } catch (error) {
                console.error(`[Sync Latest] ✗ Exception for ${config.monitoring_name}:`, error);
                totalFailed++;
            }
        }

        Swal.fire({
            icon: 'success',
            title: 'Sync Selesai!',
            html: `<p>Berhasil: <strong>${totalSynced}</strong></p><p>Gagal: <strong>${totalFailed}</strong></p>`,
            timer: 3000
        });

        await loadMonitoringData();
    } catch (error) {
        console.error('Sync error:', error);
        Swal.fire({ icon: 'error', title: 'Sync Gagal', text: error.message });
    }
}
