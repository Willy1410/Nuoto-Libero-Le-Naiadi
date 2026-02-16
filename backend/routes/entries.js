const express = require('express');
const router = express.Router();
const { body } = require('express-validator');
const entriesController = require('../controllers/entriesController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');
const { validate } = require('../middleware/validator');

// Validatori
const registerEntryValidation = [
  body('userId').isUUID().withMessage('ID utente non valido'),
  validate
];

const purchasePackageValidation = [
  body('userId').isUUID().withMessage('ID utente non valido'),
  body('packageType').isIn(['single', '10_entries', 'promo']).withMessage('Tipo pacchetto non valido'),
  body('paymentMethod').isIn(['cash', 'card', 'paypal', 'stripe']).withMessage('Metodo pagamento non valido'),
  validate
];

// Routes
router.post('/register', authenticateToken, authorizeRoles('admin', 'segreteria'), registerEntryValidation, entriesController.registerEntry);
router.post('/purchase', authenticateToken, authorizeRoles('admin', 'segreteria'), purchasePackageValidation, entriesController.purchasePackage);
router.get('/report/daily', authenticateToken, authorizeRoles('admin', 'segreteria'), entriesController.getDailyReport);

module.exports = router;
