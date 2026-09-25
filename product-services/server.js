const app = require('./app');
const pool = require('./config/db');
const productModel = require('./models/productModel');
require('dotenv').config();

const PORT = process.env.PORT || 3000;

async function waitForDatabase(maxAttempts = 30) {
    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
        try {
            const connection = await pool.getConnection();
            connection.release();
            await productModel.initializeDatabase();
            return;
        } catch (error) {
            console.log(`Menunggu database siap... (${attempt}/${maxAttempts})`);
            if (attempt === maxAttempts) {
                throw error;
            }
            await new Promise((resolve) => setTimeout(resolve, 2000));
        }
    }
}

waitForDatabase()
    .then(() => {
        app.listen(PORT, () => {
            console.log(`Product service berjalan di http://localhost:${PORT}`);
        });
    })
    .catch((error) => {
        console.error('Database tidak tersedia:', error.message);
        process.exit(1);
    });