# EJT Backend — Main API (Laravel 13)

Backend / REST API untuk **East Java Traveling (EJT)** — platform pariwisata & ekosistem
travel Jawa Timur. Bertugas sebagai **gerbang utama (gateway)**: melayani seluruh akses dari
frontend (Vue SPA), mengelola data, auth (Sanctum), pembayaran (Midtrans), wallet/loyalty,
serta menjadi **proxy aman** ke microservice AI.

Berjalan terpisah dari Frontend (Vue 3, port 5174) dan **EJT AI Core** (FastAPI, port 5001)
agar pemrosesan AI tidak membebani API utama.

## Tech Stack
| Komponen | Pilihan |
|---|---|
| Framework | Laravel `^13.8` (terverifikasi v13.26) · PHP `^8.3` |
| Auth | Laravel Sanctum (token Bearer + cookie CSRF / `statefulApi`) |
| Payment | Midtrans PHP SDK (Snap / card) |
| Akses | spatie/laravel-permission + middleware role (`admin` / `manager` / `umkm`) |
| Gambar | intervention/image (galeri / thumbnail) |
| QR | simplesoftwareio/simple-qrcode (tiket / invoice) |
| DB | MySQL (default `jhic_db`) |
| Queue | Database driver (`QUEUE_CONNECTION=database`) |
| Test | PHPUnit (`^12.5`) + Laravel Pint |

## Komponen Produk
- **Destinasi wisata** — kategori (Pantai, Pegunungan, Budaya, dll.), galeri, fasilitas, biaya, review.
- **Hotel & Kamar** — listing, kamar, galeri, ketersediaan, booking.
- **Paket Wisata** — jadwal, penjemputan, galeri, booking + itinerary hari-2.
- **Transportasi** — rental kendaraan + booking; **Tiket Transport** (kereta/pesawat/bus, mock service).
- **Event**, **UMKM Marketplace** (produk, pesanan, approval admin).
- **Sistem Booking** terpadu (hotel / transportasi / tiket / paket / tiket destinasi) + QR + hold.
- **Wallet & EJTCoin** — top-up via Midtrans, transaksi coin (debit/kredit/earn/redeem/expire).
- **Loyalty** — tier (bronze/silver/gold/platinum), reward harian/login/review/referral, masa berlaku coin.
- **Voucher, Quest & Streak** (gamifikasi, 7 hari), **Wishlist & Koleksi** (share via token).
- **Trip Planner / Itinerary** — bagikan via token, dukungan **AI Smart Trip**.
- **Review, Invoice, Notifikasi, Refund, Pencarian terpadu, Price Tracking** (wishlist).
- **AI Chat (KAVI)** — `POST /ai/chat` (SSE) & `POST /ai/trip/plan` → proxy ke EJT AI Core.
- **Dashboard per role** — Admin, Manager, dan UMKM.

## Struktur
```
backend/
├── app/
│   ├── Console/Commands/       # TrackPrices, ExpireLoyaltyCoins, ExportDestinationsForAI
│   ├── Enums/                  # UserRole, BookingStatus, OrderStatus, dsb.
│   ├── Helpers/                # GeneralHelper (autoloaded)
│   ├── Http/
│   │   ├── Controllers/Api/    # API publik & user
│   │   │   ├── Admin/          # 16 controller admin
│   │   │   ├── Manager/        # 9 controller manager
│   │   │   ├── Umkm/           # 5 controller UMKM
│   │   │   └── Dashboard/      # DashboardAdmin / Umkm / User
│   │   └── Middleware/         # Admin, Manager, Umkm middleware + guard role
│   ├── Models/                 # 55 model Eloquent
│   ├── Observers/  Policies/  Providers/
│   ├── Services/               # AiService, Midtrans, Loyalty, Voucher, PriceTracking, dll.
│   │   └── TransportTicket/    # TransportTicketServiceInterface + Mock
│   └── Traits/
├── bootstrap/
├── config/                     # services.php (ai), midtrans.php, dsb.
├── database/
│   ├── migrations/             # ±60 migrasi
│   └── seeders/                # DataSeeder berurutan + akun test
├── routes/api.php              # seluruh route API (publik / sanctum / admin / manager / umkm)
└── docs/admin-api-reference.md # referensi API admin (2093 baris)
```

