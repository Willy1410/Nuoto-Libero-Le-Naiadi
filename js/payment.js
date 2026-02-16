/**
 * Gli Squaletti - Payment Handler
 * Manages package selection and payment processing (PayPal, Stripe, In-Store)
 */

// ===================================
// Configuration (REPLACE WITH YOUR KEYS)
// ===================================
const PAYMENT_CONFIG = {
    // IMPORTANT: Replace these with your actual keys from PayPal and Stripe dashboards
    stripe: {
        publishableKey: 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY_HERE', // Replace with your Stripe publishable key
    },
    paypal: {
        clientId: 'YOUR_PAYPAL_CLIENT_ID', // Already configured in HTML script tag
    }
};

// ===================================
// State Management
// ===================================
const paymentState = {
    selectedPackage: null,
    packagePrice: 0,
    packageName: '',
    stripeInstance: null,
    cardElement: null
};

// ===================================
// Initialize Payment Page
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    // Only run on packages page
    if (!document.getElementById('packagesSection')) return;
    
    initPackageSelection();
    initCheckoutForm();
    initPaymentMethods();
});

// ===================================
// Package Selection
// ===================================
function initPackageSelection() {
    const packageCards = document.querySelectorAll('.package-card');
    const packagesSection = document.getElementById('packagesSection');
    const checkoutSection = document.getElementById('checkoutSection');
    
    packageCards.forEach(card => {
        const selectButton = card.querySelector('.select-package');
        
        if (selectButton) {
            selectButton.addEventListener('click', function() {
                const packageId = card.dataset.package;
                const packagePrice = parseFloat(card.dataset.price);
                const packageName = card.dataset.name;
                
                // Store package info
                paymentState.selectedPackage = packageId;
                paymentState.packagePrice = packagePrice;
                paymentState.packageName = packageName;
                
                // Update order summary
                updateOrderSummary(packageName, packagePrice);
                
                // Show checkout section
                if (packagesSection && checkoutSection) {
                    packagesSection.style.display = 'none';
                    checkoutSection.style.display = 'block';
                    
                    // Scroll to checkout
                    checkoutSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // Highlight selected package
                packageCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
            });
        }
    });
    
    // Back to packages button
    const backButton = document.getElementById('backToPackages');
    if (backButton) {
        backButton.addEventListener('click', function() {
            if (packagesSection && checkoutSection) {
                checkoutSection.style.display = 'none';
                packagesSection.style.display = 'block';
                packagesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }
}

// ===================================
// Update Order Summary
// ===================================
function updateOrderSummary(packageName, price) {
    const summaryPackage = document.getElementById('summaryPackage');
    const summaryPrice = document.getElementById('summaryPrice');
    const summaryTotal = document.getElementById('summaryTotal');
    
    if (summaryPackage) summaryPackage.textContent = packageName;
    if (summaryPrice) summaryPrice.textContent = window.GliSqualetti.formatCurrency(price);
    if (summaryTotal) summaryTotal.textContent = window.GliSqualetti.formatCurrency(price);
}

// ===================================
// Checkout Form Validation
// ===================================
function initCheckoutForm() {
    const checkoutForm = document.getElementById('checkoutForm');
    if (!checkoutForm) return;
    
    // Form validation
    checkoutForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const termsAccept = document.getElementById('termsAccept').checked;
        const privacyAccept = document.getElementById('privacyAccept').checked;
        
        // Validation
        if (!firstName || !lastName) {
            window.GliSqualetti.showNotification('Inserisci nome e cognome', 'error');
            return;
        }
        
        if (!window.GliSqualetti.validateEmail(email)) {
            window.GliSqualetti.showNotification('Inserisci un\'email valida', 'error');
            return;
        }
        
        if (!window.GliSqualetti.validatePhone(phone)) {
            window.GliSqualetti.showNotification('Inserisci un numero di telefono valido', 'error');
            return;
        }
        
        if (!termsAccept || !privacyAccept) {
            window.GliSqualetti.showNotification('Devi accettare i termini e la privacy policy', 'error');
            return;
        }
        
        // Get selected payment method
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
        
        if (paymentMethod === 'instore') {
            handleInStorePayment();
        }
        // PayPal and Stripe are handled by their respective buttons
    });
}

// ===================================
// Payment Method Selection
// ===================================
function initPaymentMethods() {
    const paymentOptions = document.querySelectorAll('input[name="paymentMethod"]');
    const paypalContainer = document.getElementById('paypal-button-container');
    const stripeContainer = document.getElementById('stripe-payment-container');
    const instoreContainer = document.getElementById('instore-payment-container');
    
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Hide all payment containers
            if (paypalContainer) paypalContainer.style.display = 'none';
            if (stripeContainer) stripeContainer.style.display = 'none';
            if (instoreContainer) instoreContainer.style.display = 'none';
            
            // Show selected payment container
            const selectedMethod = this.value;
            
            switch(selectedMethod) {
                case 'paypal':
                    if (paypalContainer) {
                        paypalContainer.style.display = 'block';
                        initPayPal();
                    }
                    break;
                case 'stripe':
                    if (stripeContainer) {
                        stripeContainer.style.display = 'block';
                        initStripe();
                    }
                    break;
                case 'instore':
                    if (instoreContainer) {
                        instoreContainer.style.display = 'block';
                    }
                    break;
            }
        });
    });
}

