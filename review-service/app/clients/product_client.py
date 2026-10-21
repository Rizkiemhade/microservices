# app/clients/product_client.py

# Klien HTTP untuk berkomunikasi dengan product-service.
# Hanya file ini yang mengetahui alamat dan cara memanggil service.
# Lapisan lain cukup menggunakan fungsi product_exists().

import os

import requests

from app.exceptions import ProductServiceUnavailableError


# Batas waktu menunggu respons dari product-service (detik).
# Mencegah request menggantung terlalu lama.
TIMEOUT_SECONDS = 3


# Memeriksa apakah produk tersedia di product-service.
#
# Mengembalikan:
# - True  : produk ditemukan.
# - False : produk tidak ditemukan (HTTP 404).
#
# Melempar ProductServiceUnavailableError jika service
# tidak dapat dijangkau atau mengembalikan HTTP error lainnya.
def product_exists(product_id):
    # Alamat dibaca saat fungsi dipanggil agar konfigurasi
    # dari file .env dapat digunakan setelah dimuat.
    base_url = os.getenv(
        "PRODUCT_SERVICE_URL",
        "http://localhost:3000",
    ).rstrip("/")

    url = f"{base_url}/products/{product_id}"

    try:
        response = requests.get(
            url,
            timeout=TIMEOUT_SECONDS,
        )

    except requests.RequestException as error:
        # Mencakup koneksi ditolak, DNS gagal, dan timeout.
        raise ProductServiceUnavailableError(
            "Product service tidak dapat dihubungi"
        ) from error

    if response.status_code == 404:
        return False

    if not response.ok:
        raise ProductServiceUnavailableError(
            f"Product service membalas error "
            f"{response.status_code}"
        )

    return True