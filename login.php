<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$homeHref = 'landing.php';

if (appIsLandingMode()) {
    header('Location: area-riservata.php', true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Riservata - Gli Squaletti</title>
    <link rel="icon" type="image/png" href="https://public.gensparkspace.com/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <img src="https://public.gensparkspace.com/api/files/s/s3WpPfgP" alt="Gli Squaletti Logo" style="height: 80px; margin-bottom: 1rem;">
                <h1>Area Riservata</h1>
                <p>Accedi al tuo account Gli Squaletti</p>
            </div>

            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email"><i class="fas fa-user"></i> Email</label>
                    <input type="email" id="email" name="email" required placeholder="La tua email">
                </div>

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="La tua password">
                </div>

                <div class="form-group checkbox-group" style="margin-top:-8px;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="togglePassword">
                        <span>Mostra password</span>
                    </label>
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember">
                        <span>Ricordami</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Accedi
                </button>

                <div class="login-links">
                    <a href="#" id="forgotPasswordLink">Hai dimenticato la password?</a>
                </div>
            </form>

            <form id="forgotForm" class="login-form hidden" style="margin-top:1rem;">
                <div class="form-group">
                    <label for="forgotEmail"><i class="fas fa-envelope"></i> Email per reset password</label>
                    <input type="email" id="forgotEmail" name="forgotEmail" placeholder="Inserisci email account">
                </div>
                <button type="submit" class="btn btn-secondary btn-block">
                    <i class="fas fa-paper-plane"></i> Invia link reset
                </button>
                <div class="login-links" style="margin-top:0.75rem;">
                    <a href="#" id="backToLogin">Torna al login</a>
                </div>
            </form>

            <div class="login-divider"><span>oppure</span></div>

            <div class="login-signup">
                <p>Non hai ancora un account?</p>
                <a href="moduli.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-user-plus"></i> Moduli e Iscrizione
                </a>
            </div>

            <div class="login-footer">
                <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left"></i> Torna alla Home
                </a>
            </div>

        </div>
    </div>
    <script src="js/ui-modal.js"></script>
    <script>
        const API_URL = 'api';
        const ROLE_REDIRECTS = {
            admin: 'piscina-php/dashboard-admin.php',
            ufficio: 'piscina-php/dashboard-ufficio.php',
            segreteria: 'piscina-php/dashboard-ufficio.php',
            bagnino: 'piscina-php/dashboard-bagnino.php',
            utente: 'piscina-php/dashboard-utente.php',
            user: 'piscina-php/dashboard-utente.php'
        };

        const UI = window.GliSqualettiUI || {};

        async function uiAlert(message, title = 'Avviso') {
            if (typeof UI.alert === 'function') {
                await UI.alert(message, { title });
                return;
            }
        }

        async function uiPrompt(message, options = {}) {
            if (typeof UI.prompt === 'function') {
                return UI.prompt(message, options);
            }
            return null;
        }

        function redirectByRole(role) {
            window.location.href = ROLE_REDIRECTS[role] || 'piscina-php/dashboard-utente.php';
        }

        function safeParseJson(raw) {
            try {
                return JSON.parse(raw);
            } catch (error) {
                throw new Error('Risposta non valida dal server. Verifica configurazione API.');
            }
        }

        try {
            const token = localStorage.getItem('token');
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            if (token && user && user.ruolo) {
                redirectByRole(user.ruolo);
            }
        } catch (error) {
        }

        document.getElementById('togglePassword').addEventListener('change', function () {
            const pwd = document.getElementById('password');
            pwd.type = this.checked ? 'text' : 'password';
        });

        document.getElementById('loginForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';

            try {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                const response = await fetch(`${API_URL}/auth.php?action=login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const raw = await response.text();
                const data = safeParseJson(raw);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Credenziali non valide');
                }

                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));

                if (data.user && data.user.force_password_change) {
                    const newPassword = await uiPrompt(
                        'Primo accesso: imposta una nuova password (minimo 8 caratteri).',
                        {
                            title: 'Cambio password obbligatorio',
                            inputType: 'password',
                            confirmText: 'Aggiorna password',
                            cancelText: 'Annulla',
                            required: true,
                            validator: (value) => value.trim().length >= 8 ? '' : 'Password minima: 8 caratteri'
                        }
                    );

                    if (!newPassword) {
                        throw new Error('Cambio password obbligatorio non completato.');
                    }

                    const changeResponse = await fetch(`${API_URL}/auth.php?action=change-password`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${data.token}`
                        },
                        body: JSON.stringify({
                            old_password: password,
                            new_password: newPassword
                        })
                    });

                    const changeRaw = await changeResponse.text();
                    const changeData = safeParseJson(changeRaw);
                    if (!changeResponse.ok || !changeData.success) {
                        throw new Error(changeData.message || 'Cambio password obbligatorio non completato');
                    }

                    data.user.force_password_change = false;
                    localStorage.setItem('user', JSON.stringify(data.user));
                }

                redirectByRole(data.user.ruolo);
            } catch (error) {
                await uiAlert('Errore: ' + error.message, 'Accesso non riuscito');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });

        const loginForm = document.getElementById('loginForm');
        const forgotForm = document.getElementById('forgotForm');

        document.getElementById('forgotPasswordLink').addEventListener('click', function (e) {
            e.preventDefault();
            loginForm.classList.add('hidden');
            forgotForm.classList.remove('hidden');
            document.getElementById('forgotEmail').value = document.getElementById('email').value.trim();
        });

        document.getElementById('backToLogin').addEventListener('click', function (e) {
            e.preventDefault();
            forgotForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
        });

        document.getElementById('forgotForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const email = document.getElementById('forgotEmail').value.trim();
            if (!email) {
                await uiAlert('Inserisci una email valida.', 'Validazione');
                return;
            }

            const button = this.querySelector('button[type="submit"]');
            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio...';

            try {
                const response = await fetch(`${API_URL}/auth.php?action=forgot-password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                const raw = await response.text();
                const data = safeParseJson(raw);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Errore invio reset password');
                }

                await uiAlert(data.message || 'Se l\'email esiste, riceverai un link di reset.', 'Reset password');
                forgotForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
            } catch (error) {
                await uiAlert('Errore: ' + error.message, 'Reset password');
            } finally {
                button.disabled = false;
                button.innerHTML = original;
            }
        });
    </script>
</body>
</html>
