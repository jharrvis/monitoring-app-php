# Catatan Perubahan

## [Rilis Terbaru] - 2025-10-20

### Penambahan Tab Log & Database Logging (20 Oktober 2025 - Sore/Malam)
- **Menambahkan Tab Log di Admin Dashboard:**
  - Tab baru "Log" untuk melihat riwayat sinkronisasi
  - Tabel log dengan 8 kolom: ID, Sistem, Tipe, Status, Pesan, Berhasil/Gagal, Waktu, Durasi
  - Filter berdasarkan Status (success/error/running) dan Tipe (manual/scheduled)
  - Pagination dengan 50 log per halaman
  - Tombol Refresh dan Bersihkan Log Lama (keep last 100)
  - Auto-load saat tab diklik

- **Implementasi Database Logging:**
  - API endpoint `/api/sync-logs/` untuk CRUD log
  - Fungsi helper di `helpers.php`: `startSyncLog()`, `updateSyncLog()`, `logSyncError()`
  - Background sync (`cron/background-sync.php`) log ke database dengan summary
  - Manual sync (`admin-sync.js`) log ke database dengan durasi dan status
  - Log mencatat: monitoring_key, sync_type, status, successful/failed counts, duration

- **Perbaikan Proses Sync:**
  - Setiap endpoint diproses secara **independent** (jika 1 gagal, yang lain tetap lanjut) ✅
  - Menambahkan counter `totalSynced` dan `totalFailed` di background-sync
  - Menambahkan durasi sync (timestamp start/end)
  - Status icon dengan emoji ✓ (success) dan ✗ (error)

### File yang Dibuat/Diubah
- `api/sync-logs/index.php` - API endpoint untuk log (GET, DELETE dengan pagination)
- `assets/js/admin-logs.js` - JavaScript untuk Tab Log (render table, filter, pagination)
- `includes/helpers.php` - Fungsi logging: startSyncLog(), updateSyncLog(), logSyncError()
- `admin/index.php` - Tambah tab "Log" dengan UI lengkap
- `assets/js/admin-sync.js` - Tambah logging ke database untuk manual sync
- `cron/background-sync.php` - Tambah logging ke database untuk scheduled sync

### Jawaban Pertanyaan:
1. **Apakah sync berjalan bersamaan?** → Tidak, sequential (satu per satu)
2. **Jika 1 gagal, apakah semua gagal?** → Tidak! Setiap endpoint independent dengan try-catch
3. **SIPP berhasil, lainnya gagal** → SIPP akan tersimpan ✅, yang gagal hanya ditandai failed
4. **Background sync vs manual sync** → Keduanya sekarang sama-sama log ke database

---

### Penambahan Console Logging & Troubleshooting Guide (20 Oktober 2025 - Sore)
- **Menambahkan console logging untuk debugging sync:**
  - Log setiap API call dengan URL lengkap
  - Log response dari API untuk setiap sistem
  - Log struktur data yang digunakan (database_record/data/direct)
  - Log hasil save ke database (success/failed)
  - Menambahkan prefix `[Sync]` dan `[Sync Latest]` untuk membedakan mode
  - Menambahkan emoji ✓ (berhasil) dan ✗ (gagal) untuk mudah dibaca

- **Membuat dokumentasi troubleshooting lengkap:**
  - Panduan cara melihat log (file & browser console)
  - Troubleshooting 5 masalah umum sync gagal
  - Status API endpoint yang aktif/tidak aktif
  - Cara testing manual setiap komponen
  - Expected behavior untuk sync berhasil/gagal
  - 3 opsi mengatasi "Gagal: 6" (install API, input manual, nonaktifkan sistem)

### File yang Diubah/Ditambah
- `assets/js/admin-sync.js` - Menambahkan console.log di setiap tahap sync
- `TROUBLESHOOTING.md` - Dokumentasi lengkap troubleshooting (file baru)

