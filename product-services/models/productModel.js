const pool = require('../config/db');
const DUMMY_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

async function initializeDatabase() {
    await pool.query(`
        CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            image MEDIUMTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    `);

    const [imageColumns] = await pool.query(`
        SELECT DATA_TYPE, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image'
    `);
    const needsImageMigration = imageColumns.length === 0
        || imageColumns[0].IS_NULLABLE === 'YES'
        || imageColumns[0].DATA_TYPE === 'text';
    if (imageColumns.length === 0) {
        await pool.query('ALTER TABLE products ADD COLUMN image MEDIUMTEXT NULL');
    }
    await pool.query('UPDATE products SET image = ? WHERE image IS NULL OR image = ?', [DUMMY_IMAGE, '']);
    if (needsImageMigration) {
        await pool.query('ALTER TABLE products MODIFY image MEDIUMTEXT NOT NULL');
    }

    const [rows] = await pool.query('SELECT COUNT(*) AS total FROM products');
    if (rows[0].total === 0) {
        await pool.query(`
            INSERT INTO products (name, description, price, stock, image) VALUES
            ('Product 1', 'Description A', 99.99, 10, ?),
            ('Product 2', 'Description B', 149.99, 5, ?)
        `, [DUMMY_IMAGE, DUMMY_IMAGE]);
    }
}

async function getAllProducts() {
    const [rows] = await pool.query('SELECT * FROM products ORDER BY created_at DESC');
    return rows;
}

async function getProductById(id) {
    const [rows] = await pool.query('SELECT * FROM products WHERE id = ?', [id]);
    return rows[0];
}

async function createProduct(product) {
    const { name, description, price, stock, image } = product;
    const [result] = await pool.query('INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)', [name, description, price, stock, image]);
    return getProductById(result.insertId);
}

async function updateProduct(id, product) {
    const { name, description, price, stock, image } = product;
    await pool.query('UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?', [name, description, price, stock, image, id]);
    return getProductById(id);
}

async function deleteProduct(id) {
    const [result] = await pool.query('DELETE FROM products WHERE id = ?', [id]);
    return result.affectedRows > 0;
}

module.exports = {
    initializeDatabase,
    getAllProducts,
    getProductById,
    createProduct,
    updateProduct,
    deleteProduct
}