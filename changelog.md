# Catatan Perubahan

## [Rilis Terbaru] - 2025-10-20

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