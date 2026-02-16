const express = require('express');
const router = express.Router();
const { body } = require('express-validator');
const usersController = require('../controllers/usersController');
const { authenticateToken, authorizeRoles, authorizeOwnerOrRole } = require('../middleware/auth');
const { validate } = require('../middleware/validator');

// Validatori
const updateUserValidation = [
  body('email').optional().isEmail().withMessage('Email non valida'),
  body('name').optional().trim().notEmpty().withMessage('Nome non può essere vuoto'),
  validate
];

// Routes
router.get('/', authenticateToken, authorizeRoles('admin', 'segreteria'), usersController.getAllUsers);
router.get('/stats', authenticateToken, authorizeRoles('admin', 'segreteria'), usersController.getStats);
router.get('/qr/:qrCode', authenticateToken, usersController.getUserByQR);
router.get('/:id', authenticateToken, authorizeOwnerOrRole('admin', 'segreteria'), usersController.getUserById);
router.put('/:id', authenticateToken, authorizeOwnerOrRole('admin', 'segreteria'), updateUserValidation, usersController.updateUser);
router.delete('/:id', authenticateToken, authorizeRoles('admin'), usersController.deleteUser);

module.exports = router;
