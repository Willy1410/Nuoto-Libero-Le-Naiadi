/**
 * Gli Squaletti - Richiesta pacchetti ingressi
 * Flusso pubblico non e-commerce: richiesta + verifica segreteria.
 */

const paymentState = {
    selectedPackage: null,
    packagePrice: 0,
    packageName: ''
};

document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('packagesSection')) return;

    initPackageSelection();
    initCheckoutForm();
    initPaymentMethods();
});

function initPackageSelection() {
    const packageCards = document.querySelectorAll('.package-card');
    const packagesSection = document.getElementById('packagesSection');
    const checkoutSection = document.getElementById('checkoutSection');

    packageCards.forEach(card => {
        const selectButton = card.querySelector('.select-package');
        if (!selectButton) return;

        selectButton.addEventListener('click', function () {
            const packageId = card.dataset.package;
            const packagePrice = parseFloat(card.dataset.price);
            const packageName = card.dataset.name;

            paymentState.selectedPackage = packageId;
            paymentState.packagePrice = packagePrice;
            paymentState.packageName = packageName;

            updateOrderSummary(packageName, packagePrice);

            if (packagesSection && checkoutSection) {
                packagesSection.style.display = 'none';
                checkoutSection.style.display = 'block';
                checkoutSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            packageCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
        });
    });

    const backButton = document.getElementById('backToPackages');
    if (backButton) {
        backButton.addEventListener('click', function () {
            if (packagesSection && checkoutSection) {
                checkoutSection.style.display = 'none';
                packagesSection.style.display = 'block';
                packagesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
}

function formatCurrency(value) {
    if (window.GliSqualetti && typeof window.GliSqualetti.formatCurrency === 'function') {
        return window.GliSqualetti.formatCurrency(value);
    }
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));
}

function updateOrderSummary(packageName, price) {
    const summaryPackage = document.getElementById('summaryPackage');
    const summaryPrice = document.getElementById('summaryPrice');
    const summaryTotal = document.getElementById('summaryTotal');

    if (summaryPackage) summaryPackage.textContent = packageName;
    if (summaryPrice) summaryPrice.textContent = formatCurrency(price);
    if (summaryTotal) summaryTotal.textContent = formatCurrency(price);
}

function initCheckoutForm() {
    const checkoutForm = document.getElementById('checkoutForm');
    if (!checkoutForm) return;

    checkoutForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = getFormData();
        if (!validateFormData(formData)) {
            return;
        }

        if (!paymentState.selectedPackage) {
            showNotification('Seleziona prima un pacchetto.', 'error');
            return;
        }

        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;

        try {
            if (paymentMethod === 'bonifico') {
                await handleBankTransferNotice(formData);
                return;
            }

            await handleInStoreRequest(formData);
        } catch (error) {
            showNotification(error.message || 'Errore durante l\'invio richiesta.', 'error');
        }
    });
}

function initPaymentMethods() {
    const paymentOptions = document.querySelectorAll('input[name="paymentMethod"]');
    const bankTransferContainer = document.getElementById('bank-transfer-container');
    const instoreContainer = document.getElementById('instore-payment-container');

    paymentOptions.forEach(option => {
        option.addEventListener('change', function () {
            if (bankTransferContainer) bankTransferContainer.style.display = 'none';
            if (instoreContainer) instoreContainer.style.display = 'none';

            const selectedMethod = this.value;
            if (selectedMethod === 'bonifico') {
                if (bankTransferContainer) bankTransferContainer.style.display = 'block';
                return;
            }
            if (instoreContainer) instoreContainer.style.display = 'block';
        });
    });

    const selectedOption = document.querySelector('input[name="paymentMethod"]:checked');
    if (selectedOption) {
        selectedOption.dispatchEvent(new Event('change'));
    }
}

function getFormData() {
    return {
        firstName: document.getElementById('firstName')?.value.trim(),
        lastName: document.getElementById('lastName')?.value.trim(),
        email: document.getElementById('email')?.value.trim(),
        phone: document.getElementById('phone')?.value.trim(),
        notes: document.getElementById('notes')?.value.trim(),
        termsAccept: document.getElementById('termsAccept')?.checked,
        privacyAccept: document.getElementById('privacyAccept')?.checked,
        marketingAccept: document.getElementById('marketingAccept')?.checked
    };
}

function validateEmail(value) {
    if (window.GliSqualetti && typeof window.GliSqualetti.validateEmail === 'function') {
        return window.GliSqualetti.validateEmail(value);
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || ''));
}

function validatePhone(value) {
    if (window.GliSqualetti && typeof window.GliSqualetti.validatePhone === 'function') {
        return window.GliSqualetti.validatePhone(value);
    }
    return /^[\d\s+()-]{8,}$/.test(String(value || ''));
}

function showNotification(message, type) {
    if (window.GliSqualetti && typeof window.GliSqualetti.showNotification === 'function') {
        window.GliSqualetti.showNotification(message, type);
        return;
    }
    alert(message);
}

