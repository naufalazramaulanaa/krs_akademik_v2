# KRS & Enrollment Management System (Large-Scale Dataset)

Aplikasi Manajemen KRS & Enrollment mahasiswa yang dirancang untuk menangani dataset berskala besar (5+ juta data) secara efisien, responsif, dan terstruktur.

---

## 🚀 Panduan Setup Local

### Prerequisites
* PHP >= 8.2 & Composer
* Node.js >= 18 & npm/pnpm/bun
* MySQL / PostgreSQL Database

### 1. Backend Setup (Laravel) & Next JS
```bash
# Clone repository
git clone <(https://github.com/naufalazramaulanaa/krs_akademik_v2.git)>
cd backend

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Konfigurasi database di file .env
# DB_DATABASE=db_krs
# DB_USERNAME=root
# DB_PASSWORD=

# Jalankan server
php artisan serve

-------------------------------

cd frontend

# Install dependencies
npm install

# Environment setup
cp .env.example .env.local
# Ubah NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api

# Jalankan server development
npm run dev
