# PROJECT RULES — Akademik KRS Single Page CRUD (5M Rows)

> File ini adalah **aturan wajib (rule set)** untuk seluruh sesi pengerjaan.
> Agent WAJIB membaca ulang file ini sebelum membuat/mengubah file apa pun.
> Jika ada konflik antara permintaan ad-hoc dan file ini, **file ini menang**, kecuali user secara eksplisit menyatakan "override rule".

---

## 0. Konteks & Definisi Sukses

Proyek ini adalah **tes teknis rekrutmen** dengan deadline ketat. Penilaian bukan pada jumlah fitur, tapi pada:

1. Kebenaran fungsional (pagination / sort / filter / search / export **server-side**).
2. Kualitas query & performa di skala **5.000.000 baris**.
3. Atomic transaction insert ke **3 tabel** dalam 1 transaksi.
4. Validasi ketat di **frontend DAN backend** (bukan salah satu).
5. UX jelas (loading state, error message per field).
6. Kualitas kode + README yang **reproducible** oleh reviewer.

**Definisi selesai (Definition of Done):** reviewer bisa `git clone` → ikuti README → migrate → seed 5 juta → aplikasi jalan, DAN ada URL deploy yang bisa diklik.

---

## 1. Tech Stack — TIDAK BOLEH DIUBAH

| Layer | Teknologi | Versi target |
|---|---|---|
| Frontend | Next.js (App Router) + TypeScript | 15.x |
| UI | TailwindCSS + shadcn/ui | latest |
| Data fetching | TanStack Query (React Query) v5 | 5.x |
| Table | TanStack Table v8 (headless, manual mode) | 8.x |
| Form & validasi FE | react-hook-form + Zod | latest |
| Backend | Laravel (API-only, stateless) | 11.x / 12.x |
| Database | **PostgreSQL** | 16.x |
| Ekstensi DB | `pg_trgm` (wajib), `pgcrypto` (opsional) | — |
| Container | Docker + Docker Compose | — |

**Larangan stack:**
- ❌ Jangan pakai Inertia, Livewire, atau Blade untuk UI utama. Frontend murni Next.js yang memanggil REST API Laravel.
- ❌ Jangan pakai Laravel Sanctum/auth berat. Tes ini tidak meminta autentikasi; cukup CORS aman + rate limit.
- ❌ Jangan pakai ORM query yang memuat seluruh dataset ke memori (`->get()`, `->all()` tanpa limit) di jalur list/export.
- ❌ Jangan pakai `faker` per-baris untuk seeding 5 juta data (terlalu lambat).

---

## 2. Struktur Repository (Monorepo)

```
krs-akademik/
├── README.md                  # WAJIB, dokumen utama penilaian
├── docker-compose.yml         # postgres + backend + frontend
├── .env.example
├── docs/
│   ├── ARCHITECTURE.md
│   ├── PERFORMANCE.md         # strategi index, pagination, export
│   ├── API.md                 # kontrak endpoint
│   └── postman_collection.json
├── backend/                   # Laravel
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/
│   │   ├── Services/          # business logic (EnrollmentService, ExportService)
│   │   ├── Support/Query/     # FilterCompiler, SortCompiler (whitelist)
│   │   ├── Models/
│   │   └── Console/Commands/  # SeedMassiveCommand
│   └── database/migrations/
└── frontend/                  # Next.js
    ├── src/app/
    ├── src/components/
    ├── src/features/enrollments/
    ├── src/lib/api.ts
    └── src/lib/schemas/       # Zod schema (mirror dari backend rules)
```

---

## 3. Skema Database (WAJIB)

### 3.1 Tabel inti

**students**
| kolom | tipe | constraint |
|---|---|---|
| id | BIGSERIAL | PK |
| nim | VARCHAR(12) | UNIQUE, NOT NULL, 8–12 digit angka |
| name | VARCHAR(100) | NOT NULL, min 3 |
| email | VARCHAR(150) | UNIQUE, NOT NULL |
| created_at / updated_at | TIMESTAMPTZ | |