// ===================================
// PayPal Integration
// ===================================
function initPayPal() {
    const container = document.getElementById('paypal-button-container');
    if (!container) return;
    
    // Clear existing buttons
    container.innerHTML = '';
    
    // Check if PayPal SDK is loaded
    if (typeof paypal === 'undefined') {
        container.innerHTML = '<p style="color: red;">Errore: PayPal SDK non caricato. Verifica la configurazione.</p>';
        console.error('PayPal SDK not loaded. Check the client ID in pacchetti.html');
        return;
    }
    
    // Render PayPal buttons
    paypal.Buttons({
        style: {
            layout: 'vertical',
            color: 'blue',
            shape: 'rect',
            label: 'paypal'
        },
        createOrder: function(data, actions) {
            // Get form data
            const formData = getFormData();
            
            if (!validateFormData(formData)) {
                return Promise.reject('Form validation failed');
            }
            
            return actions.order.create({
                purchase_units: [{
                    description: paymentState.packageName,
                    amount: {
                        currency_code: 'EUR',
                        value: paymentState.packagePrice.toFixed(2)
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Payment successful
                handlePaymentSuccess('paypal', details.id, details);
            });
        },
        onError: function(err) {
            console.error('PayPal error:', err);
            window.GliSqualetti.showNotification('Errore nel pagamento PayPal. Riprova.', 'error');
        }
    }).render('#paypal-button-container');
}

// ===================================
// Stripe Integration
// ===================================
function initStripe() {
    if (!paymentState.stripeInstance) {
        // Check if Stripe is loaded
        if (typeof Stripe === 'undefined') {
            console.error('Stripe.js not loaded');
            return;
        }
        
        // Initialize Stripe (replace with your publishable key)
        paymentState.stripeInstance = Stripe(PAYMENT_CONFIG.stripe.publishableKey);
        
        // Create card element
        const elements = paymentState.stripeInstance.elements();
        paymentState.cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Poppins", sans-serif',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#e74c3c'
                }
            }
        });
        
        paymentState.cardElement.mount('#card-element');
        
        // Handle real-time validation errors
        paymentState.cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
    }
    
    // Handle form submission
    const submitButton = document.getElementById('stripe-submit');
    if (submitButton) {
        submitButton.onclick = handleStripePayment;
    }
}

async function handleStripePayment() {
    const formData = getFormData();
    
    if (!validateFormData(formData)) {
        return;
    }
    
    const submitButton = document.getElementById('stripe-submit');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Elaborazione...';
    
    try {
        // In a real implementation, you would:
        // 1. Create a payment intent on your server
        // 2. Confirm the payment with Stripe
        
        // For demo purposes, simulate payment
        console.log('Stripe payment would be processed here');
        
        // Simulated successful payment
        const simulatedPaymentIntentId = 'pi_' + Math.random().toString(36).substr(2, 9);
        
        // Show success (in production, wait for actual Stripe confirmation)
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        handlePaymentSuccess('stripe', simulatedPaymentIntentId, formData);
        
    } catch (error) {
        console.error('Stripe error:', error);
        window.GliSqualetti.showNotification('Errore nel pagamento. Riprova.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-lock"></i> Paga con Carta';
    }
}

// ===================================
// In-Store Payment
// ===================================
function handleInStorePayment() {
    const formData = getFormData();
    
    if (!validateFormData(formData)) {
        return;
    }
    
    const submitButton = document.getElementById('instore-submit');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Elaborazione...';
    }
    
    // Simulate order creation
    setTimeout(() => {
        const orderId = window.GliSqualetti.generateOrderId();
        
        // Store order info (in production, send to server)
        const orderData = {
            orderId,
            ...formData,
            package: paymentState.packageName,
            price: paymentState.packagePrice,
            paymentMethod: 'instore',
            status: 'pending',
            createdAt: new Date().toISOString()
        };
        
        console.log('In-store order created:', orderData);
        
        // Redirect to confirmation page
        redirectToConfirmation(orderId, 'instore', orderData);
    }, 1500);
}

