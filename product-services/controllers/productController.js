const productModel = require('../models/productModel');

async function getAllProducts(req, res) {
    try {
        const products = await productModel.getAllProducts();
        res.json({
            message: "Berhasil mengambil semua product",
            data: products
        });
    } catch (error) {
        res.status(500).json({
            message: "Gagal mengambil product",
            error: error.message
        });
    }
}

async function getProductByIdHandler(req, res) {
    const id = Number(req.params.id);

    if (!Number.isInteger(id) || id <= 0) {
        return res.status(400).json({
            message: "ID product tidak valid"
        });
    }

    try {
        const product = await productModel.getProductById(id);

        if (!product) {
            return res.status(404).json({
                message: "Product tidak ditemukan"
            });
        }

        return res.json({
            message: "Berhasil mengambil product",
            data: product
        });
    } catch (error) {
        return res.status(500).json({
            message: "Gagal mengambil product",
            error: error.message
        });
    }
}

async function createProduct(req, res) {
    const { name, description, price, stock } = req.body;

    if (!name || price === undefined || stock === undefined) {
        return res.status(400).json({
            message: "Field name, price, dan stock wajib diisi"
        });
    }

    try {
        const product = await productModel.createProduct({
            name,
            description: description || '',
            price,
            stock
        });

        return res.status(201).json({
            message: "Berhasil menambahkan product",
            data: product
        });
    } catch (error) {
        return res.status(500).json({
            message: "Gagal menambahkan product",
            error: error.message
        });
    }
}

async function updateProduct(req, res) {
    const id = Number(req.params.id);
    const { name, description, price, stock } = req.body;

    if (!Number.isInteger(id) || id <= 0) {
        return res.status(400).json({
            message: "ID product tidak valid"
        });
    }

    if (!name || price === undefined || stock === undefined) {
        return res.status(400).json({
            message: "Field name, price, dan stock wajib diisi"
        });
    }

    try {
        const existingProduct = await productModel.getProductById(id);

        if (!existingProduct) {
            return res.status(404).json({
                message: "Product tidak ditemukan"
            });
        }

        const updatedProduct = await productModel.updateProduct(id, {
            name,
            description: description || '',
            price,
            stock
        });

        return res.json({
            message: "Berhasil memperbarui product",
            data: updatedProduct
        });
    } catch (error) {
        return res.status(500).json({
            message: "Gagal memperbarui product",
            error: error.message
        });
    }
}

async function deleteProduct(req, res) {
    const id = Number(req.params.id);

    if (!Number.isInteger(id) || id <= 0) {
        return res.status(400).json({
            message: "ID product tidak valid"
        });
    }

    try {
        const existingProduct = await productModel.getProductById(id);

        if (!existingProduct) {
            return res.status(404).json({
                message: "Product tidak ditemukan"
            });
        }

        const deleted = await productModel.deleteProduct(id);

        if (!deleted) {
            return res.status(404).json({
                message: "Product tidak ditemukan"
            });
        }

        return res.json({
            message: "Berhasil menghapus product",
            data: {
                id
            }
        });
    } catch (error) {
        return res.status(500).json({
            message: "Gagal menghapus product",
            error: error.message
        });
    }
}

module.exports = {
    index: getAllProducts,
    getAllProducts,
    show: getProductByIdHandler,
    create: createProduct,
    update: updateProduct,
    destroy: deleteProduct
};
