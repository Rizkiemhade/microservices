# app/__init__.py

# Application factory: merakit Flask app, koneksi database,
# routes, dan penanganan error umum dalam fungsi create_app().

import logging

from dotenv import load_dotenv
from flask import Flask, jsonify

from app.database import init_db
from app.routes.review_routes import review_bp


def create_app():
    # Baca file .env terlebih dahulu agar konfigurasi
    # seperti FLASK_DEBUG dapat dimuat.
    load_dotenv()

    app = Flask(__name__)
    app.logger.setLevel(logging.INFO)

    # Inisialisasi koneksi database.
    init_db()

    # Daftarkan routes review.
    app.register_blueprint(review_bp)

    # Endpoint untuk memeriksa status service.
    @app.get("/health")
    def health():
        return jsonify({
            "status": "ok",
            "service": "review-service",
        }), 200

    # Ubah respons 404 menjadi JSON.
    @app.errorhandler(404)
    def not_found(_error):
        return jsonify({
            "message": "Endpoint tidak ditemukan",
        }), 404

    # Ubah respons 405 menjadi JSON.
    @app.errorhandler(405)
    def method_not_allowed(_error):
        return jsonify({
            "message": "Method tidak diizinkan untuk endpoint ini",
        }), 405

    return app