// ===================================
// Payment Success Handler
// ===================================
function handlePaymentSuccess(method, transactionId, details) {
    const formData = getFormData();
    const orderId = window.GliSqualetti.generateOrderId();
    
    // Prepare order data
    const orderData = {
        orderId,
        transactionId,
        paymentMethod: method,
        ...formData,
        package: paymentState.packageName,
        price: paymentState.packagePrice,
        status: 'completed',
        createdAt: new Date().toISOString()
    };
    
    // In production, send this to your server
    console.log('Order completed:', orderData);
    
    // Store in localStorage for confirmation page
    localStorage.setItem('lastOrder', JSON.stringify(orderData));
    
    // Redirect to confirmation page
    redirectToConfirmation(orderId, method, orderData);
}

// ===================================
// Helper Functions
// ===================================
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

function validateFormData(data) {
    if (!data.firstName || !data.lastName) {
        window.GliSqualetti.showNotification('Inserisci nome e cognome', 'error');
        return false;
    }
    
    if (!window.GliSqualetti.validateEmail(data.email)) {
        window.GliSqualetti.showNotification('Inserisci un\'email valida', 'error');
        return false;
    }
    
    if (!window.GliSqualetti.validatePhone(data.phone)) {
        window.GliSqualetti.showNotification('Inserisci un numero di telefono valido', 'error');
        return false;
    }
    
    if (!data.termsAccept || !data.privacyAccept) {
        window.GliSqualetti.showNotification('Devi accettare i termini e la privacy policy', 'error');
        return false;
    }
    
    return true;
}

function redirectToConfirmation(orderId, method, orderData) {
    // Create a simple confirmation page or modal
    const confirmationHTML = `
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ordine Confermato - Gli Squaletti</title>
            <link rel="icon" type="image/png" href="https://www.genspark.ai/api/files/s/s3WpPfgP">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #00a8e8, #0077b6); padding: 2rem;">
                <div style="background: white; padding: 3rem; border-radius: 16px; box-shadow: 0 20px 25px rgba(0,0,0,0.15); max-width: 600px; text-align: center;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #2ecc71, #27ae60); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; font-size: 3rem; color: white;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h1 style="color: #0077b6; margin-bottom: 1rem;">Ordine Confermato!</h1>
                    <p style="font-size: 1.125rem; margin-bottom: 2rem; color: #6c757d;">
                        ${method === 'instore' 
                            ? 'La tua prenotazione è stata registrata con successo.' 
                            : 'Il tuo pagamento è stato completato con successo.'}
                    </p>
                    
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: left;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <strong>Codice Ordine:</strong>
                            <span style="color: #00a8e8; font-weight: 700;">${orderId}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <strong>Pacchetto:</strong>
                            <span>${orderData.package}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <strong>Importo:</strong>
                            <span>${window.GliSqualetti.formatCurrency(orderData.price)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <strong>Email:</strong>
                            <span>${orderData.email}</span>
                        </div>
                    </div>
                    
                    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #00a8e8;">
                        <p style="margin: 0; color: #495057;">
                            <i class="fas fa-info-circle" style="color: #00a8e8; margin-right: 0.5rem;"></i>
                            ${method === 'instore' 
                                ? 'Presentati in reception con questo codice ordine per completare il pagamento e attivare il pacchetto.' 
                                : 'Riceverai una email di conferma con tutti i dettagli. Presentati in reception con questo codice per attivare il tuo pacchetto.'}
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="index.html" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #00a8e8, #0077b6); color: white; border-radius: 50px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-home"></i> Torna alla Home
                        </a>
                        <a href="contatti.html" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: white; color: #00a8e8; border: 2px solid #00a8e8; border-radius: 50px; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-map-marker-alt"></i> Come Raggiungerci
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `;
    
    // Open confirmation in new window or redirect
    const confirmationWindow = window.open('', '_self');
    confirmationWindow.document.write(confirmationHTML);
    confirmationWindow.document.close();
}
