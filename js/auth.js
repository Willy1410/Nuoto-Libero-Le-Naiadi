// Sistema di autenticazione con LocalStorage (simulazione database)

// Database utenti simulato
const USERS_DB = {
    'admin': {
        id: 'admin-001',
        username: 'admin',
        password: 'admin123',
        role: 'admin',
        name: 'Amministratore',
        email: 'admin@glisqualetti.it'
    },
    'segreteria': {
        id: 'staff-001',
        username: 'segreteria',
        password: 'segreteria123',
        role: 'segreteria',
        name: 'Maria Bianchi',
        email: 'segreteria@glisqualetti.it'
    },
    'mario.rossi': {
        id: 'user-001',
        username: 'mario.rossi',
        password: 'password123',
        role: 'user',
        name: 'Mario Rossi',
        email: 'mario.rossi@email.it',
        phone: '+39 333 1234567',
        fiscalCode: 'RSSMRA85M01H501Z',
        birthDate: '1985-08-01',
        address: 'Via Roma 123, Pescara',
        packageId: 'pkg-001',
        packageType: '10_entries',
        totalEntries: 10,
        remainingEntries: 8,
        purchaseDate: '2026-01-15',
        expiryDate: '2026-07-15',
        price: 100,
        qrCode: 'USR001'
    },
    'laura.bianchi': {
        id: 'user-002',
        username: 'laura.bianchi',
        password: 'password123',
        role: 'user',
        name: 'Laura Bianchi',
        email: 'laura.b@email.it',
        phone: '+39 345 9876543',
        fiscalCode: 'BNCLRA90D45H501K',
        birthDate: '1990-04-05',
        address: 'Via Garibaldi 45, Spoltore',
        packageId: 'pkg-002',
        packageType: '10_entries',
        totalEntries: 10,
        remainingEntries: 2,
        purchaseDate: '2025-12-20',
        expiryDate: '2026-06-20',
        price: 100,
        qrCode: 'USR002'
    },
    'giuseppe.verdi': {
        id: 'user-003',
        username: 'giuseppe.verdi',
        password: 'password123',
        role: 'user',
        name: 'Giuseppe Verdi',
        email: 'g.verdi@email.it',
        phone: '+39 338 5551234',
        fiscalCode: 'VRDGPP80A01H501M',
        birthDate: '1980-01-01',
        address: 'Corso Umberto 78, Pescara',
        packageId: 'pkg-003',
        packageType: '10_entries',
        totalEntries: 10,
        remainingEntries: 0,
        purchaseDate: '2025-08-10',
        expiryDate: '2026-02-10',
        price: 100,
        qrCode: 'USR003'
    },
    'anna.ferrari': {
        id: 'user-004',
        username: 'anna.ferrari',
        password: 'password123',
        role: 'user',
        name: 'Anna Ferrari',
        email: 'anna.ferrari@email.it',
        phone: '+39 340 7778899',
        fiscalCode: 'FRRNN88T48H501R',
        birthDate: '1988-12-08',
        address: 'Via Venezia 56, Montesilvano',
        packageId: 'pkg-004',
        packageType: 'promo',
        totalEntries: 3,
        remainingEntries: 3,
        purchaseDate: '2026-02-10',
        expiryDate: '2026-08-31',
        price: 30,
        qrCode: 'USR004'
    }
};

// Log ingressi giornaliero
let dailyEntries = JSON.parse(localStorage.getItem('dailyEntries')) || [];
let dailyCash = parseFloat(localStorage.getItem('dailyCash')) || 0;

// Funzioni autenticazione
function login(username, password, targetUserId = null) {
    const user = USERS_DB[username];
    
    if (!user || user.password !== password) {
        return { success: false, message: 'Credenziali non valide' };
    }
    
    // Salva sessione
    const session = {
        userId: user.id,
        username: user.username,
        role: user.role,
        name: user.name,
        loginTime: new Date().toISOString()
    };
    
    // Se targetUserId è presente (scan QR), salvalo
    if (targetUserId) {
        session.targetUserId = targetUserId;
    }
    
    localStorage.setItem('session', JSON.stringify(session));
    
    return { success: true, user: session };
}

function logout() {
    localStorage.removeItem('session');
    window.location.href = 'login.html';
}

function getSession() {
    const sessionData = localStorage.getItem('session');
    return sessionData ? JSON.parse(sessionData) : null;
}

function isLoggedIn() {
    return getSession() !== null;
}

function requireAuth(allowedRoles = []) {
    const session = getSession();
    
    if (!session) {
        window.location.href = 'login.html';
        return null;
    }
    
    if (allowedRoles.length > 0 && !allowedRoles.includes(session.role)) {
        alert('Non hai i permessi per accedere a questa pagina');
        redirectToDashboard(session.role);
        return null;
    }
    
    return session;
}

function redirectToDashboard(role) {
    switch(role) {
        case 'admin':
            window.location.href = 'dashboard-admin.html';
            break;
        case 'segreteria':
            window.location.href = 'dashboard-segreteria.html';
            break;
        case 'user':
            window.location.href = 'dashboard-user.html';
            break;
        default:
            window.location.href = 'index.html';
    }
}

// Funzioni gestione utenti
function getUserByQR(qrCode) {
    return Object.values(USERS_DB).find(u => u.qrCode === qrCode);
}

function getUserById(userId) {
    return Object.values(USERS_DB).find(u => u.id === userId);
}

function getUserByUsername(username) {
    return USERS_DB[username];
}