function validateFormData(data) {
    if (!data.firstName || !data.lastName) {
        showNotification('Inserisci nome e cognome.', 'error');
        return false;
    }
    if (!validateEmail(data.email)) {
        showNotification('Inserisci un\'email valida.', 'error');
        return false;
    }
    if (!validatePhone(data.phone)) {
        showNotification('Inserisci un numero di telefono valido.', 'error');
        return false;
    }
    if (!data.termsAccept || !data.privacyAccept) {
        showNotification('Devi accettare termini e privacy.', 'error');
        return false;
    }
    return true;
}

async function handleInStoreRequest(formData) {
    const submitButton = document.getElementById('instore-submit');
    const original = submitButton ? submitButton.innerHTML : '';

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio richiesta...';
    }

    try {
        const orderId = window.GliSqualetti && typeof window.GliSqualetti.generateOrderId === 'function'
            ? window.GliSqualetti.generateOrderId()
            : ('GLI-' + Date.now());

        const orderData = {
            orderId,
            ...formData,
            package: paymentState.packageName,
            price: paymentState.packagePrice,
            paymentMethod: 'instore',
            status: 'pending',
            createdAt: new Date().toISOString()
        };

        await notifyOrderConfirmationEmail(orderData);
        redirectToConfirmation(orderId, 'instore', orderData);
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = original;
        }
    }
}

async function handleBankTransferNotice(formData) {
    const transferReference = document.getElementById('bonificoReference')?.value.trim();
    const transferDate = document.getElementById('bonificoDate')?.value;
    const submitButton = document.getElementById('bonifico-submit');
    const original = submitButton ? submitButton.innerHTML : '';

    if (!transferReference || !transferDate) {
        throw new Error('Inserisci riferimento e data bonifico.');
    }

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio notifica...';
    }

    try {
        const response = await fetch('api/bonifico-notify.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ...formData,
                package: paymentState.packageName,
                amount: paymentState.packagePrice,
                transferReference,
                transferDate
            })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Invio notifica bonifico non riuscito.');
        }

        const orderId = result.orderId || (window.GliSqualetti?.generateOrderId?.() || ('GLI-' + Date.now()));
        const orderData = {
            orderId,
            ...formData,
            package: paymentState.packageName,
            price: paymentState.packagePrice,
            paymentMethod: 'bonifico',
            transferReference,
            transferDate,
            status: 'pending',
            createdAt: new Date().toISOString()
        };

        redirectToConfirmation(orderId, 'bonifico', orderData);
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = original;
        }
    }
}

async function notifyOrderConfirmationEmail(orderData) {
    const response = await fetch('api/order-confirmation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Invio email conferma non riuscito.');
    }

    return true;
}

function redirectToConfirmation(orderId, method, orderData) {
    const message = method === 'bonifico'
        ? 'Abbiamo ricevuto la notifica bonifico. La segreteria confermerà l\'attivazione.'
        : 'Richiesta registrata. Completa iscrizione e contributo in reception.';

    const confirmationHTML = `
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Richiesta Registrata - Gli Squaletti</title>
            <link rel="icon" type="image/png" href="https://www.genspark.ai/api/files/s/s3WpPfgP">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body { margin:0; font-family:Poppins, sans-serif; background:linear-gradient(135deg,#00a8e8,#0077b6); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
                .card { background:#fff; max-width:640px; width:100%; border-radius:16px; box-shadow:0 20px 40px rgba(0,0,0,.2); padding:28px; }
                .ok { width:72px; height:72px; border-radius:50%; background:#16a34a; color:#fff; display:flex; align-items:center; justify-content:center; font-size:34px; margin:0 auto 18px; }
                h1 { margin:0 0 8px; text-align:center; color:#0077b6; }
                p { color:#334155; }
                .box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin:16px 0; }
                .row { display:flex; justify-content:space-between; gap:12px; margin-bottom:8px; font-size:14px; }
                .row:last-child { margin-bottom:0; }
                .actions { margin-top:20px; display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
                .btn { display:inline-block; padding:10px 16px; border-radius:999px; text-decoration:none; font-weight:600; }
                .btn-primary { background:#00a8e8; color:#fff; }
                .btn-secondary { border:2px solid #00a8e8; color:#00a8e8; background:#fff; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="ok">✓</div>
                <h1>Richiesta Registrata</h1>
                <p style="text-align:center;">${message}</p>
                <div class="box">
                    <div class="row"><strong>Codice richiesta</strong><span>${orderId}</span></div>
                    <div class="row"><strong>Pacchetto</strong><span>${orderData.package}</span></div>
                    <div class="row"><strong>Contributo</strong><span>${formatCurrency(orderData.price)}</span></div>
                    <div class="row"><strong>Email</strong><span>${orderData.email}</span></div>
                </div>
                <div class="actions">
                    <a href="index.html" class="btn btn-primary">Torna alla Home</a>
                    <a href="moduli.html" class="btn btn-secondary">Apri Moduli</a>
                </div>
            </div>
        </body>
        </html>
    `;

    const confirmationWindow = window.open('', '_self');
    confirmationWindow.document.write(confirmationHTML);
    confirmationWindow.document.close();
}
