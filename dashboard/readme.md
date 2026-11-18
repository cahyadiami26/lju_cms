# LJU Portal Dashboard — Log Aktivitas User

Portal SSO `https://lju.co.id/dashboard` yang mencatat aktivitas pengguna setelah login ke tabel
`login_logs`: login/logout, pembukaan dashboard, dan **klik setiap tombol aplikasi**
(Presensi Online, Naskah Dinas, Pengarsipan, Pengadaan, Task Management, SDM/HRD, Webmail,
Whistleblower, WebContent, Assessment), termasuk percobaan akses yang ditolak.

---

## 1. Alur pencatatan

| Kejadian | Dipanggil dari | Kode `activity` |
|---|---|---|
| Login password berhasil | `index.php` | `login_success` |
| Login Google berhasil | `google_callback.php` | `login_google_success` |
| Login gagal | `index.php` | `login_failed` |
| Logout | `logout.php` / `config/session.php` | `logout` |
| Sesi berakhir (idle) | `config/session.php` | `timeout` |
| Membuka dashboard | `views/dashboard.php` | `open_dashboard` |
| Klik tombol aplikasi | `go.php` | `open_<key>` |
| Percobaan akses tanpa hak | `go.php` | `access_denied` |
| Update profil | `update_profile.php` | `update_profile` |

**Klik aplikasi selalu lewat `go.php`.** Semua kartu aplikasi dan tombol Presensi Online di topbar
diarahkan ke `go.php?app=<key>`, sehingga pencatatan terjadi di server sebelum redirect. Log tetap
tercatat walau tautan dibuka di tab baru, diklik tengah, atau URL disalin.

```
Klik kartu  →  go.php?app=dms  →  validasi sesi & hak akses  →  INSERT login_logs  →  302 ke aplikasi
```

Kunjungan dashboard dibatasi agar tidak membanjiri tabel: maksimal **1 log per 15 menit per sesi**
(`logDashboardView()` memakai penanda waktu di session). Login, logout, dan klik aplikasi selalu dicatat.

---

## 2. Skema tabel `login_logs`

File referensi: `login_logs_schema.sql` (instalasi baru), `migrate_login_logs.php` (migrasi aman).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT UNSIGNED AI | primary key |
| `user_id` | INT UNSIGNED NULL | `users.id`; NULL untuk login gagal tanpa akun terdaftar |
| `activity` | VARCHAR(50) | kode aktivitas (lihat bagian 3) |
| `app_key` | VARCHAR(50) NULL | key aplikasi: `presensi`, `dms`, `arsip`, `pengadaan`, `pm`, `hrd`, `webmail`, `wbs`, `cms`, `assessment`, `dashboard` |
| `app_name` | VARCHAR(100) NULL | nama aplikasi saat tombol diklik |
| `description` | VARCHAR(255) NULL | keterangan yang mudah dibaca |
| `target_url` | VARCHAR(255) NULL | URL tujuan |
| `ip_address` | VARCHAR(45) NULL | IP klien |
| `user_agent` | VARCHAR(1000) NULL | user agent |
| `created_at` | TIMESTAMP | default `CURRENT_TIMESTAMP` |

Index: `(user_id, created_at)`, `(activity, created_at)`, `(app_key, created_at)`.

---

## 3. Kode aktivitas

Login & sesi: `login_success`, `login_google_success`, `login_nfc_success`, `login_failed`,
`logout`, `timeout`.

Navigasi: `open_dashboard`, `open_presensi`, `open_dms`, `open_arsip`, `open_pengadaan`,
`open_pm`, `open_hrd`, `open_webmail`, `open_wbs`, `open_cms`, `open_assessment`.

Lain-lain: `update_profile`, `access_denied`.

Label tampilan diambil dari `getActivityCatalog()` / `getActivityMeta()` di
`config/activity_log.php`, sehingga aplikasi baru otomatis mendapat label `Membuka <nama aplikasi>`.