function getAllUsers() {
    return Object.values(USERS_DB).filter(u => u.role === 'user');
}

function updateUser(userId, updates) {
    const user = Object.values(USERS_DB).find(u => u.id === userId);
    if (user) {
        Object.assign(user, updates);
        // Simula salvataggio (in produzione: API call)
        console.log('User updated:', user);
        return true;
    }
    return false;
}

// Funzioni gestione ingressi
function decrementEntry(userId, staffName = 'Sistema') {
    const user = getUserById(userId);
    
    if (!user) {
        return { success: false, message: 'Utente non trovato' };
    }
    
    if (user.remainingEntries <= 0) {
        return { success: false, message: 'Nessun ingresso rimanente' };
    }
    
    const today = new Date();
    const expiry = new Date(user.expiryDate);
    
    if (today > expiry) {
        return { success: false, message: 'Pacchetto scaduto' };
    }
    
    // Decrementa ingresso
    user.remainingEntries--;
    
    // Log ingresso
    const entry = {
        id: 'entry-' + Date.now(),
        userId: user.id,
        userName: user.name,
        date: new Date().toISOString(),
        time: new Date().toLocaleTimeString('it-IT'),
        staffName: staffName,
        remainingAfter: user.remainingEntries
    };
    
    dailyEntries.push(entry);
    localStorage.setItem('dailyEntries', JSON.stringify(dailyEntries));
    
    return { 
        success: true, 
        message: 'Ingresso registrato',
        remainingEntries: user.remainingEntries,
        entry: entry
    };
}

// Funzioni pagamenti
function addCashPayment(userId, amount, packageType, staffName) {
    const user = getUserById(userId);
    
    if (!user) {
        return { success: false, message: 'Utente non trovato' };
    }
    
    // Aggiorna pacchetto utente
    const updates = {
        packageType: packageType,
        price: amount,
        purchaseDate: new Date().toISOString().split('T')[0],
        paymentMethod: 'cash'
    };
    
    if (packageType === '10_entries') {
        updates.totalEntries = 10;
        updates.remainingEntries = 10;
        updates.expiryDate = new Date(Date.now() + 180*24*60*60*1000).toISOString().split('T')[0]; // 6 mesi
    } else if (packageType === 'promo') {
        updates.totalEntries = 3;
        updates.remainingEntries = 3;
        const promoExpiry = new Date('2026-08-31');
        updates.expiryDate = promoExpiry.toISOString().split('T')[0];
    } else if (packageType === 'single') {
        updates.totalEntries = 1;
        updates.remainingEntries = 1;
        updates.expiryDate = new Date(Date.now() + 30*24*60*60*1000).toISOString().split('T')[0]; // 30 giorni
    }
    
    updateUser(userId, updates);
    
    // Aggiungi incasso giornaliero
    dailyCash += amount;
    localStorage.setItem('dailyCash', dailyCash.toString());
    
    const payment = {
        id: 'pay-' + Date.now(),
        userId: user.id,
        userName: user.name,
        amount: amount,
        packageType: packageType,
        date: new Date().toISOString(),
        staffName: staffName,
        method: 'cash'
    };
    
    const payments = JSON.parse(localStorage.getItem('dailyPayments')) || [];
    payments.push(payment);
    localStorage.setItem('dailyPayments', JSON.stringify(payments));
    
    return { success: true, message: 'Pagamento registrato', payment: payment };
}

// Report giornaliero
function getDailyReport() {
    const entries = JSON.parse(localStorage.getItem('dailyEntries')) || [];
    const payments = JSON.parse(localStorage.getItem('dailyPayments')) || [];
    const cash = parseFloat(localStorage.getItem('dailyCash')) || 0;
    
    const today = new Date().toISOString().split('T')[0];
    
    const todayEntries = entries.filter(e => e.date.startsWith(today));
    const todayPayments = payments.filter(p => p.date.startsWith(today));
    
    return {
        date: today,
        totalEntries: todayEntries.length,
        totalCash: cash,
        entries: todayEntries,
        payments: todayPayments,
        users: getAllUsers()
    };
}

function clearDailyReport() {
    localStorage.setItem('dailyEntries', JSON.stringify([]));
    localStorage.setItem('dailyPayments', JSON.stringify([]));
    localStorage.setItem('dailyCash', '0');
    dailyEntries = [];
    dailyCash = 0;
}

// Statistiche
function getStats() {
    const users = getAllUsers();
    const entries = JSON.parse(localStorage.getItem('dailyEntries')) || [];
    const payments = JSON.parse(localStorage.getItem('dailyPayments')) || [];
    
    const today = new Date().toISOString().split('T')[0];
    const todayEntries = entries.filter(e => e.date.startsWith(today));
    
    return {
        totalUsers: users.length,
        activeUsers: users.filter(u => u.remainingEntries > 0).length,
        expiredUsers: users.filter(u => {
            const expiry = new Date(u.expiryDate);
            return expiry < new Date();
        }).length,
        todayEntries: todayEntries.length,
        totalRevenue: users.reduce((sum, u) => sum + (u.price || 0), 0),
        todayCash: parseFloat(localStorage.getItem('dailyCash')) || 0
    };
}

// Export funzioni
window.Auth = {
    login,
    logout,
    getSession,
    isLoggedIn,
    requireAuth,
    redirectToDashboard,
    getUserByQR,
    getUserById,
    getUserByUsername,
    getAllUsers,
    updateUser,
    decrementEntry,
    addCashPayment,
    getDailyReport,
    clearDailyReport,
    getStats,
    USERS_DB
};
