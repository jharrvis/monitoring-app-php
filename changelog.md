# Catatan Perubahan

## [Rilis Terbaru] - 2025-10-20

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