**courses**
| kolom | tipe | constraint |
|---|---|---|
| id | BIGSERIAL | PK |
| code | VARCHAR(7) | UNIQUE, NOT NULL, regex `^[A-Z]{2,4}[0-9]{3}$` |
| name | VARCHAR(120) | NOT NULL, min 3 |
| credits | SMALLINT | NOT NULL, 1–6 |
| created_at / updated_at | TIMESTAMPTZ | |

**enrollments**
| kolom | tipe | constraint |
|---|---|---|
| id | BIGSERIAL | PK |
| student_id | BIGINT | FK → students.id, RESTRICT on delete |
| course_id | BIGINT | FK → courses.id, RESTRICT on delete |
| academic_year | VARCHAR(9) | NOT NULL, format `YYYY/YYYY` |
| semester | semester_enum | NOT NULL (`GANJIL`/`GENAP`) |
| status | status_enum | NOT NULL (`DRAFT`/`SUBMITTED`/`APPROVED`/`REJECTED`) |
| student_nim | VARCHAR(12) | **kolom snapshot (denormalisasi)**, NOT NULL |
| student_name | VARCHAR(100) | **kolom snapshot**, NOT NULL |
| course_code | VARCHAR(7) | **kolom snapshot**, NOT NULL |
| course_name | VARCHAR(120) | **kolom snapshot**, NOT NULL |
| deleted_at | TIMESTAMPTZ NULL | soft delete |
| created_at / updated_at | TIMESTAMPTZ | |

**Unique constraint wajib:** `UNIQUE (student_id, course_id, academic_year, semester)` (partial: `WHERE deleted_at IS NULL`).

### 3.2 Aturan denormalisasi (WAJIB DIDOKUMENTASIKAN)

Kolom `student_nim`, `student_name`, `course_code`, `course_name` disalin ke `enrollments` **dengan sengaja**, alasan:
- Sorting/filtering/searching pada kolom tersebut di skala 5 juta baris tidak akan memaksa JOIN + sort eksternal.
- Memungkinkan composite index dan GIN trigram langsung di satu tabel.
- Export 5 juta baris bisa dilakukan tanpa JOIN.

**Konsistensi wajib dijaga:** setiap update `students.name/nim` atau `courses.code/name` harus mem-propagate ke `enrollments` **di dalam transaksi yang sama**. Jelaskan trade-off ini di `README.md` dan `docs/PERFORMANCE.md`. FK tetap ada dan tetap divalidasi — denormalisasi TIDAK menggantikan relasi.

### 3.3 Index wajib

```sql
-- keyset & default sort
CREATE INDEX idx_enr_id ON enrollments (id) WHERE deleted_at IS NULL;
-- quick filter
CREATE INDEX idx_enr_status_sem_year ON enrollments (status, semester, academic_year, id) WHERE deleted_at IS NULL;
CREATE INDEX idx_enr_year_sem_nim ON enrollments (academic_year DESC, semester ASC, student_nim ASC) WHERE deleted_at IS NULL;
-- prefix / startsWith search
CREATE INDEX idx_enr_nim_pattern ON enrollments (student_nim varchar_pattern_ops);
CREATE INDEX idx_enr_code_pattern ON enrollments (course_code varchar_pattern_ops);
-- contains search (ILIKE %x%)
CREATE INDEX idx_enr_name_trgm ON enrollments USING GIN (student_name gin_trgm_ops);
CREATE INDEX idx_enr_nim_trgm  ON enrollments USING GIN (student_nim gin_trgm_ops);
CREATE INDEX idx_enr_code_trgm ON enrollments USING GIN (course_code gin_trgm_ops);
-- FK
CREATE INDEX idx_enr_student ON enrollments (student_id);
CREATE INDEX idx_enr_course  ON enrollments (course_id);
```

Setiap index harus disertai komentar alasan di file migration.

---

## 4. Aturan Backend (Laravel)

