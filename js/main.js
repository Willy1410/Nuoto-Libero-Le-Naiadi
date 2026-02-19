/**
 * Nuoto libero Le Naiadi - Main JavaScript
 * Handles all interactive functionality
 */

// ===================================
// Global State
// ===================================
const state = {
    currentGalleryIndex: 0,
    galleryImages: [],
    selectedPackage: null
};

// ===================================
// DOM Ready
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    initLazyImages();
    initNavigation();
    initCookieBanner();
    initFAQ();
    initGallery();
    initContactForm();
    initScrollEffects();
});

function initLazyImages() {
    const images = document.querySelectorAll('img:not([loading])');
    images.forEach((img, index) => {
        img.setAttribute('loading', index < 2 ? 'eager' : 'lazy');
    });
}

// ===================================
// Navigation
// ===================================
function initNavigation() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    const header = document.getElementById('header');
    
    // Hamburger menu toggle
    if (hamburger && navMenu) {
        const setMenuOpen = (isOpen) => {
            hamburger.classList.toggle('active', isOpen);
            navMenu.classList.toggle('active', isOpen);
            document.body.classList.toggle('menu-open', isOpen);
        };

        hamburger.addEventListener('click', function() {
            setMenuOpen(!navMenu.classList.contains('active'));
        });
        
        // Close menu when clicking on a link
        const navLinks = navMenu.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                setMenuOpen(false);
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!hamburger.contains(event.target) && !navMenu.contains(event.target)) {
                setMenuOpen(false);
            }
        });

        // Ensure mobile menu state is reset on desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 991) {
                setMenuOpen(false);
            }
        });
    }
    
    // Sticky header with scroll effect
    if (header) {
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    }
}

// ===================================
// Cookie Banner
// ===================================
function initCookieBanner() {
    const banner = document.getElementById('cookieBanner');
    const acceptButton = document.getElementById('acceptCookies');
    
    if (!banner || !acceptButton) return;
    
    // Check if cookie consent was already given
    const cookieConsent = localStorage.getItem('cookieConsent');
    
    if (!cookieConsent) {
        // Show banner after a short delay
        setTimeout(() => {
            banner.classList.add('show');
        }, 1000);
    }
    
    // Handle accept button
    acceptButton.addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'accepted');
        localStorage.setItem('cookieConsentDate', new Date().toISOString());
        banner.classList.remove('show');
    });
}

// ===================================
// FAQ Accordion
// ===================================
function initFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        if (question) {
            question.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                
                // Close all FAQ items
                faqItems.forEach(i => i.classList.remove('active'));
                
                // Toggle current item
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        }
    });
}

// ===================================
// Gallery & Lightbox
// ===================================
function initGallery() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    
    // Store all images
    state.galleryImages = Array.from(galleryItems);
    
    // Filter functionality
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Filter gallery items
            galleryItems.forEach(item => {
                const category = item.dataset.category;
                
                if (filter === 'all' || category === filter) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 10);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
            
            // Update gallery images array based on filter
            if (filter === 'all') {
                state.galleryImages = Array.from(galleryItems);
            } else {
                state.galleryImages = Array.from(galleryItems).filter(
                    item => item.dataset.category === filter
                );
            }
        });
    });
    
    // Open lightbox
    galleryItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            const img = item.querySelector('img');
            const caption = item.querySelector('.gallery-overlay p');
            
            if (img && lightbox && lightboxImage && lightboxCaption) {
                lightboxImage.src = img.src;
                lightboxCaption.textContent = caption ? caption.textContent : '';
                state.currentGalleryIndex = state.galleryImages.indexOf(item);
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    
    // Close lightbox
    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }
    
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
    }
    
    // Navigation
    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', () => {
            state.currentGalleryIndex = (state.currentGalleryIndex - 1 + state.galleryImages.length) % state.galleryImages.length;
            updateLightboxImage();
        });
    }
    
    if (lightboxNext) {
        lightboxNext.addEventListener('click', () => {
            state.currentGalleryIndex = (state.currentGalleryIndex + 1) % state.galleryImages.length;
            updateLightboxImage();
        });
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!lightbox || !lightbox.classList.contains('active')) return;
        
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft' && lightboxPrev) lightboxPrev.click();
        if (e.key === 'ArrowRight' && lightboxNext) lightboxNext.click();
    });
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function updateLightboxImage() {
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    
    if (state.galleryImages[state.currentGalleryIndex]) {
        const currentItem = state.galleryImages[state.currentGalleryIndex];
        const img = currentItem.querySelector('img');
        const caption = currentItem.querySelector('.gallery-overlay p');
        
        if (img && lightboxImage) {
            lightboxImage.src = img.src;
        }
        if (caption && lightboxCaption) {
            lightboxCaption.textContent = caption.textContent;
        }
    }
}

