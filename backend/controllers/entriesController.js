const db = require('../config/database');

// POST /api/entries/register
const registerEntry = async (req, res) => {
  const client = await db.pool.connect();
  
  try {
    const { userId } = req.body;
    const staffId = req.user.id;
    const staffName = req.user.name;

    await client.query('BEGIN');

    // Recupera pacchetto attivo
    const packageResult = await client.query(`
      SELECT * FROM packages 
      WHERE user_id = $1 AND active = true 
        AND remaining_entries > 0 
        AND expiry_date >= CURRENT_DATE
      LIMIT 1
    `, [userId]);

    if (packageResult.rows.length === 0) {
      await client.query('ROLLBACK');
      return res.status(400).json({
        success: false,
        message: 'Nessun pacchetto valido disponibile'
      });
    }

    const package = packageResult.rows[0];

    // Decrementa ingresso
    const newRemaining = package.remaining_entries - 1;
    await client.query(
      'UPDATE packages SET remaining_entries = $1 WHERE id = $2',
      [newRemaining, package.id]
    );

    // Registra ingresso
    const entryResult = await client.query(`
      INSERT INTO entries (
        user_id, package_id, entry_date, entry_time, 
        staff_name, staff_id, remaining_after
      )
      VALUES ($1, $2, CURRENT_DATE, CURRENT_TIME, $3, $4, $5)
      RETURNING *
    `, [userId, package.id, staffName, staffId, newRemaining]);

    await client.query('COMMIT');

    res.json({
      success: true,
      message: 'Ingresso registrato con successo',
      data: {
        entry: entryResult.rows[0],
        remainingEntries: newRemaining
      }
    });
  } catch (error) {
    await client.query('ROLLBACK');
    console.error('Errore registrazione ingresso:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante la registrazione dell\'ingresso',
      error: error.message
    });
  } finally {
    client.release();
  }
};

// POST /api/packages/purchase
const purchasePackage = async (req, res) => {
  const client = await db.pool.connect();
  
  try {
    const { userId, packageType, paymentMethod, amount } = req.body;
    const staffId = req.user.id;
    const staffName = req.user.name;

    await client.query('BEGIN');

    // Determina dettagli pacchetto
    let totalEntries, price, expiryDate;
    const purchaseDate = new Date();

    switch (packageType) {
      case 'single':
        totalEntries = 1;
        price = amount || 12;
        expiryDate = new Date(purchaseDate);
        expiryDate.setDate(expiryDate.getDate() + 30);
        break;
      case '10_entries':
        totalEntries = 10;
        price = amount || 100;
        expiryDate = new Date(purchaseDate);
        expiryDate.setMonth(expiryDate.getMonth() + 6);
        break;
      case 'promo':
        totalEntries = 3;
        price = amount || 30;
        expiryDate = new Date('2026-08-31');
        break;
      default:
        await client.query('ROLLBACK');
        return res.status(400).json({
          success: false,
          message: 'Tipo pacchetto non valido'
        });
    }

    // Disattiva pacchetti precedenti
    await client.query(
      'UPDATE packages SET active = false WHERE user_id = $1 AND active = true',
      [userId]
    );

    // Crea nuovo pacchetto
    const packageResult = await client.query(`
      INSERT INTO packages (
        user_id, package_type, total_entries, remaining_entries,
        purchase_date, expiry_date, price, payment_method
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      RETURNING *
    `, [
      userId, packageType, totalEntries, totalEntries,
      purchaseDate.toISOString().split('T')[0],
      expiryDate.toISOString().split('T')[0],
      price, paymentMethod
    ]);

    // Registra pagamento
    await client.query(`
      INSERT INTO payments (
        user_id, package_id, amount, payment_method, payment_date,
        staff_name, staff_id, status
      )
      VALUES ($1, $2, $3, $4, CURRENT_DATE, $5, $6, $7)
    `, [
      userId, packageResult.rows[0].id, price, paymentMethod,
      staffName, staffId, 'completed'
    ]);

    await client.query('COMMIT');

    res.json({
      success: true,
      message: 'Pacchetto acquistato con successo',
      data: { package: packageResult.rows[0] }
    });
  } catch (error) {
    await client.query('ROLLBACK');
    console.error('Errore acquisto pacchetto:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante l\'acquisto del pacchetto',
      error: error.message
    });
  } finally {
    client.release();
  }
};

// GET /api/reports/daily
const getDailyReport = async (req, res) => {
  try {
    const { date } = req.query;
    const reportDate = date || new Date().toISOString().split('T')[0];

    // Ingressi giornalieri
    const entriesResult = await db.query(`
      SELECT e.*, u.name as user_name, u.qr_code
      FROM entries e
      JOIN users u ON e.user_id = u.id
      WHERE e.entry_date = $1
      ORDER BY e.entry_time DESC
    `, [reportDate]);

    // Pagamenti giornalieri
    const paymentsResult = await db.query(`
      SELECT p.*, u.name as user_name
      FROM payments p
      JOIN users u ON p.user_id = u.id
      WHERE p.payment_date = $1
      ORDER BY p.created_at DESC
    `, [reportDate]);

    // Totale incasso
    const cashTotal = paymentsResult.rows
      .filter(p => p.payment_method === 'cash')
      .reduce((sum, p) => sum + parseFloat(p.amount), 0);

    // Utenti in scadenza (30 giorni)
    const expiringResult = await db.query(`
      SELECT u.id, u.name, u.email, p.expiry_date, p.remaining_entries
      FROM users u
      JOIN packages p ON u.id = p.user_id
      WHERE u.role = 'user' AND p.active = true
        AND p.expiry_date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'
      ORDER BY p.expiry_date ASC
    `);

    // Utenti con pochi ingressi
    const lowEntriesResult = await db.query(`
      SELECT u.id, u.name, u.email, p.remaining_entries, p.expiry_date
      FROM users u
      JOIN packages p ON u.id = p.user_id
      WHERE u.role = 'user' AND p.active = true
        AND p.remaining_entries > 0 AND p.remaining_entries <= 2
      ORDER BY p.remaining_entries ASC
    `);

    res.json({
      success: true,
      data: {
        date: reportDate,
        entries: entriesResult.rows,
        payments: paymentsResult.rows,
        totalEntries: entriesResult.rows.length,
        totalCash: cashTotal,
        expiringUsers: expiringResult.rows,
        lowEntriesUsers: lowEntriesResult.rows
      }
    });
  } catch (error) {
    console.error('Errore recupero report:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il recupero del report',
      error: error.message
    });
  }
};

module.exports = {
  registerEntry,
  purchasePackage,
  getDailyReport
};
