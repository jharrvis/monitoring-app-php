# PA Salatiga Monitoring Dashboard - PHP Version

Dashboard monitoring kinerja pengadilan untuk PA Salatiga (Smartvinesa v.13) - versi PHP Native.

## 🚀 Fitur Utama

- **Dashboard Publik**: Monitoring real-time dengan auto-slide antar halaman
- **Admin Panel**: Kelola sistem, input data, visualisasi charts, dan pengaturan
- **Sinkronisasi Otomatis**: Background sync dari API eksternal
- **22+ Sistem Monitoring**: SIPP, Mediasi, E-Court, Banding, dll
- **Visualisasi Data**: Charts interaktif dengan Chart.js
- **Responsive Design**: Menggunakan Tailwind CSS

## 📋 Requirements

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Apache/Nginx Web Server
- Extension PHP: PDO, PDO_MySQL, JSON, mbstring

## 🔧 Instalasi

### 1. Clone atau Copy Project

```bash
cd /path/to/htdocs
# Copy folder monitoring-app-php ke htdocs
```

### 2. Konfigurasi Database

Edit file `includes/config.php`:

```php
define('DB_HOST', '167.172.88.142');
define('DB_USER', 'generator_monitoring');
define('DB_PASS', '}Pqm;?_0bgg()mv!');
define('DB_NAME', 'monitoring_db');
define('DB_PORT', '3306');
```

### 3. Import Database Schema

Gunakan schema dari project Next.js:
- `monitoring-app/database/init.sql`
- `monitoring-app/database/init-monitoring-tables.sql`
- `monitoring-app/database/create_sync_tables.sql`

### 4. Set Permissions

```bash
chmod -R 755 monitoring-app-php
chmod -R 777 monitoring-app-php/logs
```

### 5. Akses Aplikasi

- **Dashboard Publik**: `http://localhost/monitoring-app-php/`
- **Admin Login**: `http://localhost/monitoring-app-php/admin/login.php`

**Default Credentials:**
- Username: `admin`
- Password: `admin123`

## 📁 Struktur Folder

