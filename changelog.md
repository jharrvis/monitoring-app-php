# Catatan Perubahan

## [Rilis Terbaru] - 2025-10-20

### Perubahan
- Memperbarui kartu dashboard "Persentase 100%" untuk menampilkan jumlah total dari semua item `current_value` daripada persentase rata-rata
- Mengubah label dari "Persentase 100%" menjadi "Total Nilai Saat Ini" untuk mencerminkan perhitungan yang baru
- Menambahkan logika perhitungan untuk menjumlahkan semua item `current_value` dari konfigurasi monitoring
  - Menambahkan inisialisasi variabel `$totalCurrentValues`
  - Menambahkan logika untuk mengakumulasi `current_value` untuk setiap item monitoring
  - Memperbarui elemen tampilan untuk menampilkan jumlah total daripada persentase rata-rata
- Menambahkan script breakdown data untuk tahun 2024 triwulan 1

### Isu Ditemukan
- Nilai target untuk item "Kasasi dan PK" saat ini adalah 100, seharusnya 2
  - Ditemukan bahwa target value diperoleh dari API endpoint masing-masing monitoring
  - Nilai target yang benar (2) perlu diperbaiki di sumber data API
- Nilai berbeda antara filter "Terbaru" dan periode spesifik (contoh: 2025 Q3)
  - Ini wajar karena menunjukkan data dari periode waktu yang berbeda
  - Filter "Terbaru" menampilkan data terkini dari setiap sistem
  - Filter tahun/kuartal menampilkan data untuk periode yang dipilih

### File yang Diubah
- `index.php` - Memperbarui logika perhitungan dan tampilan dashboard
- `changelog.md` - Menambahkan catatan perubahan
- `breakdown-2024-q1.php` - Menambahkan script untuk breakdown nilai per item periode 2024 triwulan 1
- `check-kasasi-pk.php` - Menambahkan script untuk memeriksa konfigurasi dan data Kasasi dan PK
- `compare-data.php` - Menambahkan script untuk membandingkan data terbaru vs periode spesifik