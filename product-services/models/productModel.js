const pool = require('../config/db');

async function initializeDatabase() {
    await pool.query(`
        CREATE TABLE IF NOT EXISTS products (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10, 2) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    `);

    const [rows] = await pool.query('SELECT COUNT(*) AS total FROM products');
    if (rows[0].total === 0) {
        await pool.query(`
            INSERT INTO products (name, description, price, stock) VALUES
            ('Product 1', 'Description A', 99.99, 10),
            ('Product 2', 'Description B', 149.99, 5)
        `);
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
    const { name, description, price, stock } = product;
    const [result] = await pool.query('INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)', [name, description, price, stock]);
    return getProductById(result.insertId);
}

async function updateProduct(id, product) {
    const { name, description, price, stock } = product;
    await pool.query('UPDATE products SET name = ?, description = ?, price = ?, stock = ? WHERE id = ?', [name, description, price, stock, id]);
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