### 4.1 Prinsip
- Controller **tipis**; seluruh logic di `Services/`.
- Setiap request masuk lewat **FormRequest** (validasi) → Service → Resource (output).
- Semua query yang menyentuh input user WAJIB **parameterized** (`DB::select($sql, $bindings)`), tidak ada string concatenation nilai user.
- Nama kolom & arah sort WAJIB lewat **whitelist array**, bukan langsung dari request.
- Response error konsisten:
  ```json
  { "message": "...", "errors": { "field": ["..."] } }
  ```
  dengan HTTP 422 (validasi), 404 (not found), 409 (konflik unik), 500 (server).

### 4.2 Endpoint wajib

| Method | Path | Fungsi |
|---|---|---|
| GET | `/api/enrollments` | list: pagination, sort multi kolom, quick filter, search, advanced filter |
| POST | `/api/enrollments` | create atomic 3 tabel |
| GET | `/api/enrollments/{id}` | detail |
| PUT | `/api/enrollments/{id}` | update enrollment (+opsional student/course) |
| DELETE | `/api/enrollments/{id}` | soft delete |
| GET | `/api/enrollments/export` | streaming CSV seluruh hasil query |
| GET | `/api/students/lookup?q=` | autocomplete pilih student existing |
| GET | `/api/courses/lookup?q=` | autocomplete pilih course existing |
| GET | `/api/meta` | enum status/semester/academic_year untuk FE |

### 4.3 Kontrak query list (WAJIB)

```
GET /api/enrollments
  ?page=1&page_size=25
  &sort=[{"field":"academic_year","dir":"desc"},{"field":"semester","dir":"asc"}]
  &search=2201
  &quick[status]=APPROVED&quick[semester]=GANJIL
  &filter={"logic":"AND","groups":[
      {"logic":"OR","conditions":[
        {"field":"student_nim","operator":"startsWith","value":"2201"},
        {"field":"course_code","operator":"contains","value":"IF"}
      ]},
      {"logic":"AND","conditions":[
        {"field":"academic_year","operator":"between","value":["2023/2024","2025/2026"]},
        {"field":"status","operator":"in","value":["APPROVED","SUBMITTED"]}
      ]}
  ]}
  &count=exact|estimate
```

- Operator yang didukung: `equals`, `notEquals`, `contains`, `startsWith`, `endsWith`, `in`, `notIn`, `between`, `gt`, `gte`, `lt`, `lte`, `isNull`, `isNotNull`.
- `FilterCompiler` wajib: validasi field terhadap whitelist, validasi operator terhadap tipe kolom, dan menolak (422) jika tidak valid. Kedalaman group maksimal 2 level; maksimal 20 kondisi per request.
- **Logika AND/OR berlaku pada filter group**, bukan pada ORDER BY. Wajib dijelaskan di README bagian "Interpretasi Advanced Order AND/OR" bahwa advanced order = multi-column ORDER BY berurutan, sedangkan AND/OR = kombinasi filter group.

### 4.4 Aturan performa list (KRITIS)

- **Jangan** menjalankan `COUNT(*)` penuh pada 5 juta baris di setiap request.
  - Default: `count=estimate` → gunakan `EXPLAIN (FORMAT JSON)` row estimate atau `pg_class.reltuples` bila tanpa filter.
  - `count=exact` hanya dijalankan bila estimate < 50.000 baris, atau bila user menekan tombol "hitung tepat".
  - Response wajib membawa `"total_is_estimate": true|false`.
- Sediakan **dua mode pagination**:
  - `offset` (default, untuk UI page number) — batasi maksimal `page * page_size <= 100000`, lewat itu arahkan user ke filter/keyset.
  - `keyset` (cursor by `id`) — dipakai untuk "next page" cepat dan untuk export.
- `page_size` dibatasi enum: 10, 25, 50, 100. Tolak nilai lain (422).
- Setiap endpoint list wajib mengembalikan `"query_time_ms"` untuk pembuktian performa.

### 4.5 Create — atomic 3 tabel (WAJIB)

