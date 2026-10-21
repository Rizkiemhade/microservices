# app/exceptions.py

# Exception khusus untuk menangani masalah yang sudah diperkirakan.
# Service melempar exception, lalu controller menangkapnya
# dan menentukan status HTTP response.
# Dengan demikian, service tidak perlu mengetahui urusan HTTP.


class ValidationError(Exception):
    """Kesalahan ketika data yang dikirim client tidak valid."""

    def __init__(self, errors):
        super().__init__("Data yang dikirim tidak valid")

        # Dictionary berisi nama field dan daftar pesan kesalahan.
        self.errors = errors


class ReviewNotFoundError(Exception):
    """Review tidak ditemukan berdasarkan ID."""

    def __init__(self, review_id):
        super().__init__(
            f"Review dengan id {review_id} tidak ditemukan"
        )


class DuplicateReviewError(Exception):
    """User sudah pernah memberikan review pada produk yang sama."""

    def __init__(self, user_id, product_id):
        super().__init__(
            f"User {user_id} sudah pernah mengulas "
            f"produk {product_id}"
        )


class ProductNotFoundError(Exception):
    """Produk tidak ditemukan di product-service."""

    def __init__(self, product_id):
        super().__init__(
            f"Produk dengan id {product_id} tidak ditemukan"
        )


class ProductServiceUnavailableError(Exception):
    """Product-service tidak dapat dijangkau atau mengalami error."""

    def __init__(self, message="Product service tidak tersedia"):
        super().__init__(message)
