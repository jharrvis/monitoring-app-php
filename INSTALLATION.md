# Panduan Instalasi PA Salatiga Monitoring Dashboard (PHP)

## 📋 Prerequisites

Sebelum memulai instalasi, pastikan sistem Anda memiliki:

### Software Requirements
- **PHP**: Version 7.4 atau lebih tinggi
- **MySQL/MariaDB**: Version 5.7+ atau 10.3+
- **Web Server**: Apache 2.4+ atau Nginx 1.18+
- **Composer** (optional): Untuk dependency management

### PHP Extensions
Pastikan extension berikut sudah terinstall dan enabled:
- PDO
- PDO_MySQL
- JSON
- mbstring
- session
- curl (untuk sync API)

Cek dengan:
```bash
php -m | grep -E "PDO|json|mbstring"
```

## 🚀 Langkah Instalasi

### 1. Persiapan Environment

#### Windows (XAMPP/Laragon)
```bash
# Copy project ke htdocs
cd C:\xampp\htdocs
# atau
cd C:\laragon\www
```

#### Linux/Ubuntu
```bash
# Install Apache, PHP, MySQL
sudo apt update
sudo apt install apache2 php php-mysql php-mbstring php-json mysql-server

# Copy project ke document root
cd /var/www/html
```

### 2. Copy Project Files

```bash
# Copy folder monitoring-app-php
cp -r /path/to/monitoring-app-php /var/www/html/
# atau drag & drop di Windows
```

### 3. Konfigurasi Database

#### A. Buat Database (jika belum ada)

Login ke MySQL:
```bash
mysql -u root -p
```

Buat database:
```sql
CREATE DATABASE monitoring_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Buat user (optional, untuk security):
```sql
CREATE USER 'monitoring_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON monitoring_db.* TO 'monitoring_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### B. Import Database Schema

Import schema dari project Next.js:
```bash
# Dari folder monitoring-app/database/
mysql -u root -p monitoring_db < init.sql
mysql -u root -p monitoring_db < init-monitoring-tables.sql
mysql -u root -p monitoring_db < create_sync_tables.sql
```

Atau via phpMyAdmin:
1. Buka phpMyAdmin
2. Pilih database `monitoring_db`
3. Tab "Import"
4. Upload file SQL satu per satu
5. Klik "Go"

#### C. Insert Default Admin User

```sql
USE monitoring_db;

INSERT INTO admin_users (username, password, email, full_name, role, created_at, updated_at)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: admin123
    'admin@pasalatiga.go.id',
    'Administrator',
    'admin',
    NOW(),
    NOW()
);
```

#### D. Insert Default Settings

```sql
INSERT INTO app_settings (setting_key, setting_value, setting_type, description, created_at, updated_at)
VALUES
('app_name', 'Smartvinesa v.13', 'string', 'Nama Aplikasi', NOW(), NOW()),
('institution_name', 'PA Salatiga', 'string', 'Nama Institusi', NOW(), NOW()),
('slide_duration', '5000', 'number', 'Durasi slide (ms)', NOW(), NOW()),
('update_interval', '300000', 'number', 'Interval update (ms)', NOW(), NOW()),
('auto_update_enabled', 'true', 'boolean', 'Auto update enabled', NOW(), NOW()),
('auto_slide_enabled', 'true', 'boolean', 'Auto slide enabled', NOW(), NOW());
```

### 4. Konfigurasi Aplikasi

#### A. Edit Configuration File

Edit `includes/config.php`:

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');  // atau IP remote
define('DB_USER', 'monitoring_user');
define('DB_PASS', 'your_password');
define('DB_NAME', 'monitoring_db');
define('DB_PORT', '3306');

// Application Configuration
define('APP_NAME', 'Smartvinesa v.13');
define('INSTITUTION_NAME', 'PA Salatiga');
define('BASE_URL', 'http://localhost/monitoring-app-php');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 900); // 15 minutes

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (DISABLE IN PRODUCTION!)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
```

**⚠️ PENTING untuk Production:**
```php
error_reporting(0);
ini_set('display_errors', 0);
```

### 5. Set File Permissions

#### Linux/Ubuntu
```bash
cd /var/www/html/monitoring-app-php

# Set ownership
sudo chown -R www-data:www-data .

# Set permissions
sudo chmod -R 755 .
sudo chmod -R 777 logs/

# Jika menggunakan user lain
sudo chown -R $USER:www-data .
```

#### Windows (XAMPP/Laragon)
Klik kanan folder `logs/` → Properties → Security → Edit → Allow "Full Control"

### 6. Apache Configuration

#### A. Enable Mod Rewrite (Linux)
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod expires
sudo a2enmod deflate
sudo systemctl restart apache2
```

#### B. Update VirtualHost (Optional)

Edit `/etc/apache2/sites-available/000-default.conf`:

```apache
<VirtualHost *:80>
    ServerName monitoring.pasalatiga.go.id
    DocumentRoot /var/www/html/monitoring-app-php

    <Directory /var/www/html/monitoring-app-php>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/monitoring_error.log
    CustomLog ${APACHE_LOG_DIR}/monitoring_access.log combined
</VirtualHost>
```

Restart Apache:
```bash
sudo systemctl restart apache2
```

### 7. Test Installation

#### A. Test Database Connection

Buat file `test-db.php`:
```php
<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $result = db()->fetchOne("SELECT 1 as test");
    echo "✅ Database connection successful!\n";
    print_r($result);
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage();
}
?>
```