```php
DB::transaction(function () use ($dto) {
    $student = $dto->studentMode === 'new'
        ? Student::create($dto->student)          // gagal → unique violation → rollback
        : Student::lockForUpdate()->findOrFail($dto->studentId);

    $course  = $dto->courseMode === 'new'
        ? Course::create($dto->course)
        : Course::lockForUpdate()->findOrFail($dto->courseId);

    return Enrollment::create([
        'student_id'   => $student->id,
        'course_id'    => $course->id,
        'student_nim'  => $student->nim,
        'student_name' => $student->name,
        'course_code'  => $course->code,
        'course_name'  => $course->name,
        ...$dto->enrollment,
    ]);
}, 3); // 3x retry untuk deadlock
```

- Form Create WAJIB mendukung dua mode per entitas: **"pilih yang sudah ada"** atau **"tambah baru"**, dan tetap melibatkan 3 tabel.
- WAJIB ada endpoint/flag uji rollback (mis. `?simulate_failure=enrollment`) **hanya di environment `local`**, untuk membuktikan TS-02. Dokumentasikan di README.
- Unique violation Postgres (SQLSTATE 23505) harus di-map ke HTTP 409 + pesan field yang jelas, bukan 500.

### 4.6 Export 5 juta baris (WAJIB)

Implementasi utama: **streaming CSV** via `COPY ... TO STDOUT`.

```php
return response()->streamDownload(function () use ($sql, $bindings) {
    $pdo = DB::connection()->getPdo();
    $stmt = $pdo->prepare($sql);   // SELECT hasil compile filter yang sama dengan list
    $stmt->execute($bindings);
    echo "NIM,Nama Mahasiswa,Kode MK,Nama MK,Semester,Tahun Ajaran,Status\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {   // unbuffered
        echo implode(',', array_map([Csv::class, 'escape'], $row)) . "\n";
        if (++$i % 5000 === 0) { flush(); }
    }
}, 'enrollments.csv', ['Content-Type' => 'text/csv', 'X-Accel-Buffering' => 'no']);
```

Aturan:
- WAJIB `PDO::ATTR_EMULATE_PREPARES = false` dan **tidak** memakai `->get()`/`->chunk()` untuk export penuh.
- Alternatif yang boleh ditambahkan (nilai plus): queue job (`php artisan queue:work`) → simpan file ke storage → endpoint download link + status polling. Jika dipakai, tetap sediakan mode streaming.
- Export WAJIB menghormati filter/search/sort yang aktif (gunakan compiler yang sama dengan endpoint list — jangan duplikasi logic).
- `set_time_limit(0)` dan nonaktifkan output buffering. Nginx: `proxy_buffering off` untuk route export.
- Dokumentasikan estimasi waktu & ukuran file 5 juta baris di `docs/PERFORMANCE.md` (hasil pengukuran nyata, bukan tebakan).

### 4.7 Seeder 5 juta baris (WAJIB)

Command: `php artisan seed:massive --students=200000 --courses=2000 --enrollments=5000000 --truncate`

Aturan implementasi:
1. Seed `courses` (2.000 baris) dan `students` (200.000 baris) dengan **bulk insert** berbasis `generate_series`, bukan faker per baris.
2. **Drop/disable index non-PK pada `enrollments` sebelum seeding**, buat ulang setelah selesai.
3. Generate enrollments **deterministik** agar unique constraint tidak pernah bentrok:
   - Setiap student mendapat 25 enrollment: `k = 0..24`
   - `course_id = ((student_id * 7 + k * 13) % 2000) + 1` → dijamin unik per student karena `k*13` distinct untuk k<154.
   - `academic_year` & `semester` diturunkan dari `k`.
4. Eksekusi per batch (mis. 10.000 student per iterasi) via `INSERT INTO ... SELECT ... FROM generate_series(...)` supaya memori PHP konstan.
5. Setelah selesai: `CREATE INDEX` + `ANALYZE enrollments;` (wajib, agar planner akurat).
6. Tampilkan progress bar + durasi total, dan cetak hasil `SELECT COUNT(*)` di akhir.
7. Target: selesai < 15 menit di mesin lokal biasa. Dokumentasikan waktu nyata.