### Penjelasan "Berhasil: 0, Gagal: 6"
Dari 26 sistem monitoring, hanya 6 yang memiliki API endpoint:
- ✅ **SIPP** - Aktif dan berfungsi normal
- ❌ **Mediasi, E-Court, Gugatan, Banding, Kasasi** - API endpoint 404 (belum di-install/offline)

20 sistem lainnya tidak memiliki API dan harus diinput manual melalui admin panel.

---

### Perbaikan Sync Data (20 Oktober 2025 - Sore)
- **Memperbaiki fungsi Sync Latest Data yang gagal:**
  - Menambahkan dukungan untuk berbagai format response API (nested structures)
  - Menangani 3 format response: `{data: {database_record: {...}}}`, `{data: {...}}`, dan `{...}` langsung
  - Memperbaiki parsing data dari API eksternal yang memiliki struktur berbeda-beda

- **Meningkatkan error handling:**
  - Menambahkan try-catch untuk operasi logging ke tabel `sync_logs` (optional)
  - Menambahkan try-catch untuk update tabel `sync_settings` (optional)
  - Menambahkan logging lebih informatif untuk debugging
  - Menampilkan response API yang gagal di log (truncated untuk performa)

- **Memperbaiki bug:**
  - Fix syntax error di comment cron job yang menyebabkan parse error
  - Memperbaiki kompatibilitas dengan tabel database yang berbeda struktur

### File yang Diubah
- `assets/js/admin-sync.js` - Fungsi syncAllData() dan syncLatestData() dengan fallback parsing
- `api/sync/index.php` - API endpoint sync via Server-Sent Events dengan multi-format support
- `cron/background-sync.php` - Background sync cron job dengan improved error handling

### Testing
- ✅ SIPP API berhasil di-sync dengan data: Current=63.22, Target=71, Percentage=12%
- ✅ Background sync cron job berjalan tanpa error
- ✅ Data tersimpan dengan benar ke database

---

## [Rilis Sebelumnya] - 2025-10-20

### Perubahan
- Memperbarui kartu dashboard "Persentase 100%" untuk menampilkan jumlah total dari semua item `current_value` daripada persentase rata-rata
- Mengubah label dari "Persentase 100%" menjadi "Total Nilai Saat Ini" untuk mencerminkan perhitungan yang baru
- Menambahkan logika perhitungan untuk menjumlahkan semua item `current_value` dari konfigurasi monitoring
  - Menambahkan inisialisasi variabel `$totalCurrentValues`
  - Menambahkan logika untuk mengakumulasi `current_value` untuk setiap item monitoring
  - Memperbarui elemen tampilan untuk menampilkan jumlah total daripada persentase rata-rata
- Menghapus opsi "Terbaru" dari filter tahun/kuartal
- Mengganti default tampilan dari "terbaru" ke tahun dan kuartal saat ini
- Memperbarui logika filter untuk selalu menampilkan data untuk tahun/kuartal tertentu
- Memperbaiki bug pada proses sync data di admin panel
  - Memperbaiki penanganan target_value dari API response
  - Memperbaiki perhitungan persentase menggunakan current_value / target_value
  - Memperbaiki parameter API endpoint untuk sync sesuai dengan format yang digunakan cron

### Isu Ditemukan
- Nilai target untuk item "Kasasi dan PK" saat ini adalah 100, seharusnya 2
  - Ditemukan bahwa target value diperoleh dari API endpoint masing-masing monitoring
  - Nilai target yang benar (2) perlu diperbaiki di sumber data API

### File yang Diubah
- `index.php` - Memperbarui logika perhitungan, tampilan dashboard, dan filter tahun/kuartal
- `changelog.md` - Menambahkan catatan perubahan
- `assets/js/admin-sync.js` - Memperbaiki penanganan target_value dari API response saat sync dan parameter API endpoint
- `api/monitoring-data/index.php` - Memperbaiki perhitungan persentase dan capping sesuai max_value konfigurasi