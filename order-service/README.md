# Order Service

Service pemrosesan pesanan dan checkout untuk arsitektur microservices toko online (Laravel 11 & MySQL).

---

## 1. Daftar Endpoint API

| Method | Endpoint | Fungsi | Status Sukses | Status Gagal & Kondisi |
|:---:|---|---|:---:|---|
| `GET` | `/health` | Mengecek status service hidup | **200** | *(Tidak ada)* |
| `POST` | `/orders` | Membuat pesanan baru dari keranjang belanja | **201** | **422**: Body tanpa `user_id`<br>**409**: Keranjang kosong / stok kurang / produk tidak ada<br>**503**: `cart-service` / `product-service` mati<br>**500**: Error internal server |
| `GET` | `/orders/{id}` | Detail satu pesanan beserta item snapshot | **200** | **404**: Pesanan tidak ditemukan / ID bukan angka<br>**500**: Error internal server |
| `GET` | `/orders/user/{userId}` | Riwayat pesanan user (filter opsional `?status=`) | **200** | **422**: Status tidak valid (bukan `PENDING`/`PAID`)<br>**404**: `userId` bukan angka<br>**500**: Error internal server |
| `PATCH` | `/orders/{id}/pay` | Simulasi bayar: `PENDING` menjadi `PAID` | **200** | **404**: Pesanan tidak ditemukan / ID bukan angka<br>**409**: Status bukan `PENDING` (sudah `PAID`)<br>**500**: Error internal server |

---

## 2. Struktur Database

### Tabel `orders`
| Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | `BIGINT UNSIGNED (PK)` | Auto-increment ID pesanan |
| `user_id` | `BIGINT UNSIGNED` | ID user pemilik pesanan |
| `status` | `VARCHAR(255)` | Status pesanan (`PENDING` atau `PAID`) |
| `total_amount` | `DECIMAL(12, 2)` | Total nominal pesanan |
| `created_at` | `TIMESTAMP` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Waktu diperbarui |

### Tabel `order_items`
| Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | `BIGINT UNSIGNED (PK)` | Auto-increment ID item |
| `order_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `orders.id` (*cascade delete*) |
| `product_id` | `BIGINT UNSIGNED` | ID produk |
| `product_name` | `VARCHAR(255)` | Salinan nama produk saat transaksi (*snapshot*) |
| `unit_price` | `DECIMAL(12, 2)` | Salinan harga satuan produk (*snapshot*) |
| `quantity` | `INT UNSIGNED` | Kuantitas barang yang dipesan |
| `subtotal` | `DECIMAL(12, 2)` | Subtotal per item (`unit_price * quantity`) |
| `created_at` | `TIMESTAMP` | Waktu dibuat |
| `updated_at` | `TIMESTAMP` | Waktu diperbarui |

---

## 3. Konfigurasi Environment

| Variable | Default Docker | Deskripsi |
|---|---|---|
| `APP_URL` | `http://localhost:3003` | URL dasar order-service |
| `DB_CONNECTION` | `mysql` | Driver database |
| `DB_HOST` | `order-db` | Host container MySQL |
| `DB_PORT` | `3306` | Port internal MySQL container |
| `DB_DATABASE` | `ordersdb` | Nama database |
| `DB_USERNAME` | `orderuser` | Username database |
| `DB_PASSWORD` | `orderpassword` | Password database |
| `CART_SERVICE_URL` | `http://cart-service:3001` | Alamat base URL cart-service |
| `CART_SERVICE_TIMEOUT` | `5` | Batas timeout HTTP (detik, maks 5) |
| `PRODUCT_SERVICE_URL` | `http://product-service:3000` | Alamat base URL product-service |
| `PRODUCT_SERVICE_TIMEOUT` | `5` | Batas timeout HTTP (detik, maks 5) |
