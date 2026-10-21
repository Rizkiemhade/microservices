# app/schemas/review_schema.py

# Lapisan validasi request:
# Memeriksa data yang dikirim client sebelum masuk ke service.
# Jika terdapat kesalahan, ValidationError akan berisi
# seluruh kesalahan validasi sekaligus.

from app.exceptions import ValidationError


MAX_COMMENT_LENGTH = 1000


# Memastikan nilai berupa integer, bukan boolean.
# Dalam Python, bool merupakan turunan dari int.
def _is_int(value):
    return isinstance(value, int) and not isinstance(value, bool)


# Memvalidasi rating dengan rentang 1 sampai 5.
def _rating_error(value):
    if not _is_int(value) or not 1 <= value <= 5:
        return "Field rating harus berupa angka bulat antara 1 sampai 5"

    return None


# Memvalidasi komentar.
def _comment_error(value):
    if not isinstance(value, str):
        return "Field comment harus berupa teks"

    if len(value) > MAX_COMMENT_LENGTH:
        return (
            f"Field comment maksimal "
            f"{MAX_COMMENT_LENGTH} karakter"
        )

    return None


# Validasi untuk POST /reviews.
# Mengembalikan data bersih yang siap digunakan oleh service.
def validate_create_review(payload):
    if not isinstance(payload, dict):
        raise ValidationError({
            "body": ["Body request harus berupa JSON object"],
        })

    errors = {}

    # Validasi product_id dan user_id.
    for field in ("product_id", "user_id"):
        value = payload.get(field)

        if value is None:
            errors[field] = [
                f"Field {field} wajib diisi"
            ]

        elif not _is_int(value) or value < 1:
            errors[field] = [
                f"Field {field} harus berupa angka bulat minimal 1"
            ]

    # Validasi rating.
    rating = payload.get("rating")

    if rating is None:
        errors["rating"] = [
            "Field rating wajib diisi"
        ]

    else:
        error = _rating_error(rating)

        if error:
            errors["rating"] = [error]

    # Validasi comment. Jika tidak dikirim, gunakan string kosong.
    comment = payload.get("comment", "")
    error = _comment_error(comment)

    if error:
        errors["comment"] = [error]

    # Jika ada kesalahan, kirim semua kesalahan sekaligus.
    if errors:
        raise ValidationError(errors)

    # Hanya mengembalikan field yang diperbolehkan.
    return {
        "product_id": payload["product_id"],
        "user_id": payload["user_id"],
        "rating": rating,
        "comment": comment,
    }


# Validasi untuk PUT /reviews/<review_id>.
# Hanya rating dan comment yang boleh diperbarui.
# Minimal salah satu field harus dikirim.
def validate_update_review(payload):
    if not isinstance(payload, dict):
        raise ValidationError({
            "body": ["Body request harus berupa JSON object"],
        })

    errors = {}
    data = {}

    # Pastikan minimal satu field dikirim.
    if "rating" not in payload and "comment" not in payload:
        errors["body"] = [
            "Kirim minimal salah satu dari rating atau comment"
        ]

    # Validasi rating jika dikirim.
    if "rating" in payload:
        error = _rating_error(payload["rating"])

        if error:
            errors["rating"] = [error]
        else:
            data["rating"] = payload["rating"]

    # Validasi comment jika dikirim.
    if "comment" in payload:
        error = _comment_error(payload["comment"])

        if error:
            errors["comment"] = [error]
        else:
            data["comment"] = payload["comment"]

    if errors:
        raise ValidationError(errors)

    return data