// ===================================
// Contact Form
// ===================================
function initContactForm() {
    const contactForm = document.getElementById('contactForm');
    
    if (!contactForm) return;
    
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formMessage = document.getElementById('formMessage');
        const submitButton = contactForm.querySelector('button[type="submit"]');
        
        // Check honeypot field
        const honeypot = contactForm.querySelector('input[name="website"]');
        if (honeypot && honeypot.value) {
            return;
        }
        
        // Get form data
        const formData = {
            name: contactForm.querySelector('#contactName').value,
            email: contactForm.querySelector('#contactEmail').value,
            phone: contactForm.querySelector('#contactPhone').value,
            subject: contactForm.querySelector('#contactSubject').value,
            message: contactForm.querySelector('#contactMessage').value,
            privacy: contactForm.querySelector('#contactPrivacy').checked,
            timestamp: new Date().toISOString()
        };
        
        // Disable submit button
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Invio in corso...';
        }
        
        try {
            const response = await fetch('api/contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ...formData,
                    website: honeypot ? honeypot.value : ''
                })
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Invio non riuscito');
            }

            const params = new URLSearchParams({
                name: formData.name || '',
                subject: formData.subject || '',
                email: formData.email || ''
            });
            window.location.href = `grazie-contatto.php?${params.toString()}`;
            return;

        } catch (error) {
            // Error
            if (formMessage) {
                formMessage.className = 'form-message error';
                formMessage.textContent = error.message || 'Si e verificato un errore. Riprova piu tardi o contattaci direttamente.';
            }
        } finally {
            // Re-enable submit button
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Invia Messaggio';
            }
        }
    });
}

// ===================================
// Scroll Effects
// ===================================
function initScrollEffects() {
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#' || href === '') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                const headerHeight = document.getElementById('header')?.offsetHeight || 0;
                const targetPosition = target.offsetTop - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements
    document.querySelectorAll('.advantage-card, .step, .pricing-card, .rule-card, .document-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// ===================================
// Utility Functions
// ===================================

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Generate unique ID
function generateOrderId() {
    return 'GLI-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9).toUpperCase();
}

// Validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate phone (Italian)
function validatePhone(phone) {
    const re = /^[\d\s+()-]{8,}$/;
    return re.test(phone);
}

// Show notification
function showNotification(message, type = 'info') {
    if (window.NuotoLiberoUI && typeof window.NuotoLiberoUI.toast === 'function') {
        window.NuotoLiberoUI.toast(message, type);
        return;
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

async function alertDialog(message, title = 'Avviso') {
    if (window.NuotoLiberoUI && typeof window.NuotoLiberoUI.alert === 'function') {
        await window.NuotoLiberoUI.alert(message, { title });
        return true;
    }
    showNotification(message, 'info');
    return true;
}

async function confirmDialog(message, title = 'Conferma') {
    if (window.NuotoLiberoUI && typeof window.NuotoLiberoUI.confirm === 'function') {
        return window.NuotoLiberoUI.confirm(message, { title });
    }
    return true;
}

async function promptDialog(message, options = {}) {
    if (window.NuotoLiberoUI && typeof window.NuotoLiberoUI.prompt === 'function') {
        return window.NuotoLiberoUI.prompt(message, options);
    }
    return null;
}

if (!document.getElementById('gs-fallback-toast-style')) {
    const style = document.createElement('style');
    style.id = 'gs-fallback-toast-style';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Export functions for use in other scripts
window.NuotoLibero = {
    formatCurrency,
    generateOrderId,
    validateEmail,
    validatePhone,
    showNotification,
    alertDialog,
    confirmDialog,
    promptDialog
};

