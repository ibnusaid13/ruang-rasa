# Ruang Rasa — Kit Update E-Commerce (Laravel + MySQL, Laragon)

Kit ini berisi file yang **ditimpa/ditambahkan** ke project Laravel 11/12.
Semua file ditulis dengan struktur path yang sama seperti project Laravel.

> **Status verifikasi (jujur):** kode ditulis dan dicek sintaks PHP (`php -l`) di lingkungan tanpa akses
> Composer/MySQL, sehingga **migration, seeder, dan 20 tes belum pernah dijalankan**. Jalankan langkah
> di bawah, lalu `php artisan test`. Jika ada error, kirim pesannya agar diperbaiki.

---

## 1. Persiapan (Laragon → Menu → Terminal / cmd)

```bat
cd C:\laragon\www
composer create-project laravel/laravel ruang-rasa
cd ruang-rasa
```

> Jika Anda **sudah punya** project Ruang Rasa, jangan buat baru. Gunakan project itu dan lihat bagian "Menimpa file".

Buat database lewat HeidiSQL (Laragon → Database) atau:

```bat
mysql -u root -e "CREATE DATABASE ruang_rasa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 2. Salin file kit

Salin seluruh isi folder kit ke root project (pertahankan struktur folder):

```bat
xcopy /E /Y /I path\ke\ruang-rasa-kit\* C:\laragon\www\ruang-rasa\
```

**File default Laravel yang akan tertimpa:** hanya `routes/web.php` (isi bawaan hanya route `welcome`).
Jika project lama Anda punya route/layout sendiri, **backup dulu** dan gabungkan manual. Semua file lain di kit adalah file baru.

Jika landing page lama Anda sudah ada: pertahankan desainnya, lalu cukup tambahkan tombol Panel Admin, badge keranjang, dan form
`js-add-cart` dari `resources/views/layouts/app.blade.php` dan `partials/product-card.blade.php`.

## 3. Konfigurasi `.env`

Buka `.env` dan terapkan nilai dari `.env.example.snippet` (DB, APP_URL, metode pembayaran).
Jalankan `php artisan key:generate` bila `APP_KEY` kosong.

## 4. Migration, seeder, storage

```bat
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Buka http://127.0.0.1:8000 (atau http://ruang-rasa.test jika auto virtual host Laragon aktif).

`storage:link` wajib agar foto produk terlihat. Bukti pembayaran **tidak** lewat link ini: disimpan privat di `storage/app/private`.

## 5. Foto produk

Seeder membuat produk contoh **tanpa foto** (placeholder huruf). Unggah foto asli berkualitas tinggi lewat
**Panel Admin → Produk → Edit**. Hindari memakai foto milik orang lain tanpa izin.

## 6. Menjalankan tes

Tes memakai SQLite in-memory bawaan Laravel (tidak menyentuh database MySQL Anda). Pastikan ekstensi **`pdo_sqlite`** dan **`gd`** aktif di PHP Laragon
(Menu → PHP → Extensions).

```bat
php artisan test --filter=ShopFlowTest
```

Pemetaan tes ke requirement bagian I:

| # | Requirement | Tes |
|---|---|---|
| 1 | Produk masuk keranjang DB | `test_product_is_saved_into_database_cart` |
| 2 | Keranjang bertahan dengan token | `test_cart_persists_for_same_token_after_browser_restart` |
| 3 | Qty & total dihitung backend | `test_quantity_and_totals_are_computed_by_backend` |
| 4 | Stok tidak melebihi | `test_cannot_add_more_than_available_stock` |
| 5 | Order & items konsisten | `test_checkout_stores_order_items_and_payment_consistently` |
| 6 | Dua checkout tidak oversell | `test_two_checkouts_cannot_oversell_the_same_stock` |
| 7 | Status awal pending | `test_new_order_is_pending_before_verification` |
| 8 | Bukti ≠ otomatis valid | `test_uploading_proof_does_not_mark_payment_as_paid` |
| 9 | Paid hanya via jalur benar | `test_paid_status_only_through_verified_paths` + webhook + COD |
| 10 | Struk benar | `test_receipt_shows_exact_order_details` |
| 11 | UUID tak bisa ditebak | `test_orders_cannot_be_accessed_by_guessing_sequential_ids` |
| 12 | Panel Admin tanpa login | `test_admin_panel_button_opens_dashboard_without_login` |

**Catatan #6:** PHPUnit berjalan satu proses, sehingga tes ini mensimulasikan dua pembeli berebut stok terakhir secara berurutan.
Perlindungan konkurensi sebenarnya ada di `CheckoutService` (`lockForUpdate` pada keranjang & produk di dalam `DB::transaction`,
plus `decrement` bersyarat `stock >= qty`). Uji paralel sungguhan butuh MySQL + dua proses (mis. dua tab/`ab`), tidak tercakup di sini.

