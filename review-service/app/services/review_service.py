# app/services/review_service.py

# Lapisan service:  
# Tempat logika bisnis review.
# Dipanggil oleh controller dan menggunakan model untuk mengakses data.
# Tidak menangani request atau response HTTP.

from datetime import datetime, timezone

from bson import ObjectId
from pymongo.errors import DuplicateKeyError

from app.clients import product_client
from app.exceptions import (
    DuplicateReviewError,
    ProductNotFoundError,
    ReviewNotFoundError,
)
from app.models import review_model


# Mengubah ID dari URL menjadi ObjectId.
# Jika format ID tidak valid, anggap review tidak ditemukan.
def _parse_id(review_id):
    if not ObjectId.is_valid(review_id):
        raise ReviewNotFoundError(review_id)

    return ObjectId(review_id)


# Mengambil semua review suatu produk beserta ringkasan rating.
def get_product_reviews(product_id):
    return {
        "product_id": product_id,
        "summary": review_model.rating_summary(product_id),
        "reviews": review_model.find_by_product(product_id),
    }


# Mengambil satu review berdasarkan ID.
def get_review(review_id):
    review = review_model.find_by_id(
        _parse_id(review_id)
    )

    if review is None:
        raise ReviewNotFoundError(review_id)

    return review


# Membuat review baru.
# Produk diperiksa terlebih dahulu melalui product-service.
# Indeks unik database digunakan untuk mencegah review duplikat
# dari user yang sama untuk produk yang sama.
def create_review(data):
    product_exists = product_client.product_exists(
        data["product_id"]
    )

    if not product_exists:
        raise ProductNotFoundError(data["product_id"])

    now = datetime.now(timezone.utc)

    document = {
        **data,
        "created_at": now,
        "updated_at": now,
    }

    try:
        return review_model.insert(document)

    except DuplicateKeyError as error:
        raise DuplicateReviewError(
            data["user_id"],
            data["product_id"],
        ) from error


# Memperbarui review berdasarkan ID.
def update_review(review_id, data):
    fields = {
        **data,
        "updated_at": datetime.now(timezone.utc),
    }

    review = review_model.update(
        _parse_id(review_id),
        fields,
    )

    if review is None:
        raise ReviewNotFoundError(review_id)

    return review


# Menghapus review berdasarkan ID.
def delete_review(review_id):
    deleted = review_model.delete(
        _parse_id(review_id)
    )

    if not deleted:
        raise ReviewNotFoundError(review_id)