### 3.1 Pengecualian user (Administrator)

Secara default **user_id = 1 (Administrator) tidak dicatat** aktivitasnya. Pengaturan ada di
`config/activity_log.php`:

```php
// Daftar user yang tidak dicatat
define('ACTIVITY_LOG_EXCLUDED_USER_IDS', [1]);

// Kode aktivitas yang tetap dicatat untuk user tersebut (kosong = semua diabaikan)
define('ACTIVITY_LOG_EXCLUDED_ALLOW', []);
```

Pengecekan dilakukan di `logUserActivity()` sehingga berlaku untuk semua jalur: login, logout,
kunjungan dashboard, klik aplikasi (`go.php`), update profil, dan `access_denied`. Kartu **Login per
pengguna** di halaman laporan juga memakai daftar yang sama, jadi akun tersebut tidak muncul di
rekap login. Bila login/logout Administrator masih ingin terekam, isi allow-list:

```php
define('ACTIVITY_LOG_EXCLUDED_ALLOW', ['login_success', 'login_google_success', 'logout', 'timeout']);
```

Log lama milik Administrator tetap ada di tabel; hapus bila tidak diperlukan:

```sql
DELETE FROM login_logs WHERE user_id = 1;
```

---

## 4. Instalasi & migrasi

Produksi masih memakai skema lama (`activity` ENUM). Jalankan **sekali** setelah deploy:

1. Login sebagai admin / Infokom / Direksi.
2. Buka `https://lju.co.id/dashboard/migrate_login_logs.php`.
3. Tekan **Jalankan Migrasi**. Halaman menampilkan status tiap langkah (dibuat / dilewati / gagal).
4. Hapus `migrate_login_logs.php` setelah selesai.

Migrasi bersifat idempoten: kolom dan index yang sudah ada dilewati. Yang diubah:

- `activity` ENUM → `VARCHAR(50)`
- `user_id` → boleh NULL
- tambah `app_key`, `app_name`, `description`, `target_url`
- `user_agent` → `VARCHAR(1000)`
- tambah tiga index laporan

Alternatif manual: jalankan blok SQL di `login_logs_schema.sql` lewat phpMyAdmin.

**Sebelum migrasi dijalankan** portal tetap berjalan normal: `logUserActivity()` mendeteksi error
skema lalu menulis ulang dengan format kolom lama (kode `open_*` dipetakan ke `login_success`) dan
mencatat peringatan di `error_log`. Tidak ada halaman yang error, tetapi detail aplikasi belum
tersimpan sampai migrasi dijalankan.

---

## 5. Halaman laporan

`activity_logs.php` — hanya untuk admin, Infokom, dan Direksi (selain itu HTTP 403).

- KPI hari ini: total aktivitas, login, pengguna aktif, akses ditolak.
- Filter: pengguna (nama/username/ID), aplikasi, aktivitas, rentang tanggal + preset 1/7/30 hari.
- Daftar aplikasi teratas hari ini.
- **Login per pengguna**: jumlah login (password, Google, NFC) pada periode yang sedang dipilih
  (Hari ini / 7 hari / 30 hari / rentang tanggal manual), urut dari yang terbanyak, lengkap dengan
  jabatan dan total keseluruhan. Akun yang terdaftar di `ACTIVITY_LOG_EXCLUDED_USER_IDS` tidak
  dihitung, sehingga angka berbeda sedikit dengan KPI "Login hari ini" bila masih ada baris lama
  milik akun tersebut.
- Peringatan + tautan ke halaman migrasi bila skema belum lengkap.
- Pagination 50 baris; parameter filter dipertahankan saat pindah halaman.

Data user yang online (fungsi `getActiveUserCount()` / `getOnlineUsersList()` di
`config/database.php`) kini memakai aktivitas terakhir: user dianggap online bila aktivitas
terakhirnya hari ini dan bukan `logout`, `timeout`, atau `login_failed`. Jadi klik aplikasi tidak
lagi membuat user hilang dari daftar online.

