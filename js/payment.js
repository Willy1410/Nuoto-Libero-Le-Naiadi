/**
 * Gli Squaletti - Flusso iscrizione in struttura (no pagamento online)
 */

const paymentState = {
    selectedPackage: null,
    packagePrice: 0,
    mandatoryFee: 0,
    totalPrice: 0,
    packageName: ''
};

document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('packagesSection')) return;

    initPackageSelection();
    initCheckoutForm();
});

function initPackageSelection() {
    const packageCards = document.querySelectorAll('.package-card');
    const packagesSection = document.getElementById('packagesSection');
    const checkoutSection = document.getElementById('checkoutSection');

    packageCards.forEach(card => {
        const selectButton = card.querySelector('.select-package');
        if (!selectButton) return;

        selectButton.addEventListener('click', function () {
            const packagePrice = parseFloat(card.dataset.price || '0');
            const mandatoryFee = parseFloat(card.dataset.requiredFee || '0');
            const packageName = card.dataset.name || '10 Ingressi';
            const totalPrice = packagePrice + mandatoryFee;

            paymentState.selectedPackage = card.dataset.package || '10-ingressi';
            paymentState.packagePrice = packagePrice;
            paymentState.mandatoryFee = mandatoryFee;
            paymentState.totalPrice = totalPrice;
            paymentState.packageName = packageName;

            updateOrderSummary(packageName, packagePrice, mandatoryFee, totalPrice);

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

function updateOrderSummary(packageName, packageFee, mandatoryFee, totalPrice) {
    const summaryPackage = document.getElementById('summaryPackage');
    const summaryMandatoryFee = document.getElementById('summaryMandatoryFee');
    const summaryPackageFee = document.getElementById('summaryPackageFee');
    const legacySummaryPrice = document.getElementById('summaryPrice');
    const summaryTotal = document.getElementById('summaryTotal');

    if (summaryPackage) summaryPackage.textContent = packageName;
    if (summaryMandatoryFee) summaryMandatoryFee.textContent = formatCurrency(mandatoryFee);
    if (summaryPackageFee) summaryPackageFee.textContent = formatCurrency(packageFee);
    if (legacySummaryPrice) legacySummaryPrice.textContent = formatCurrency(packageFee);
    if (summaryTotal) summaryTotal.textContent = formatCurrency(totalPrice);
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
            showNotification('Seleziona prima il pacchetto da 10 ingressi.', 'error');
            return;
        }

        const submitButton = document.getElementById('instore-submit');
        const original = submitButton ? submitButton.innerHTML : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio richiesta...';
        }

        try {
            const payload = {
                firstName: formData.firstName,
                lastName: formData.lastName,
                email: formData.email,
                phone: formData.phone,
                note: formData.notes,
                termsAccept: formData.termsAccept,
                privacyAccept: formData.privacyAccept,
                package_name: paymentState.packageName,
                package_price: paymentState.totalPrice,
                package_fee: paymentState.packagePrice,
                registration_fee: paymentState.mandatoryFee,
                terms_accept: formData.termsAccept,
                privacy_accept: formData.privacyAccept
            };

            const response = await fetch('api/iscrizioni.php?action=submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Errore invio iscrizione');
            }

            redirectToConfirmation(result.iscrizione_id || ('ISCR-' + Date.now()), {
                package: paymentState.packageName,
                packageFee: paymentState.packagePrice,
                mandatoryFee: paymentState.mandatoryFee,
                total: paymentState.totalPrice,
                email: formData.email,
                message: result.message || 'Richiesta inviata correttamente'
            });
        } catch (error) {
            showNotification(error.message || 'Errore durante l\'invio della richiesta.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = original;
            }
        }
    });
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
    if (window.GliSqualettiUI && typeof window.GliSqualettiUI.toast === 'function') {
        window.GliSqualettiUI.toast(message, type || 'info');
        return;
    }
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

function redirectToConfirmation(enrollmentId, orderData) {
    const params = new URLSearchParams({
        id: String(enrollmentId || ''),
        package: String(orderData.package || ''),
        package_fee: String(orderData.packageFee || 0),
        mandatory_fee: String(orderData.mandatoryFee || 0),
        total: String(orderData.total || 0),
        email: String(orderData.email || '')
    });

    window.location.href = `grazie-iscrizione.php?${params.toString()}`;
}