---

## 5. Aturan Frontend (Next.js)

- Halaman utama **satu halaman** (`/`): tabel + toolbar + panel filter + modal create/edit. Tidak ada navigasi multi-halaman untuk CRUD.
- State query (page, page_size, sort, filter, search) disimpan di **URL search params** agar shareable & bisa di-reload.
- TanStack Table WAJIB mode manual: `manualPagination`, `manualSorting`, `manualFiltering` = `true`. Dilarang melakukan sort/filter di sisi klien.
- Search input: debounce **400ms**, dengan indikator "searching…" dan pembatalan request lama (AbortController / React Query `keepPreviousData`).
- Setiap header kolom: klik → ASC → DESC → none, dengan ikon indikator arah. Shift+klik = tambah kolom ke urutan sort (advanced multi-column order), dan panel "Sort builder" untuk menyusun urutan secara eksplisit + drag reorder.
- Advanced filter: builder visual dengan group AND/OR, tombol Reset dan "Clear all", serta preview JSON query (nilai plus untuk reviewer).
- Validasi form: **Zod schema** yang identik dengan rules backend, ditempatkan di `src/lib/schemas/enrollment.ts` dan diberi komentar referensi ke FormRequest yang bersangkutan.
- WAJIB tangani dan tampilkan error 422 dari backend ke field yang sesuai (`setError` react-hook-form), bukan sekadar toast generik.
- WAJIB ada: skeleton loading, empty state, error state dengan tombol retry, disabled state saat submit.
- Export: tombol memicu `window.location` ke endpoint export dengan query params saat ini + toast peringatan bahwa file besar sedang di-stream.
- Aksesibilitas dasar: label pada semua input, `aria-sort` pada header, focus ring terlihat.
- Tampilan harus **responsif** (tabel horizontal scroll di mobile) dan mendukung dark mode.

---

## 6. Validasi — Sinkron FE & BE

| Field | Aturan |
|---|---|
| `students.nim` | required, unik, `^\d{8,12}$`, tanpa spasi |
| `students.name` | required, 3–100 karakter |
| `students.email` | required, email valid, unik |
| `courses.code` | required, unik, `^[A-Z]{2,4}\d{3}$` |
| `courses.name` | required, 3–120 karakter |
| `courses.credits` | required, integer 1–6 |
| `enrollments.academic_year` | required, `^\d{4}/\d{4}$` dan tahun kedua = tahun pertama + 1 |
| `enrollments.semester` | required, in `GANJIL,GENAP` |
| `enrollments.status` | required, in `DRAFT,SUBMITTED,APPROVED,REJECTED` |
| kombinasi | unik: (student_id, course_id, academic_year, semester) |

Backend WAJIB tetap menolak payload invalid meski frontend sudah memvalidasi (uji via curl/Postman, TS-04).

---

## 7. Keamanan & Kualitas Kode

- CORS: hanya origin frontend (env `FRONTEND_URL`), bukan `*`.
- Rate limit pada endpoint export dan list (`throttle:60,1`).
- Tidak ada raw SQL yang menyisipkan nilai user; hanya nama kolom dari whitelist yang boleh masuk ke SQL string.
- Laravel Pint + PHPStan (level 5) untuk backend; ESLint + Prettier + `tsc --noEmit` untuk frontend. Semua harus lolos tanpa error.
- Logging: request id + durasi query untuk endpoint list/export.
- `.env.example` lengkap, tidak ada credential asli di repo.
- Commit message konvensional (`feat:`, `fix:`, `perf:`, `docs:`), commit bertahap per fitur — bukan satu commit raksasa.

---

## 8. Dokumentasi (README) — Struktur Wajib