---

## 6. Mencatat aktivitas dari kode lain

Semua helper ada di `config/activity_log.php`.

```php
require_once __DIR__ . '/config/activity_log.php';

// 1. Event bebas
logUserActivity($_SESSION['user_id'], 'export_laporan', [
    'app_key'     => 'dms',
    'app_name'    => 'Naskah Dinas',
    'description' => 'Export daftar naskah ke PDF',
    'target_url'  => '/dms/export.php',
]);

// 2. Membuka aplikasi (dipakai go.php)
logAppOpen('dms', getAppByKey('dms'), $_SESSION['user_id'], 'klik tombol dashboard');

// 3. Event yang mudah ter-refresh, cukup sekali per N detik per sesi
logEventOnce('open_dashboard', 900, [
    'app_key'     => 'dashboard',
    'app_name'    => 'Dashboard Portal',
    'description' => 'Membuka dashboard portal',
]);
```

Membaca log:

```php
$result = getActivityLogs([
    'user'      => 'budi',          // nama / username / ID
    'app'       => 'dms',           // app_key
    'activity'  => '',              // kode aktivitas
    'date_from' => '2026-11-01',
    'date_to'   => '2026-11-18',
], 50, 0);

$total = countActivityLogs($filters);
$stats = getActivityLogStats();      // today_total, today_logins, today_users, today_denied, top_apps
```

Contoh query laporan langsung di SQL ada di bagian bawah `login_logs_schema.sql`.

---

## 7. Aplikasi baru

1. Tambahkan entri di `getAppsConfig()` (`config/apps.php`) dengan `name`, `url`, `active`, dan
   `icon_active`/`icon_inactive`.
2. Tambahkan konstanta URL bila perlu (`APP_URL_*`).
3. Kartu dashboard otomatis memakai `go.php?app=<key>` sehingga kliknya langsung tercatat.
4. Bila aplikasi hanya untuk bidang tertentu, tambahkan aturan di `getAppAccessState()`; percobaan
   akses akan tercatat sebagai `access_denied`.

Aplikasi yang hanya tampil sebagai tombol cepat (bukan kartu grid) ditandai `'hidden' => true`,
contohnya `presensi` di topbar.

---

## 8. Berkas terkait

| File | Fungsi |
|---|---|
| `config/activity_log.php` | helper pencatatan + query laporan |
| `go.php` | pencatat klik aplikasi + redirect |
| `activity_logs.php` | halaman laporan aktivitas |
| `migrate_login_logs.php` | migrasi skema sekali jalan |
| `login_logs_schema.sql` | skema & contoh query |
| `config/apps.php` | daftar aplikasi, hak akses, helper `getAppByKey`/`getAppByUrl` |
| `views/dashboard.php` | kartu aplikasi, tombol topbar, pencatatan kunjungan dashboard |
| `config/session.php` | timeout sesi + helper log |
| `index.php`, `google_callback.php`, `logout.php`, `update_profile.php` | titik pencatatan login/logout/profil |

---

## 9. Troubleshooting

- **Kolom detail kosong di laporan** → migrasi belum dijalankan; buka `migrate_login_logs.php`.
- **Klik aplikasi tidak tercatat** → pastikan kartu memakai `go.php?app=<key>`, bukan URL langsung.
- **User hilang dari daftar online** → cek aktivitas terakhirnya; `logout`/`timeout`/`login_failed`
  memang menandai offline.
- **Log dashboard berlimpah** → throttle 15 menit per sesi; ubah angka di `logDashboardView()` bila
  perlu.
- **Aktivitas Administrator ikut tercatat** → periksa `ACTIVITY_LOG_EXCLUDED_USER_IDS` di
  `config/activity_log.php`; tambahkan juga user lain yang ingin dikecualikan ke daftar tersebut.