## Persona Role
| Role | Kemampuan (arah frontend dashboard) |
|---|---|
| `tourist` | Explore, wishlist, wallet, loyalty, booking, trip planner, chat AI |
| `umkm` | Dashboard & kelola produk, pesanan, profil |
| `manager` | Kelola destinasi/hotel/paket/transport/event/booking miliknya |
| `admin` | Kelola seluruh master data, user, order, voucher, kategori |

## Setup & Menjalankan
```bash
# 1. Install dependency & siapkan env (satu perintah)
composer setup
#    = composer install
#    + salin .env.example -> .env + php artisan key:generate
#    + php artisan migrate --force
#    + npm install + npm run build

# 2. Atur variabel kunci di .env (contoh nilai dev)
DB_DATABASE=jhic_db
DB_USERNAME=jhic_user
DB_PASSWORD=jhic_123

MIDTRANS_SERVER_KEY=...
MIDTRANS_CLIENT_KEY=...
MIDTRANS_IS_PRODUCTION=false

FRONTEND_URL=http://localhost:5174

# --- EJT AI Core (opsional, untuk chat/trip AI) ---
AI_BASE_URL=http://127.0.0.1:5001
AI_API_KEY=                  # = SHARED_SECRET di ai-service/.env
AI_TIMEOUT=0
AI_SYNC_ENABLED=false

# 3. Jalankan dev server (server + queue + log + vite sekaligus)
composer dev
```

Server API berjalan di **http://localhost:8000/api** (queue `database` → butuh `queue:listen`,
sudah otomatis di `composer dev`).

## Seed & Akun Test
```bash
php artisan db:seed --force
```
Semua akun test memakai password **`password123`**:

| Role | Email |
|---|---|
| Admin | `admin@ejt.com`, `superadmin@ejt.com` |
| Manager | `manager@ejt.com`, `manager2@ejt.com`, `manager3@ejt.com` |
| UMKM | `umkm@ejt.com` (+ `umkm2`–`umkm5@ejt.com`) |
| Tourist | `tourist@ejt.com`, `andi@gmail.com` … (+ 12 total tourist) |

Untuk mengisi data AI (RAG destinasi ke vector store):
```bash
php artisan destinations:export-ai          # -> storage/app/ai/destinations-ai.json
cd ../ai-service && ./scripts/backfill_destinations.sh
```

## Scheduler
Didaftarkan di `routes/console.php`:
| Perintah | Jadwal | Fungsi |
|---|---|---|
| `prices:track` | setiap 6 jam | snapshot harga wishlist & notifikasi penurunan |
| `loyalty:expire` | harian 03:00 | kadaluarsakan coin loyalty melewati masa berlaku |

## Test
```bash
composer test        # php artisan config:clear && php artisan test
# Fokus AI:
php artisan test --filter=AiChatControllerTest

# Ambil subset unit:
php artisan test --testsuite=Unit
```
> Catatan: test suite butuh DB test terpisah (di `phpunit.xml`, default `jhic_test`).

## Prinsip
- **AI tidak pernah memotong saldo / charge.** Booking & trip plan dari AI selalu berupa **draft**.
- **Keamanan:** endpoint mutasi (chat/trip) meneruskan shared secret `X-AI-Secret` ke FastAPI;
  request masuk hanya yang sudah divalidasi token Sanctum oleh frontend.
- Backend tidak memuat Vue SPA — frontend adalah **proyek terpisah** (`frontend/`, port 5174).

## File Terkait
- Route API: `routes/api.php`
- Konfigurasi AI: `config/services.php` → `ai`
- Konfigurasi payment: `config/midtrans.php`
- Proxy AI: `app/Services/AiService.php`
- Referensi API admin: `docs/admin-api-reference.md`
