const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const db = require('../config/database');

// Genera JWT token
const generateToken = (user) => {
  return jwt.sign(
    { 
      id: user.id, 
      username: user.username, 
      role: user.role,
      email: user.email
    },
    process.env.JWT_SECRET,
    { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
  );
};

// POST /api/auth/register
const register = async (req, res) => {
  try {
    const { 
      username, email, password, name, phone, 
      fiscalCode, birthDate, address, role = 'user' 
    } = req.body;

    // Verifica se username o email già esistono
    const existingUser = await db.query(
      'SELECT id FROM users WHERE username = $1 OR email = $2',
      [username, email]
    );

    if (existingUser.rows.length > 0) {
      return res.status(400).json({
        success: false,
        message: 'Username o email già registrati'
      });
    }

    // Hash password
    const passwordHash = await bcrypt.hash(password, 10);

    // Genera QR code univoco
    const qrCodeResult = await db.query(
      'SELECT COUNT(*) as count FROM users WHERE role = $1',
      [role]
    );
    const count = parseInt(qrCodeResult.rows[0].count) + 1;
    const qrCode = role === 'user' ? `USR${String(count).padStart(3, '0')}` : 
                   role === 'segreteria' ? `STF${String(count).padStart(3, '0')}` :
                   `ADM${String(count).padStart(3, '0')}`;

    // Inserisci nuovo utente
    const result = await db.query(`
      INSERT INTO users (
        username, email, password_hash, role, name, phone, 
        fiscal_code, birth_date, address, qr_code
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      RETURNING id, username, email, role, name, qr_code, created_at
    `, [
      username, email, passwordHash, role, name, phone,
      fiscalCode, birthDate, address, qrCode
    ]);

    const newUser = result.rows[0];
    const token = generateToken(newUser);

    res.status(201).json({
      success: true,
      message: 'Registrazione completata con successo',
      data: {
        user: {
          id: newUser.id,
          username: newUser.username,
          email: newUser.email,
          role: newUser.role,
          name: newUser.name,
          qrCode: newUser.qr_code
        },
        token
      }
    });
  } catch (error) {
    console.error('Errore registrazione:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante la registrazione',
      error: error.message
    });
  }
};

// POST /api/auth/login
const login = async (req, res) => {
  try {
    const { username, password } = req.body;

    // Trova utente
    const result = await db.query(
      'SELECT * FROM users WHERE username = $1 AND active = true',
      [username]
    );

    if (result.rows.length === 0) {
      return res.status(401).json({
        success: false,
        message: 'Username o password non validi'
      });
    }

    const user = result.rows[0];

    // Verifica password
    const passwordMatch = await bcrypt.compare(password, user.password_hash);

    if (!passwordMatch) {
      return res.status(401).json({
        success: false,
        message: 'Username o password non validi'
      });
    }

    // Genera token
    const token = generateToken(user);

    // Aggiorna first_login se necessario
    if (user.first_login) {
      await db.query(
        'UPDATE users SET first_login = false WHERE id = $1',
        [user.id]
      );
    }

    res.json({
      success: true,
      message: 'Login effettuato con successo',
      data: {
        user: {
          id: user.id,
          username: user.username,
          email: user.email,
          role: user.role,
          name: user.name,
          qrCode: user.qr_code,
          firstLogin: user.first_login
        },
        token
      }
    });
  } catch (error) {
    console.error('Errore login:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il login',
      error: error.message
    });
  }
};

// POST /api/auth/logout
const logout = (req, res) => {
  // Con JWT il logout è gestito lato client (rimozione token)
  res.json({
    success: true,
    message: 'Logout effettuato con successo'
  });
};

// GET /api/auth/me
const getMe = async (req, res) => {
  try {
    const result = await db.query(
      'SELECT id, username, email, role, name, phone, qr_code, created_at FROM users WHERE id = $1',
      [req.user.id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Utente non trovato'
      });
    }

    res.json({
      success: true,
      data: { user: result.rows[0] }
    });
  } catch (error) {
    console.error('Errore recupero profilo:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il recupero del profilo',
      error: error.message
    });
  }
};

// POST /api/auth/change-password
const changePassword = async (req, res) => {
  try {
    const { oldPassword, newPassword } = req.body;

    // Recupera utente con password hash
    const result = await db.query(
      'SELECT password_hash FROM users WHERE id = $1',
      [req.user.id]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: 'Utente non trovato'
      });
    }

    // Verifica old password
    const passwordMatch = await bcrypt.compare(oldPassword, result.rows[0].password_hash);

    if (!passwordMatch) {
      return res.status(401).json({
        success: false,
        message: 'Password attuale non corretta'
      });
    }

    // Hash nuova password
    const newPasswordHash = await bcrypt.hash(newPassword, 10);

    // Aggiorna password
    await db.query(
      'UPDATE users SET password_hash = $1, first_login = false WHERE id = $2',
      [newPasswordHash, req.user.id]
    );

    res.json({
      success: true,
      message: 'Password modificata con successo'
    });
  } catch (error) {
    console.error('Errore cambio password:', error);
    res.status(500).json({
      success: false,
      message: 'Errore durante il cambio password',
      error: error.message
    });
  }
};

module.exports = {
  register,
  login,
  logout,
  getMe,
  changePassword
};
