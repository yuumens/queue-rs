# Sistem Antrian Pasien (Patient Queue System)

Aplikasi web untuk mengelola pendaftaran pasien dan sistem antrian di fasilitas kesehatan. Dibangun dengan Laravel 12, Blade + TailwindCSS, dan Livewire v3.

## Fitur

- **Pendaftaran Pasien Baru** — Input data pasien, generate nomor rekam medis otomatis (format `RM-000001`)
- **Verifikasi Pasien Lama** — Cari pasien berdasarkan nomor rekam medis atau nama
- **Pemilihan Poliklinik & Dokter** — Tampilkan poliklinik dan dokter yang tersedia hari ini berdasarkan jadwal praktik
- **Nomor Antrian Otomatis** — Generate nomor antrian unik per poliklinik per hari (format `A-01`, `A-02`, dst.)
- **Cetak Tiket Antrian** — Tampilkan dan cetak tiket antrian dengan data lengkap
- **Panel Admin** — CRUD poliklinik, dokter, dan jadwal praktik (dilindungi autentikasi)

## Tech Stack

- **Backend:** Laravel 12, PHP 8.3+
- **Database:** MySQL
- **Frontend:** Blade templates, TailwindCSS v4, Livewire v3
- **Build Tool:** Vite

## Prasyarat

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- MySQL 8.0+

## Instalasi

```bash
# Clone repository
git clone <repository-url> queue-rs
cd queue-rs

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Konfigurasi database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=queue_rs
# DB_USERNAME=root
# DB_PASSWORD=

# Buat database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS queue_rs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migrasi
php artisan migrate

# Jalankan seeder (opsional, untuk data contoh)
php artisan db:seed
```

## Menjalankan Aplikasi

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (untuk TailwindCSS & asset compilation)
npm run dev
```

Akses aplikasi di `http://localhost:8000`

## Rute Utama

| URL | Deskripsi |
|-----|-----------|
| `/` | Halaman utama (pilih Pasien Baru / Pasien Lama) |
| `/register` | Form pendaftaran pasien baru |
| `/verify` | Verifikasi pasien lama (cari by RM / nama) |
| `/queue/select` | Pilih poliklinik & dokter |
| `/queue/ticket/{id}` | Tampilkan tiket antrian |
| `/admin/polyclinics` | Kelola poliklinik |
| `/admin/doctors` | Kelola dokter |
| `/admin/practice-schedules` | Kelola jadwal praktik |
| `/login` | Login admin |

## Testing

```bash
# Jalankan seluruh test suite
php artisan test

# Jalankan test spesifik
php artisan test --filter=PatientServiceTest
php artisan test --filter=QueueServiceTest
php artisan test --filter=PatientSearchTest
```

Test menggunakan SQLite in-memory database (dikonfigurasi di `phpunit.xml`).

## Struktur Proyek

```
app/
├── Exceptions/          # DuplicateNikException
├── Http/
│   ├── Controllers/
│   │   ├── Admin/       # PolyclinicController, DoctorController, PracticeScheduleController
│   │   ├── Auth/        # LoginController
│   │   ├── PatientController.php
│   │   ├── QueueController.php
│   │   └── RegistrationController.php
│   ├── Middleware/       # EnsurePatientInSession
│   └── Requests/        # StorePatientRequest, StorePracticeScheduleRequest
├── Livewire/            # PatientSearch, PolyclinicDoctorSelector
├── Models/              # Patient, Polyclinic, Doctor, PracticeSchedule, Registration
├── Providers/           # AppServiceProvider
└── Services/            # PatientService, QueueService
```

## Alur Pendaftaran

### Pasien Baru
1. Pilih "Pasien Baru" di halaman utama
2. Isi form (nama, tanggal lahir, alamat, NIK)
3. Sistem generate nomor rekam medis otomatis
4. Pilih poliklinik dan dokter
5. Sistem generate nomor antrian
6. Cetak tiket antrian

### Pasien Lama
1. Pilih "Pasien Lama" di halaman utama
2. Cari berdasarkan nomor RM atau nama
3. Konfirmasi identitas pasien
4. Pilih poliklinik dan dokter
5. Sistem generate nomor antrian
6. Cetak tiket antrian

## Lisensi

MIT
