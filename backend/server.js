const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware di sicurezza
app.use(helmet());

// CORS
app.use(cors({
  origin: process.env.CORS_ORIGIN || 'http://localhost:8080',
  credentials: true
}));

// Rate limiting
const limiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000, // 15 minuti
  max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100,
  message: { success: false, message: 'Troppi richieste, riprova più tardi' }
});

app.use('/api/', limiter);

// Logging
if (process.env.NODE_ENV === 'development') {
  app.use(morgan('dev'));
} else {
  app.use(morgan('combined'));
}

// Body parsing
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/users', require('./routes/users'));
app.use('/api/entries', require('./routes/entries'));

// Health check
app.get('/api/health', (req, res) => {
  res.json({
    success: true,
    message: 'API Gli Squaletti funzionante',
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

// 404 handler
app.use('/api/*', (req, res) => {
  res.status(404).json({
    success: false,
    message: 'Endpoint non trovato'
  });
});

// Error handler
app.use((err, req, res, next) => {
  console.error('Errore server:', err);
  res.status(err.status || 500).json({
    success: false,
    message: err.message || 'Errore interno del server',
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
});

// Avvio server
app.listen(PORT, () => {
  console.log(`
╔═══════════════════════════════════════════╗
║   🏊 Gli Squaletti API Server            ║
║                                           ║
║   🚀 Server avviato su porta ${PORT}        ║
║   🌍 Ambiente: ${process.env.NODE_ENV || 'development'}         ║
║   📡 CORS: ${process.env.CORS_ORIGIN || 'http://localhost:8080'}
║                                           ║
║   ✅ Pronto per le richieste!            ║
╚═══════════════════════════════════════════╝
  `);
});

module.exports = app;
