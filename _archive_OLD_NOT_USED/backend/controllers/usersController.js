const db = require('../config/database');

// GET /api/users (admin e segreteria)
const getAllUsers = async (req, res) => {
  try {
    const { role, search, page = 1, limit = 100 } = req.query;
    const offset = (page - 1) * limit;

    let query = `
      SELECT 
        u.id, u.username, u.email, u.role, u.name, u.phone, 
        u.fiscal_code, u.birth_date, u.address, u.qr_code, u.active,
        u.created_at,
        p.id as package_id, p.package_type, p.total_entries, 
        p.remaining_entries, p.purchase_date, p.expiry_date, p.price
      FROM users u
      LEFT JOIN packages p ON u.id = p.user_id AND p.active = true
      WHERE 1=1
    `;

    const params = [];
    let paramIndex = 1;

    if (role) {
      query += ` AND u.role = $${paramIndex}`;
      params.push(role);
      paramIndex++;
    }

    if (search) {
      query += ` AND (u.name ILIKE $${paramIndex} OR u.email ILIKE $${paramIndex} OR u.username ILIKE $${paramIndex})`;
      params.push(`%${search}%`);
      paramIndex++;
    }

    query += ` ORDER BY u.created_at DESC LIMIT $${paramIndex} OFFSET $${paramIndex + 1}`;
    params.push(limit, offset);

    const result = await db.query(query, params);

    // Count totale
    let countQuery = 'SELECT COUNT(*) FROM users u WHERE 1=1';
    const countParams = [];
    let countIndex = 1;

    if (role) {
      countQuery += ` AND u.role = $${countIndex}`;
      countParams.push(role);
      countIndex++;
    }

    if (search) {
      countQuery += ` AND (u.name ILIKE $${countIndex} OR u.email ILIKE $${countIndex} OR u.username ILIKE $${countIndex})`;
      countParams.push(`%${search}%`);
    }

    const countResult = await db.query(countQuery, countParams);
    const total = parseInt(countResult.rows[0].count);

    res.json({
      success: true,
      data: {
        users: result.rows,
        pagination: {
          page: parseInt(page),
          limit: parseInt(limit),
          total,
          totalPages: Math.ceil(total / limit)
        }
      }
    });
  } catch (error) {
    console.error('Errore recupero utenti:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il recupero degli utenti',
      error: error.message
    });
  }
};

// GET /api/users/:id
const getUserById = async (req, res) => {
  try {
    const { id } = req.params;

    const result = await db.query(`
      SELECT 
        u.id, u.username, u.email, u.role, u.name, u.phone,
        u.fiscal_code, u.birth_date, u.birth_place, u.address, 
        u.qr_code, u.active, u.created_at
      FROM users u
      WHERE u.id = $1
    `, [id]);

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Utente non trovato'
      });
    }

    const user = result.rows[0];

    // Recupera pacchetti
    const packagesResult = await db.query(`
      SELECT * FROM packages 
      WHERE user_id = $1 
      ORDER BY created_at DESC
    `, [id]);

    // Recupera ultimi ingressi
    const entriesResult = await db.query(`
      SELECT * FROM entries 
      WHERE user_id = $1 
      ORDER BY entry_date DESC, entry_time DESC 
      LIMIT 20
    `, [id]);

    res.json({
      success: true,
      data: {
        user,
        packages: packagesResult.rows,
        recentEntries: entriesResult.rows
      }
    });
  } catch (error) {
    console.error('Errore recupero utente:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il recupero dell\'utente',
      error: error.message
    });
  }
};

// GET /api/users/qr/:qrCode
const getUserByQR = async (req, res) => {
  try {
    const { qrCode } = req.params;

    const result = await db.query(`
      SELECT 
        u.id, u.username, u.email, u.role, u.name, u.phone,
        u.qr_code, u.active,
        p.id as package_id, p.package_type, p.total_entries, 
        p.remaining_entries, p.purchase_date, p.expiry_date, p.price
      FROM users u
      LEFT JOIN packages p ON u.id = p.user_id AND p.active = true
      WHERE u.qr_code = $1
    `, [qrCode]);

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'QR Code non trovato'
      });
    }

    res.json({
      success: true,
      data: { user: result.rows[0] }
    });
  } catch (error) {
    console.error('Errore recupero utente da QR:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il recupero dell\'utente',
      error: error.message
    });
  }
};

// PUT /api/users/:id
const updateUser = async (req, res) => {
  try {
    const { id } = req.params;
    const { name, email, phone, fiscalCode, birthDate, birthPlace, address } = req.body;

    const result = await db.query(`
      UPDATE users 
      SET name = $1, email = $2, phone = $3, fiscal_code = $4,
          birth_date = $5, birth_place = $6, address = $7
      WHERE id = $8
      RETURNING id, username, email, role, name, phone, qr_code
    `, [name, email, phone, fiscalCode, birthDate, birthPlace, address, id]);

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Utente non trovato'
      });
    }

    res.json({
      success: true,
      message: 'Utente aggiornato con successo',
      data: { user: result.rows[0] }
    });
  } catch (error) {
    console.error('Errore aggiornamento utente:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante l\'aggiornamento dell\'utente',
      error: error.message
    });
  }
};

// DELETE /api/users/:id (soft delete)
const deleteUser = async (req, res) => {
  try {
    const { id } = req.params;

    const result = await db.query(
      'UPDATE users SET active = false WHERE id = $1 RETURNING id',
      [id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Utente non trovato'
      });
    }

    res.json({
      success: true,
      message: 'Utente disattivato con successo'
    });
  } catch (error) {
    console.error('Errore eliminazione utente:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante l\'eliminazione dell\'utente',
      error: error.message
    });
  }
};

// GET /api/users/stats
const getStats = async (req, res) => {
  try {
    const stats = {};

    // Totale utenti
    const totalResult = await db.query(
      "SELECT COUNT(*) as count FROM users WHERE role = 'user' AND active = true"
    );
    stats.totalUsers = parseInt(totalResult.rows[0].count);

    // Utenti attivi (con ingressi disponibili)
    const activeResult = await db.query(`
      SELECT COUNT(DISTINCT u.id) as count
      FROM users u
      JOIN packages p ON u.id = p.user_id
      WHERE u.role = 'user' AND u.active = true 
        AND p.active = true AND p.remaining_entries > 0
        AND p.expiry_date >= CURRENT_DATE
    `);
    stats.activeUsers = parseInt(activeResult.rows[0].count);

    // Ingressi oggi
    const todayEntriesResult = await db.query(
      'SELECT COUNT(*) as count FROM entries WHERE entry_date = CURRENT_DATE'
    );
    stats.todayEntries = parseInt(todayEntriesResult.rows[0].count);

    // Incasso totale
    const revenueResult = await db.query(
      'SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = $1',
      ['completed']
    );
    stats.totalRevenue = parseFloat(revenueResult.rows[0].total);

    // Incasso oggi
    const todayCashResult = await db.query(
      "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_date = CURRENT_DATE AND payment_method = 'cash'",
    );
    stats.todayCash = parseFloat(todayCashResult.rows[0].total);

    res.json({
      success: true,
      data: { stats }
    });
  } catch (error) {
    console.error('Errore recupero statistiche:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il recupero delle statistiche',
      error: error.message
    });
  }
};

module.exports = {
  getAllUsers,
  getUserById,
  getUserByQR,
  updateUser,
  deleteUser,
  getStats
};
