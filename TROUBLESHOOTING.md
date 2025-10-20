# Troubleshooting Guide - Monitoring App

## 📋 Cara Melihat Log Sync Data

### 1. **Log File (Background Sync)**

File log tersimpan di: `logs/background-sync.log`

**Cara membaca log:**
```bash
# Lihat seluruh log
cat logs/background-sync.log

# Lihat 50 baris terakhir
tail -n 50 logs/background-sync.log

# Lihat log secara real-time
tail -f logs/background-sync.log
```

**Format log:**
```
[2025-10-20 15:20:30] === Background Sync Started ===
[2025-10-20 15:20:30] Found 6 monitoring configs to sync
[2025-10-20 15:20:30] Syncing: SIPP (sipp)
[2025-10-20 15:20:30]   Calling API: http://localhost/dashboard/sipp/api.php?...
[2025-10-20 15:20:34]   Updated: Current=63.22, Target=71, Percentage=12%
[2025-10-20 15:20:34]   SUCCESS
[2025-10-20 15:20:35] === Background Sync Completed Successfully ===
```

---

### 2. **Browser Console (Manual Sync via Admin Panel)**

**Langkah-langkah:**

1. Buka Admin Panel: `http://localhost/monitoring-app-php/admin/`
2. Login dengan kredensial admin
3. Klik tab **"Input Data"**
4. **Tekan F12** atau klik kanan → **Inspect Element**
5. Pilih tab **"Console"**
6. Klik tombol **"Sync Data"** → Pilih **"Sync Latest Data"** atau **"Sync All Data"**
7. Lihat output di console

**Contoh output di console:**
```javascript
[Sync Latest] Calling API: http://localhost/dashboard/sipp/api.php?type=triwulan&triwulan=4&year=2025&clear_cache=1
[Sync Latest] API Response for SIPP: {success: true, data: {…}}
[Sync Latest] Using database_record structure
[Sync Latest] Extracted data: {current_value: 63.22, target_value: 71, ...}
[Sync Latest] Save result for SIPP: {success: true, message: "Data updated successfully", id: 320}
[Sync Latest] ✓ Successfully synced SIPP

[Sync Latest] ✗ Invalid data structure for Mediasi: null
[Sync Latest] ✗ Exception for E-Court: SyntaxError: Unexpected token...
```

---

## 🔍 Troubleshooting Sync Gagal

### **Masalah: "Berhasil: 0, Gagal: 6"**

**Penyebab umum:**

#### 1. **API Endpoint Tidak Tersedia (404)**

**Gejala:**
```
[Sync] ✗ Invalid data structure for Mediasi: null
```

**Solusi:**
- Cek apakah API endpoint aktif dengan mengakses URL langsung di browser
- Contoh: Buka `http://localhost/dashboardsmartvinesa/mediasi/api.php?type=triwulan&triwulan=4&year=2025`
- Jika 404, pastikan folder dashboard tersebut sudah di-install dan web server berjalan

**Test via command line:**
```bash
curl http://localhost/dashboard/sipp/api.php?type=triwulan&triwulan=4&year=2025
```

---

#### 2. **Format Response API Tidak Sesuai**

**Gejala:**
```
[Sync] ✗ Invalid data structure for SystemX: {error: "Invalid quarter"}
```

**Solusi:**
- API harus mengembalikan salah satu format berikut:

**Format 1 (Nested with database_record):**
```json
{
  "success": true,
  "data": {
    "database_record": {
      "current_value": 63.22,
      "target_value": 71
    }
  }
}
```

**Format 2 (Nested):**
```json
{
  "data": {
    "current_value": 63.22,
    "target_value": 71
  }
}
```

**Format 3 (Direct):**
```json
{
  "current_value": 63.22,
  "target_value": 71
}
```

---

#### 3. **Database Connection Error**

**Gejala:**
```
[Sync] ✗ Failed to save SystemX: {error: "Database connection failed"}
```

**Solusi:**
- Cek koneksi database di `includes/config.php`
- Pastikan MySQL/MariaDB berjalan
- Test koneksi:
```bash
mysql -h167.172.88.142 -P3306 -ugenerator_monitoring -p
```

---

#### 4. **CORS Error (Cross-Origin)**

**Gejala di Console:**
```
Access to fetch at 'http://serverlokal.go.id/...' from origin 'http://localhost' has been blocked by CORS policy
```