Jalankan:
```bash
php test-db.php
```

#### B. Test Web Access

Buka browser:
- **Dashboard Publik**: http://localhost/monitoring-app-php/
- **Admin Login**: http://localhost/monitoring-app-php/admin/login.php

Login dengan:
- Username: `admin`
- Password: `admin123`

### 8. Setup Background Sync (Cron Job)

#### Linux/Ubuntu

Edit crontab:
```bash
crontab -e
```

Tambahkan (jalankan setiap 30 menit):
```bash
*/30 * * * * php /var/www/html/monitoring-app-php/cron/background-sync.php >> /var/www/html/monitoring-app-php/logs/cron.log 2>&1
```

Test manual:
```bash
php /var/www/html/monitoring-app-php/cron/background-sync.php
```

Check logs:
```bash
tail -f /var/www/html/monitoring-app-php/logs/background-sync.log
```

#### Windows (Task Scheduler)

1. Buka **Task Scheduler**
2. Create Basic Task
   - Name: "PA Monitoring Sync"
   - Trigger: Daily, every 30 minutes
   - Action: Start a program
     - Program: `C:\xampp\php\php.exe`
     - Arguments: `C:\xampp\htdocs\monitoring-app-php\cron\background-sync.php`
3. Finish

Atau buat file `sync-task.bat`:
```batch
@echo off
C:\xampp\php\php.exe C:\xampp\htdocs\monitoring-app-php\cron\background-sync.php >> C:\xampp\htdocs\monitoring-app-php\logs\cron.log 2>&1
```

## 🔧 Konfigurasi Tambahan

### SSL/HTTPS Setup (Optional)

#### A. Install Certbot (Let's Encrypt)
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d monitoring.pasalatiga.go.id
```

#### B. Update BASE_URL
```php
define('BASE_URL', 'https://monitoring.pasalatiga.go.id');
```

### PHP.ini Optimization

Edit `php.ini`:
```ini
; Memory & Upload
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
max_input_time = 300

; Session
session.gc_maxlifetime = 3600
session.cookie_httponly = 1
session.cookie_secure = 1  ; if using HTTPS

; Timezone
date.timezone = Asia/Jakarta

; Error Logging
log_errors = On
error_log = /var/log/php_errors.log
display_errors = Off  ; PRODUCTION ONLY
```

Restart PHP:
```bash
sudo systemctl restart apache2
# atau
sudo systemctl restart php7.4-fpm
```

### MySQL Optimization

Edit `/etc/mysql/my.cnf`:
```ini
[mysqld]
max_connections = 100
wait_timeout = 600
interactive_timeout = 600

# InnoDB Settings
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2

# Query Cache
query_cache_type = 1
query_cache_size = 32M
```

Restart MySQL:
```bash
sudo systemctl restart mysql
```

## 🐛 Troubleshooting

### Error: Cannot connect to database
```bash
# Check MySQL service
sudo systemctl status mysql

# Check credentials
mysql -u monitoring_user -p monitoring_db

# Check firewall (remote DB)
sudo ufw allow 3306/tcp
```

### Error: 500 Internal Server Error
```bash
# Check Apache error log
tail -f /var/log/apache2/error.log

# Check PHP error log
tail -f /var/log/php_errors.log

# Check file permissions
ls -la /var/www/html/monitoring-app-php
```

### Error: Session warnings
```php
// Clear session files
rm -rf /var/lib/php/sessions/*

// Or set custom session path in config.php
session_save_path(__DIR__ . '/../logs/sessions');
```

### Error: Logs not writing
```bash
# Check permissions
sudo chmod 777 /var/www/html/monitoring-app-php/logs/
sudo chown www-data:www-data /var/www/html/monitoring-app-php/logs/
```

### Cron Job not running
```bash
# Check cron service
sudo systemctl status cron

# Check cron logs
tail -f /var/log/syslog | grep CRON

# Test manual execution
php /var/www/html/monitoring-app-php/cron/background-sync.php
```

## ✅ Post-Installation Checklist

- [ ] Database connected successfully
- [ ] Admin login works
- [ ] Public dashboard displays data
- [ ] File permissions set correctly
- [ ] Error logging configured
- [ ] Cron job scheduled
- [ ] Background sync working
- [ ] SSL certificate installed (production)
- [ ] Error reporting disabled (production)
- [ ] Default password changed
- [ ] Backup strategy implemented

## 🔒 Security Hardening

1. **Change default admin password**
   ```sql
   UPDATE admin_users SET password = '$2y$10$...' WHERE username = 'admin';
   ```

2. **Disable directory listing**
   - Sudah ada di `.htaccess`

3. **Hide PHP version**
   ```ini
   expose_php = Off
   ```

4. **Enable HTTPS only**
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

5. **Restrict API access by IP** (optional)
   ```apache
   <Directory "/var/www/html/monitoring-app-php/api">
       Require ip 192.168.1.0/24
   </Directory>
   ```

## 📚 Additional Resources

- [PHP Official Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Apache HTTP Server](https://httpd.apache.org/docs/)
- [Chart.js Documentation](https://www.chartjs.org/docs/)
- [Tailwind CSS](https://tailwindcss.com/docs)

## 📞 Support

Jika mengalami kendala instalasi, hubungi:
- Tim IT PA Salatiga
- Email: support@pasalatiga.go.id
