const jwt = require('jsonwebtoken');

// Middleware per verificare JWT token
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

  if (!token) {
    return res.status(401).json({ 
      success: false, 
      message: 'Token di autenticazione mancante' 
    });
  }

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ 
        success: false, 
        message: 'Token non valido o scaduto' 
      });
    }
    
    req.user = user; // { id, username, role }
    next();
  });
};

// Middleware per verificare ruoli specifici
const authorizeRoles = (...allowedRoles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ 
        success: false, 
        message: 'Autenticazione richiesta' 
      });
    }

    if (!allowedRoles.includes(req.user.role)) {
      return res.status(403).json({ 
        success: false, 
        message: 'Non hai i permessi per accedere a questa risorsa' 
      });
    }

    next();
  };
};

// Middleware per verificare che l'utente acceda solo ai propri dati
const authorizeOwnerOrRole = (...allowedRoles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ 
        success: false, 
        message: 'Autenticazione richiesta' 
      });
    }

    const requestedUserId = req.params.userId || req.params.id;
    
    // Admin e segreteria possono accedere a tutti i dati
    if (allowedRoles.includes(req.user.role)) {
      return next();
    }

    // User può accedere solo ai propri dati
    if (req.user.id === requestedUserId) {
      return next();
    }

    return res.status(403).json({ 
      success: false, 
      message: 'Non puoi accedere ai dati di altri utenti' 
    });
  };
};

module.exports = {
  authenticateToken,
  authorizeRoles,
  authorizeOwnerOrRole
};