**Solusi:**
- Pastikan server API mengizinkan CORS dari domain monitoring app
- Tambahkan header di API:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

---

#### 5. **Session Timeout / Not Authenticated**

**Gejala:**
```
{error: "Authentication required"}
```

**Solusi:**
- Login ulang ke admin panel
- Cek apakah session masih aktif (timeout default: 1 jam)
- Clear browser cookies dan login kembali

---

## 📊 Status API Endpoint Saat Ini

Dari 26 sistem monitoring, hanya **6 sistem yang memiliki API endpoint**:

| No | Sistem | API Endpoint | Status |
|----|--------|--------------|--------|
| 1 | SIPP | `http://localhost/dashboard/sipp/api.php` | ✅ AKTIF |
| 2 | Mediasi | `http://localhost/dashboardsmartvinesa/mediasi/api.php` | ❌ 404 |
| 3 | E-Court | `http://localhost/dashboardsmartvinesa/ecourt/api.php` | ❌ 404 |
| 4 | Gugatan Mandiri | `http://localhost/dashboardsmartvinesa/gugatan/api.php` | ❌ 404 |
| 5 | Banding | `http://localhost/dashboardsmartvinesa/banding/api.php` | ❌ 404 |
| 6 | Kasasi & PK | `http://serverlokal.go.id/dashboardsmartvinesa/kasasi/api.php` | ❌ 404 |

**20 sistem lainnya** tidak memiliki API endpoint dan harus diinput manual melalui admin panel.

---

## 🛠️ Testing Manual

### Test Single API Endpoint:
```bash
# Test dengan curl
curl -s "http://localhost/dashboard/sipp/api.php?type=triwulan&triwulan=4&year=2025&clear_cache=1" | jq

# Test dengan browser
# Buka: http://localhost/dashboard/sipp/api.php?type=triwulan&triwulan=4&year=2025
```

### Test Background Sync Script:
```bash
php D:\laragon\www\monitoring-app-php\cron\background-sync.php
```

### Check Database Data:
```bash
# Via API
curl "http://localhost/monitoring-app-php/api/monitoring-data/index.php?year=2025&quarter=4"

# Via MySQL (jika available)
mysql -h167.172.88.142 -P3306 -ugenerator_monitoring -p generator_monitoring \
  -e "SELECT * FROM monitoring_data WHERE year=2025 AND quarter=4;"
```

---

## 📝 Log Locations

| Log Type | Location | Purpose |
|----------|----------|---------|
| Background Sync | `logs/background-sync.log` | Cron job sync history |
| PHP Errors | `logs/php_errors.log` | PHP runtime errors |
| App Logs | `logs/app.log` | General application logs |
| Cron Logs | `logs/cron.log` | Cron execution logs |

---

## ✅ Expected Behavior

**Sync Berhasil:**
- Console: `[Sync Latest] ✓ Successfully synced SIPP`
- Database: Data tercatat di tabel `monitoring_data`
- Admin Panel: Data tampil di tabel dengan nilai terbaru

**Sync Gagal (API Offline):**
- Console: `[Sync Latest] ✗ Invalid data structure for Mediasi: null`
- Database: Data tidak berubah
- Admin Panel: Tetap menampilkan data lama (jika ada)

---

## 🔧 Cara Mengatasi "Gagal: 6"

### Opsi 1: Install API Dashboard yang Hilang
1. Install dashboard untuk sistem: Mediasi, E-Court, Gugatan, Banding, Kasasi
2. Pastikan URL endpoint sesuai dengan konfigurasi
3. Test setiap endpoint dengan curl/browser

### Opsi 2: Input Data Manual
1. Login ke admin panel
2. Tab "Input Data"
3. Pilih tahun dan kuartal
4. Klik "Tambah Data"
5. Isi manual untuk setiap sistem yang tidak memiliki API

### Opsi 3: Nonaktifkan Sistem yang Tidak Ada API-nya
1. Tab "Kelola Sistem"
2. Edit sistem yang tidak tersedia
3. Set `is_active = 0` atau hapus `api_endpoint`

---

## 📞 Support

Jika masalah masih berlanjut:
1. Check log file: `logs/background-sync.log`
2. Check browser console (F12)
3. Test API endpoint secara langsung
4. Verifikasi database connection
5. Review konfigurasi di `includes/config.php`
