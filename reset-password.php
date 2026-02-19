<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Nuoto Libero</title>
    <link rel="icon" type="image/png" href="https://public.gensparkspace.com/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-header">
                <h1>Reset Password</h1>
                <p>Imposta una nuova password per il tuo account</p>
            </div>

            <div id="statusBox" class="login-note" style="display:none;"></div>

            <form id="resetForm" class="login-form">
                <div class="form-group">
                    <label for="newPassword"><i class="fas fa-lock"></i> Nuova password</label>
                    <input type="password" id="newPassword" required minlength="10" placeholder="Minimo 10 caratteri con maiuscola, minuscola, numero e simbolo">
                </div>
                <div class="form-group">
                    <label for="confirmPassword"><i class="fas fa-lock"></i> Conferma password</label>
                    <input type="password" id="confirmPassword" required minlength="10" placeholder="Ripeti la password">
                </div>
                <div class="form-group checkbox-group" style="margin-top:-8px;">
                    <label class="checkbox-label">
                        <input type="checkbox" id="togglePwd">
                        <span>Mostra password</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-key"></i> Aggiorna password
                </button>
            </form>

            <div class="login-footer">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Torna al login</a>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api';
        const token = new URLSearchParams(window.location.search).get('token') || '';
        const form = document.getElementById('resetForm');
        const statusBox = document.getElementById('statusBox');

        function showStatus(message, isError = false) {
            statusBox.style.display = 'block';
            statusBox.style.background = isError ? 'rgba(231, 76, 60, 0.12)' : 'rgba(46, 204, 113, 0.12)';
            statusBox.style.borderLeftColor = isError ? '#e74c3c' : '#2ecc71';
            statusBox.textContent = message;
        }

        async function validateToken() {
            if (!token) {
                showStatus('Token di reset mancante.', true);
                form.classList.add('hidden');
                return;
            }

            try {
                const res = await fetch(`${API_URL}/auth.php?action=validate-reset-token&token=${encodeURIComponent(token)}`);
                const data = await res.json();
                if (!data.success || !data.valid) {
                    showStatus('Token non valido o scaduto. Richiedi un nuovo reset password.', true);
                    form.classList.add('hidden');
                }
            } catch (error) {
                showStatus('Errore durante la verifica del token.', true);
                form.classList.add('hidden');
            }
        }

        document.getElementById('togglePwd').addEventListener('change', function () {
            const type = this.checked ? 'text' : 'password';
            document.getElementById('newPassword').type = type;
            document.getElementById('confirmPassword').type = type;
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            const strongPassword = newPassword.length >= 10
                && /[a-z]/.test(newPassword)
                && /[A-Z]/.test(newPassword)
                && /\d/.test(newPassword)
                && /[^a-zA-Z\d]/.test(newPassword);

            if (!strongPassword) {
                showStatus('Password non valida: minimo 10 caratteri con maiuscola, minuscola, numero e simbolo.', true);
                return;
            }

            if (newPassword !== confirmPassword) {
                showStatus('Le password non coincidono.', true);
                return;
            }

            const button = form.querySelector('button[type="submit"]');
            const original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aggiornamento...';

            try {
                const res = await fetch(`${API_URL}/auth.php?action=reset-password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token, new_password: newPassword })
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Errore reset password');
                }

                showStatus(data.message || 'Password aggiornata con successo.');
                form.classList.add('hidden');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1800);
            } catch (error) {
                showStatus(error.message, true);
                button.disabled = false;
                button.innerHTML = original;
            }
        });

        validateToken();
    </script>
</body>
</html>
