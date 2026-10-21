# app/routes/review_routes.py

# Hanya memetakan method dan path ke fungsi controller,
# tanpa menjalankan logika bisnis.

from flask import Blueprint
from app.controllers import review_controller as controller


review_bp = Blueprint("reviews", __name__)


# Mengambil semua review berdasarkan ID produk
review_bp.add_url_rule(
    "/reviews/product/<int:product_id>",
    view_func=controller.list_by_product,
    methods=["GET"],
)


# Mengambil detail review berdasarkan ID
review_bp.add_url_rule(
    "/reviews/<review_id>",
    view_func=controller.show,
    methods=["GET"],
)


# Membuat review baru
review_bp.add_url_rule(
    "/reviews",
    view_func=controller.store,
    methods=["POST"],
)


# Memperbarui review berdasarkan ID
review_bp.add_url_rule(
    "/reviews/<review_id>",
    view_func=controller.update,
    methods=["PUT"],
)


# Menghapus review berdasarkan ID
review_bp.add_url_rule(
    "/reviews/<review_id>",
    view_func=controller.destroy,
    methods=["DELETE"],
)