## 7. Panduan uji manual (belanja → struk)

1. Buka `/products`, klik **+ Keranjang**. Toast muncul dan badge keranjang bertambah.
2. Tutup browser, buka lagi: badge & `/cart` masih terisi (cek tabel `carts` & `cart_items` di HeidiSQL).
3. Di `/cart` ubah qty / hapus. Coba qty melebihi stok → ditolak dengan pesan.
4. **Lanjut Checkout**, isi data, pilih metode. Setelah **Buat Pesanan** Anda diarahkan ke `/orders/{uuid}`.
5. Status awal: *Menunggu Pembayaran*. Unggah bukti (transfer) → *Menunggu Verifikasi*. Belum "Pembayaran Berhasil".
6. **Panel Admin → Pembayaran → Detail → Setujui**. Status menjadi *Pembayaran Berhasil*, pesanan *Diproses*.
7. Ubah status pesanan: Diproses → Siap → Selesai. Dashboard menampilkan total penjualan.
8. Buka **Lihat Struk → Cetak Struk** (Ctrl+P, kertas A4).
9. Isi data rekening/QRIS di **Panel Admin → Pengaturan** agar muncul di halaman pesanan/checkout.

## 8. Alur status

- Pembayaran: `pending → awaiting_verification → paid | rejected` (rejected bisa unggah ulang), `cancelled`, `failed`, `refunded` (label ada, fitur refund **belum** dibuat).
- Pesanan: `pending → processing → ready → completed`; `cancelled` hanya dari pesanan yang belum lunas (stok dikembalikan).
- Non-COD hanya bisa masuk *Diproses* setelah pembayaran `paid`. COD menjadi `paid` saat pesanan diubah ke *Selesai* (uang diterima saat serah terima).

## 9. Keamanan — baca ini

- **Panel admin TANPA login** (`/admin/*`) sesuai permintaan demo tugas kuliah. Ini **tidak aman untuk deployment publik**: siapa pun yang
  tahu URL-nya dapat mengubah produk, melihat data pelanggan & bukti pembayaran, dan menyetujui pembayaran. Jalankan hanya lokal/jaringan tertutup.
  Sebelum online, tambahkan autentikasi (mis. middleware `auth` + role admin) pada grup route `admin`.
- Jangan mengisi data rekening/QRIS **asli** pada server yang bisa diakses publik selama admin tanpa login.
- Cookie `rr_cart` hanya berisi UUID acak (terenkripsi, HttpOnly, SameSite=Lax), tanpa data pribadi/pembayaran.
- Secret gateway hanya di `.env`. Tidak ada secret di Blade/JS.
- Tautan `/orders/{uuid}` berfungsi sebagai "kunci": siapa pun yang memegang tautan bisa melihat pesanan itu. Tidak ada daftar order publik.
- **Retensi keranjang:** keranjang anonim tersimpan selama cookie ada (1 tahun) dan **tidak sinkron antarperangkat**.
  Belum ada job pembersih; untuk produksi tambahkan scheduled command yang menghapus keranjang aktif lama.

## 10. Daftar fitur

**Berjalan penuh (setelah migrate; belum diuji di sini):**
- Keranjang persisten di MySQL (token UUID di cookie), toast, badge, qty, hapus, validasi stok/aktif.
- Checkout dengan validasi backend, hitung ulang total, transaksi DB + `lockForUpdate`, snapshot item, nomor order unik, keranjang → `checked_out`.
- Transfer manual + upload bukti privat + verifikasi/penolakan admin; COD.
- Struk cetak A4 (`@media print`), halaman pesanan via UUID.
- Panel Admin: dashboard (statistik + grafik 7 hari), CRUD produk + foto, Best Seller, pesanan + alur status, pembayaran, pesan kontak, pengaturan (rekening, ongkir demo, QRIS, info usaha).

**Butuh konfigurasi/pekerjaan eksternal:**
- **QRIS:** harus unggah gambar QRIS resmi milik usaha di Pengaturan + `PAY_QRIS_ENABLED=true`. Tanpa itu opsi tidak muncul.
- **Rekening bank:** isi data asli di Pengaturan (seeder sengaja mengosongkannya).
- **Payment gateway:** hanya **kerangka** (`PaymentGateway`, `HmacGateway`, `/webhooks/payment` dengan verifikasi HMAC + idempotensi).
  Ini bukan integrasi provider nyata. Anda harus membuat adapter sesuai dokumentasi resmi provider (Midtrans/Xendit/dll.), mengisi
  `PAYMENT_WEBHOOK_SECRET`, dan menyalakan `PAY_GATEWAY_ENABLED=true`. Sebelum itu, tidak ada klaim pembayaran uang nyata otomatis.
- **Ongkir:** tarif demo tunggal yang bisa diubah; belum terhubung ekspedisi.
- **Refund, CRUD kategori, notifikasi WhatsApp/email:** belum dibuat.
