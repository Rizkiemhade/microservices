# app/models/review_model.py

# Lapisan model:
# Satu-satunya tempat yang berinteraksi langsung dengan MongoDB.
# Tidak menangani aturan bisnis atau request/response HTTP.

from pymongo import DESCENDING, ReturnDocument

from app.database import get_collection


# Mengubah dokumen MongoDB menjadi dictionary yang siap dijadikan JSON.
# ObjectId diubah menjadi string bernama "id".
# Waktu diubah menjadi string ISO 8601.
def _serialize(doc):
    if doc is None:
        return None

    return {
        "id": str(doc["_id"]),
        "product_id": doc["product_id"],
        "user_id": doc["user_id"],
        "rating": doc["rating"],
        "comment": doc["comment"],
        "created_at": doc["created_at"].isoformat(),
        "updated_at": doc["updated_at"].isoformat(),
    }


# Mengambil semua review untuk satu produk.
# Review terbaru ditampilkan terlebih dahulu.
def find_by_product(product_id):
    cursor = (
        get_collection()
        .find({"product_id": product_id})
        .sort([
            ("created_at", DESCENDING),
            ("_id", DESCENDING),
        ])
    )

    return [_serialize(doc) for doc in cursor]


# Mengambil satu review berdasarkan ObjectId.
# Mengembalikan None jika review tidak ditemukan.
def find_by_id(object_id):
    doc = get_collection().find_one({
        "_id": object_id,
    })

    return _serialize(doc)


# Menyimpan review baru dan mengembalikan dokumen
# yang tersimpan, termasuk ID yang dibuat MongoDB.
def insert(document):
    result = get_collection().insert_one(document)

    return find_by_id(result.inserted_id)


# Memperbarui sebagian field review.
# Mengembalikan dokumen terbaru atau None jika tidak ditemukan.
def update(object_id, fields):
    doc = get_collection().find_one_and_update(
        {"_id": object_id},
        {"$set": fields},
        return_document=ReturnDocument.AFTER,
    )

    return _serialize(doc)


# Menghapus satu review.
# Mengembalikan True jika berhasil menghapus dokumen.
def delete(object_id):
    result = get_collection().delete_one({
        "_id": object_id,
    })

    return result.deleted_count > 0


# Menghitung rata-rata rating dan jumlah review suatu produk
# menggunakan aggregation pipeline MongoDB.
def rating_summary(product_id):
    pipeline = [
        {
            "$match": {
                "product_id": product_id,
            },
        },
        {
            "$group": {
                "_id": None,
                "average": {
                    "$avg": "$rating",
                },
                "total": {
                    "$sum": 1,
                },
            },
        },
    ]

    result = list(
        get_collection().aggregate(pipeline)
    )

    # Jika belum ada review, kembalikan nilai default.
    if not result or not result[0]["total"]:
        return {
            "average_rating": 0,
            "total_reviews": 0,
        }

    return {
        "average_rating": round(result[0]["average"], 2),
        "total_reviews": result[0]["total"],
    }
