const express = require('express');
const router = express.Router();
const { body } = require('express-validator');
const authController = require('../controllers/authController');
const { authenticateToken } = require('../middleware/auth');
const { validate } = require('../middleware/validator');

// Validatori
const registerValidation = [
  body('username').trim().isLength({ min: 3 }).withMessage('Username deve essere almeno 3 caratteri'),
  body('email').isEmail().withMessage('Email non valida'),
  body('password').isLength({ min: 6 }).withMessage('Password deve essere almeno 6 caratteri'),
  body('name').trim().notEmpty().withMessage('Nome richiesto'),
  validate
];

const loginValidation = [
  body('username').trim().notEmpty().withMessage('Username richiesto'),
  body('password').notEmpty().withMessage('Password richiesta'),
  validate
];

const changePasswordValidation = [
  body('oldPassword').notEmpty().withMessage('Password attuale richiesta'),
  body('newPassword').isLength({ min: 6 }).withMessage('Nuova password deve essere almeno 6 caratteri'),
  validate
];

// Routes
router.post('/register', registerValidation, authController.register);
router.post('/login', loginValidation, authController.login);
router.post('/logout', authController.logout);
router.get('/me', authenticateToken, authController.getMe);
router.post('/change-password', authenticateToken, changePasswordValidation, authController.changePassword);

module.exports = router;