1. Ringkasan & screenshot/GIF aplikasi
2. Link demo online + link repo
3. Tech stack & alasan pemilihan
4. Arsitektur (diagram sederhana)
5. Skema DB + ERD + daftar index dan alasannya
6. **Setup lokal**: prasyarat, `.env`, `docker compose up`, migrate, seed
7. **Cara seeding 5 juta data** + cara membuktikan (`SELECT COUNT(*) FROM enrollments;`) + waktu eksekusi nyata
8. Strategi performa: indexing, estimate count, keyset pagination, streaming export, trigram search
9. Penjelasan transaksi atomic 3 tabel + cara menguji rollback
10. Penjelasan pilihan **soft delete** dan dampaknya
11. Interpretasi **Advanced Order & AND/OR** (sesuai §4.3)
12. Kontrak API + contoh curl tiap endpoint
13. Panduan deploy
14. **Pemetaan TS-01 s/d TS-13 → cara mengujinya** (tabel: test case | langkah | hasil yang diharapkan | bukti)
15. Asumsi & limitasi yang diketahui (jujur, jangan diklaim lebih dari kenyataan)

---

## 9. Deployment

- Frontend: Vercel (env `NEXT_PUBLIC_API_URL`).
- Backend + PostgreSQL: satu VPS (Docker Compose, Nginx reverse proxy) — **direkomendasikan**, karena 5 juta baris (± 1–1,5 GB + index) melebihi kuota free tier umum.
- Alternatif: Railway/Render (backend) + Neon/Supabase paid (DB). Jika kuota DB tidak mencukupi, **jangan mengklaim 5 juta di produksi**; seed sesuai kapasitas, cantumkan jumlah nyata di README, sertakan bukti 5 juta dari lokal (screenshot `COUNT(*)` + waktu query). Kejujuran ini bagian dari penilaian.
- WAJIB: endpoint `/api/health` mengembalikan status DB + jumlah baris enrollments.

---

## 10. Urutan Pengerjaan (Agent WAJIB mengikuti fase ini)

1. **Fase 0** — Scaffold monorepo, docker-compose (postgres+pg_trgm), .env.example, health endpoint.
2. **Fase 1** — Migration 3 tabel + enum + unique constraint + FK. Belum ada index tambahan.
3. **Fase 2** — Seeder massive + command count. **Uji dengan 100.000 baris dulu**, baru 5 juta.
4. **Fase 3** — Index + `ANALYZE` + dokumentasi `EXPLAIN ANALYZE` sebelum/sesudah index.
5. **Fase 4** — Endpoint list (pagination + sort + quick filter + search + advanced filter) dengan FilterCompiler & SortCompiler.
6. **Fase 5** — Create/Update/Delete + transaksi atomic + FormRequest.
7. **Fase 6** — Export streaming CSV.
8. **Fase 7** — Frontend: tabel + toolbar + search + quick filter + sorting.
9. **Fase 8** — Frontend: advanced filter builder + sort builder + modal create/edit + Zod.
10. **Fase 9** — Polish UX, lint, error handling, dokumentasi lengkap.
11. **Fase 10** — Deploy + isi tabel pemetaan TS-01..TS-13 di README.

Di akhir setiap fase: jalankan build/lint, laporkan singkat apa yang selesai, dan **berhenti untuk konfirmasi** sebelum lanjut ke fase berikutnya.

---

## 11. Aturan Perilaku Agent

- Jangan menulis kode untuk fitur yang tidak ada di dokumen tes. Tidak ada auth, tidak ada multi-tenant, tidak ada chart/dashboard tambahan.
- Jangan mengarang angka performa. Setiap klaim performa di README harus berasal dari eksekusi nyata (`EXPLAIN ANALYZE`, `\timing`).
- Jika sebuah requirement tidak bisa dipenuhi (mis. kuota hosting), **katakan jelas**, jangan diam-diam dikurangi.
- Jangan membuat file placeholder kosong atau TODO yang dibiarkan; setiap file yang dibuat harus fungsional.
- Prioritaskan: requirement wajib selesai 100% > fitur nilai plus. Fitur opsional hanya dikerjakan jika semua wajib sudah lolos.