```
monitoring-app-php/
├── admin/                      # Admin area
│   ├── index.php              # Admin panel
│   └── login.php              # Login page
├── api/                       # REST API endpoints
│   ├── auth/                  # Authentication
│   ├── monitoring-configs/    # Config CRUD
│   ├── monitoring-data/       # Data CRUD
│   ├── settings/              # Settings API
│   └── sync/                  # Sync operations
├── assets/                    # Static assets
│   ├── css/                   # Custom CSS
│   └── js/                    # JavaScript files
│       ├── dashboard.js       # Public dashboard
│       └── admin.js           # Admin panel
├── cron/                      # Background jobs
│   └── background-sync.php    # Auto sync script
├── includes/                  # Core files
│   ├── config.php             # Configuration
│   ├── db.php                 # Database class
│   ├── auth.php               # Authentication
│   ├── cache.php              # Cache service
│   └── helpers.php            # Helper functions
├── logs/                      # Log files
├── index.php                  # Public dashboard
└── README.md                  # Documentation
```

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/logout.php` - Logout

### Monitoring Configs
- `GET /api/monitoring-configs/index.php` - Get all configs
- `GET /api/monitoring-configs/index.php?id={id}` - Get single config
- `POST /api/monitoring-configs/index.php` - Create config
- `PUT /api/monitoring-configs/index.php` - Update config
- `DELETE /api/monitoring-configs/index.php?id={id}` - Delete config

### Monitoring Data
- `GET /api/monitoring-data/index.php` - Get all data
- `GET /api/monitoring-data/index.php?id={id}` - Get single data
- `POST /api/monitoring-data/index.php` - Create/update data
- `PUT /api/monitoring-data/index.php` - Update data
- `DELETE /api/monitoring-data/index.php?id={id}` - Delete data

**Query Parameters:**
- `year` - Filter by year
- `quarter` - Filter by quarter (1-4)
- `monitoring_id` - Filter by monitoring config ID
- `search` - Search by name/key

### Sync Operations
- `GET /api/sync/index.php?type={type}` - Sync data (SSE)

**Sync Types:**
- `sipp` - Sync SIPP data
- `mediasi` - Sync Mediasi data
- `banding` - Sync Banding data
- `ecourt` - Sync E-Court data
- `gugatan-mandiri` - Sync Gugatan Mandiri data

### Settings
- `GET /api/settings/index.php` - Get all settings
- `PUT /api/settings/index.php` - Update settings

## ⚙️ Background Sync (Cron)

### Setup Cron Job

Edit crontab:
```bash
crontab -e
```

Tambahkan (jalankan setiap 30 menit):
```bash
*/30 * * * * php /path/to/monitoring-app-php/cron/background-sync.php >> /path/to/logs/cron.log 2>&1
```

### Manual Run

```bash
php cron/background-sync.php
```

Log akan disimpan di `logs/background-sync.log`

## 🎨 Fitur Dashboard

### Dashboard Publik
- Auto-slide setiap 5 detik
- 2 Halaman: Sistem Utama & Sistem Pendukung
- Filter status: All, Good, Warning, Critical
- Animated counters & progress bars
- Click logo 5x untuk admin login
- Modal detail dengan historical chart

### Admin Panel

#### Tab Kelola Sistem
- Daftar konfigurasi monitoring
- Tombol sync per sistem (SIPP, Mediasi, dll)
- CRUD operations
- Real-time sync progress (Server-Sent Events)

#### Tab Input Data
- Input data triwulanan
- Filter: Tahun, Triwulan, Search
- CRUD operations
- Validasi data otomatis

#### Tab Charts
- Pilih sistem monitoring
- Visualisasi bar chart
- Data 12 triwulan terakhir
- Color-coded by status

#### Tab Pengaturan
- App name & institution name
- Slide duration
- Auto-slide enabled
- Auto-update enabled

## 🎯 Sistem yang Dimonitor

### Halaman 1 - Sistem Utama
1. SIPP (100 poin)
2. Mediasi (100 poin)
3. E-Court (12 poin)
4. Gugatan Mandiri (2 poin)
5. Banding (100 poin)
6. Kasasi & PK (100 poin)
7. Eksaminasi (3 poin)
8. Keuangan Perkara (4 poin)
9. Pengelolaan PNBP (3 poin)
10. Zona Integritas (5 poin)
11. SKM/IKM (4 poin)
12. Inovasi (3 poin)

### Halaman 2 - Sistem Pendukung
13. Pelaporan Kinsatker (3 poin)
14. Layanan PTSP (2 poin)
15. IKPA (6 poin)
16. Website (3 poin)
17. Prestasi (5 poin)
18. Validasi Data Simtepa (3 poin)
19. SIKEP (3 poin)
20. SKP (3 poin)
21. CCTV (3 poin)
22. Sipintar (3 poin)
23. ETR (3 poin)
24. LHKPN & LHKASN (5 poin)
25. Kumdis (5 poin)
26. LHP oleh Hawasbid (3 poin)

## 🔒 Security Features

- Password hashing dengan bcryptjs
- Session management
- Session timeout (1 jam)
- CSRF protection (via session)
- SQL injection protection (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Input sanitization
- Authentication required untuk admin endpoints

## 🚦 Status Calculation

```php
if ($percentage >= 100) {
    $status = 'good';      // Green
} elseif ($percentage >= 50) {
    $status = 'warning';   // Yellow
} else {
    $status = 'critical';  // Red
}
```

## 📊 Database Schema

### monitoring_configs
- Konfigurasi sistem monitoring
- Icon, nama, deskripsi, max_value, unit
- API endpoint untuk sync
- Page number (1 atau 2)

### monitoring_data
- Data triwulanan per sistem
- Current value, target value, percentage
- Calculated status

### app_settings
- Pengaturan aplikasi
- Key-value dengan type casting

### admin_users
- User admin dengan password bcrypt

### sync_logs
- Log history sinkronisasi

## 🛠️ Troubleshooting

### Error: Database Connection Failed
- Periksa credentials di `includes/config.php`
- Pastikan MySQL service running
- Cek firewall jika remote database

### Error: Session Already Started
- Periksa output buffer di `php.ini`
- Set `session.auto_start = 0`

### Error: Permission Denied on logs/
```bash
chmod -R 777 logs/
```

### Cron Job Tidak Jalan
- Periksa path PHP: `which php`
- Gunakan absolute path di crontab
- Cek cron logs: `/var/log/cron`

### Sync Tidak Berfungsi
- Periksa API endpoint di database
- Test API manual: `curl {api_endpoint}`
- Cek logs: `logs/background-sync.log`

## 📝 Changelog

### Version 1.0.0 (2025)
- Initial release
- Port dari Next.js ke PHP native
- Semua fitur dashboard publik
- Admin panel lengkap
- Background sync service
- REST API endpoints

## 👨‍💻 Developer Notes

### Menambah Sistem Monitoring Baru

1. Insert ke `monitoring_configs`:
```sql
INSERT INTO monitoring_configs (
    monitoring_key, monitoring_name, monitoring_description,
    max_value, unit, icon, page_number, display_order,
    is_active, api_endpoint
) VALUES (
    'SISTEM_BARU', 'Nama Sistem Baru', 'Deskripsi',
    100, '%', '📊', 1, 99,
    1, 'http://localhost/api/sistem-baru/api.php'
);
```

2. Sistem otomatis muncul di dashboard

### Custom API Endpoint Format

API eksternal harus return JSON:
```json
{
    "current_value": 75.5,
    "target_value": 100
}
```

### Cache Management

Clear cache programmatically:
```php
cache()->clear();
// atau
cache()->delete('specific_key');
```

## 📄 License

© 2025 PA Salatiga. All rights reserved.

## 📞 Support

Untuk pertanyaan dan dukungan, hubungi tim IT PA Salatiga.
