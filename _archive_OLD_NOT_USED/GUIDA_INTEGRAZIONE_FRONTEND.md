# 🔌 Guida Integrazione Frontend → Backend

Questa guida mostra come aggiornare le altre pagine del frontend per usare le API reali invece di LocalStorage.

---

## 📝 Pattern da Seguire

### Struttura Tipica:

```html
<!-- Includi API client PRIMA di altri script -->
<script src="js/api-client.js"></script>
<script src="js/auth.js"></script> <!-- Mantieni per compatibilità QR -->
<script>
    // 1. Verifica autenticazione
    if (!API.isAuthenticated()) {
        window.location.href = 'login.html';
    }

    // 2. Carica dati
    async function loadData() {
        try {
            const response = await API.metodoAPI();
            // Gestisci risposta
            displayData(response.data);
        } catch (error) {
            alert('Errore: ' + error.message);
        }
    }

    // 3. Init
    loadData();
</script>
```

---

## 🎯 Esempi Pratici

### 1️⃣ **dashboard-admin.html**

**PRIMA (LocalStorage):**
```javascript
const stats = Auth.getStats();
document.getElementById('totalUsers').textContent = stats.totalUsers;
```

**DOPO (API):**
```javascript
async function loadStats() {
    try {
        const response = await API.getStats();
        const stats = response.data.stats;
        
        document.getElementById('totalUsers').textContent = stats.totalUsers;
        document.getElementById('activeUsers').textContent = stats.activeUsers;
        document.getElementById('todayEntries').textContent = stats.todayEntries;
        document.getElementById('totalRevenue').textContent = '€' + stats.totalRevenue.toFixed(2);
    } catch (error) {
        console.error('Errore caricamento stats:', error);
        showError('Impossibile caricare le statistiche');
    }
}

// Carica utenti
async function loadUsers() {
    try {
        const response = await API.getAllUsers({ role: 'user', page: 1, limit: 100 });
        const users = response.data.users;
        
        const tbody = document.querySelector('#usersTable tbody');
        tbody.innerHTML = '';
        
        users.forEach(user => {
            const row = `
                <tr>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>${user.remaining_entries || 0}/${user.total_entries || 0}</td>
                    <td><span class="badge">${user.active ? 'Attivo' : 'Inattivo'}</span></td>
                    <td>
                        <button onclick="viewUser('${user.id}')">Vedi</button>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    } catch (error) {
        console.error('Errore caricamento utenti:', error);
        showError('Impossibile caricare gli utenti');
    }
}

// Init
loadStats();
loadUsers();
```

---

### 2️⃣ **dashboard-segreteria.html**

**Registrazione Ingresso:**

```javascript
async function registerEntry(userId) {
    if (!confirm('Confermi la registrazione dell\'ingresso?')) {
        return;
    }

    try {
        // Mostra loading
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrazione...';

        const response = await API.registerEntry(userId);
        
        if (response.success) {
            alert('✅ Ingresso registrato! Rimangono ' + response.data.remainingEntries + ' ingressi.');
            window.location.reload(); // Ricarica per aggiornare dati
        }
    } catch (error) {
        alert('❌ Errore: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Conferma Ingresso';
    }
}
```

**Report Giornaliero:**

```javascript
async function loadDailyReport() {
    try {
        const response = await API.getDailyReport();
        const report = response.data;

        // Statistiche
        document.getElementById('reportTotalEntries').textContent = report.totalEntries;
        document.getElementById('reportTotalCash').textContent = '€' + report.totalCash.toFixed(2);

        // Dettaglio ingressi
        const entriesLog = document.getElementById('entriesLog');
        entriesLog.innerHTML = '';
        
        report.entries.forEach(entry => {
            const div = document.createElement('div');
            div.style.cssText = 'padding: 1rem; border-bottom: 1px solid #ddd;';
            div.innerHTML = `
                <strong>${entry.user_name}</strong><br>
                <small>Ore ${entry.entry_time} - Registrato da ${entry.staff_name}</small>
            `;
            entriesLog.appendChild(div);
        });

        // Utenti in scadenza
        const expiringDiv = document.getElementById('expiringUsers');
        expiringDiv.innerHTML = '<ul>' + 
            report.expiringUsers.map(u => 
                `<li>${u.name} - Scade il ${u.expiry_date} (${u.remaining_entries} ingressi)</li>`
            ).join('') + 
        '</ul>';

    } catch (error) {
        console.error('Errore report:', error);
        showError('Impossibile caricare il report');
    }
}
```

---

### 3️⃣ **dashboard-user.html**

```javascript
async function loadUserProfile() {
    try {
        const response = await API.getMe();
        const user = response.data.user;

        // Aggiorna UI
        document.querySelector('.dashboard-user-info strong').textContent = user.name;
        document.querySelector('.dashboard-user-info small').textContent = user.email;
        
        // Carica dati completi utente
        const detailsResponse = await API.getUserById(user.id);
        const details = detailsResponse.data;

        // Pacchetto attivo
        if (details.packages && details.packages.length > 0) {
            const activePackage = details.packages.find(p => p.active);
            if (activePackage) {
                document.getElementById('remainingEntries').textContent = activePackage.remaining_entries;
                document.getElementById('totalEntries').textContent = activePackage.total_entries;
                document.getElementById('expiryDate').textContent = activePackage.expiry_date;
            }
        }

        // QR Code
        document.getElementById('qrCodeText').textContent = user.qr_code || user.qrCode;

    } catch (error) {
        console.error('Errore profilo:', error);
        showError('Impossibile caricare il profilo');
    }
}

loadUserProfile();
```

---

### 4️⃣ **check-entry.html**

```javascript
// Al caricamento pagina
const urlParams = new URLSearchParams(window.location.search);
const userId = urlParams.get('id');

async function loadUser() {
    try {
        const response = await API.getUserById(userId);
        const user = response.data.user;
        const packages = response.data.packages;

        // Mostra dati utente
        document.getElementById('userName').textContent = user.name;
        document.getElementById('userEmail').textContent = user.email;

        // Pacchetto attivo
        const activePackage = packages.find(p => p.active);
        if (activePackage) {
            document.getElementById('remainingEntries').textContent = activePackage.remaining_entries;
            document.getElementById('totalEntries').textContent = activePackage.total_entries;
            document.getElementById('expiryDate').textContent = activePackage.expiry_date;

            // Controlla validità
            if (activePackage.remaining_entries === 0) {
                document.getElementById('warningNoEntries').style.display = 'block';
                document.getElementById('btnConfirm').disabled = true;
            }
        }
    } catch (error) {
        console.error('Errore:', error);
        alert('Impossibile caricare i dati dell\'utente');
        window.location.href = 'dashboard-segreteria.html';
    }
}

async function confirmEntry() {
    try {
        const response = await API.registerEntry(userId);
        
        if (response.success) {
            // Mostra successo
            document.getElementById('checkInSection').style.display = 'none';
            document.getElementById('successMessage').style.display = 'block';
            document.getElementById('successText').textContent = 
                `Ingresso registrato! Rimangono ${response.data.remainingEntries} ingressi.`;
        }
    } catch (error) {
        alert('Errore: ' + error.message);
    }
}

loadUser();
```

---

### 5️⃣ **user-detail.html**

```javascript
async function loadUserDetails() {
    const userId = new URLSearchParams(window.location.search).get('id');

    try {
        const response = await API.getUserById(userId);
        const user = response.data.user;
        const packages = response.data.packages;
        const recentEntries = response.data.recentEntries;

        // Dati anagrafici
        document.getElementById('fullName').textContent = user.name;
        document.getElementById('email').textContent = user.email;
        document.getElementById('phone').textContent = user.phone;
        document.getElementById('fiscalCode').textContent = user.fiscal_code;
        document.getElementById('birthDate').textContent = user.birth_date;
        document.getElementById('address').textContent = user.address;

        // Pacchetto attivo
        const activePackage = packages.find(p => p.active);
        if (activePackage) {
            document.getElementById('remainingBig').textContent = activePackage.remaining_entries;
            document.getElementById('totalBig').textContent = activePackage.total_entries;
            document.getElementById('packageTypeDetail').textContent = getPackageName(activePackage.package_type);
            document.getElementById('priceDetail').textContent = '€' + activePackage.price;
            document.getElementById('purchaseDateDetail').textContent = activePackage.purchase_date;
            document.getElementById('expiryDateDetail').textContent = activePackage.expiry_date;
        }

        // QR Code
        document.getElementById('qrCodeText').textContent = user.qr_code;

        // Storico ingressi
        displayEntries(recentEntries);

    } catch (error) {
        console.error('Errore:', error);
        alert('Impossibile caricare i dettagli dell\'utente');
    }
}

function getPackageName(type) {
    switch(type) {
        case '10_entries': return '10 Ingressi';
        case 'promo': return 'Promo Iscrizione';
        case 'single': return 'Singolo Ingresso';
        default: return type;
    }
}

loadUserDetails();
```

---

## 🎨 UI Feedback (Best Practices)

### Loading States:

```javascript
function showLoading(element) {
    element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Caricamento...';
    element.disabled = true;
}

function hideLoading(element, originalText) {
    element.innerHTML = originalText;
    element.disabled = false;
}

// Uso
const btn = document.getElementById('myBtn');
showLoading(btn);
try {
    await API.someMethod();
} finally {
    hideLoading(btn, 'Testo Originale');
}
```

### Error Messages:

```javascript
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-error';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i> ${message}
    `;
    document.body.prepend(errorDiv);
    
    setTimeout(() => errorDiv.remove(), 5000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success';
    successDiv.innerHTML = `
        <i class="fas fa-check-circle"></i> ${message}
    `;
    document.body.prepend(successDiv);
    
    setTimeout(() => successDiv.remove(), 3000);
}
```

### CSS per Alert:

```css
.alert {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 9999;
    animation: slideIn 0.3s ease;
}

.alert-error {
    background: #e74c3c;
    color: white;
}

.alert-success {
    background: #27ae60;
    color: white;
}

@keyframes slideIn {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
```

---

## 🔍 Debugging Tips

### Console Logging:

```javascript
// Aggiungi sempre try-catch con log
async function myFunction() {
    try {
        console.log('🚀 Chiamata API...');
        const response = await API.someMethod();
        console.log('✅ Risposta:', response);
        // ...
    } catch (error) {
        console.error('❌ Errore:', error);
        console.error('Stack:', error.stack);
        alert('Errore: ' + error.message);
    }
}
```

### Network Tab:

1. Apri DevTools (F12)
2. Tab "Network"
3. Filtra "XHR" o "Fetch"
4. Controlla:
   - Request URL
   - Request Headers (Authorization?)
   - Request Body
   - Response Status
   - Response Body

---

## ✅ Checklist Migrazione Pagina

- [ ] Incluso `<script src="js/api-client.js"></script>`
- [ ] Verificato `API.isAuthenticated()` all'inizio
- [ ] Sostituito `Auth.metodo()` con `await API.metodo()`
- [ ] Aggiunto try-catch per error handling
- [ ] Aggiunto loading state durante chiamate
- [ ] Testato con backend avviato
- [ ] Verificato in Console (F12) per errori
- [ ] Testato login/logout
- [ ] Testato con dati reali dal database

---

## 📚 API Client Reference Quick

```javascript
// Auth
await API.login(username, password)
await API.register(userData)
await API.logout()
await API.getMe()
await API.changePassword(oldPassword, newPassword)

// Users
await API.getAllUsers({ role: 'user', page: 1, limit: 100, search: 'mario' })
await API.getUserById(userId)
await API.getUserByQR(qrCode)
await API.updateUser(userId, { name: 'Nuovo Nome', email: '...' })
await API.deleteUser(userId)
await API.getStats()

// Entries
await API.registerEntry(userId)
await API.purchasePackage(userId, 'single', 'cash', 12)
await API.getDailyReport(date)

// Helpers
API.isAuthenticated()  // true/false
API.getUser()          // { id, username, role, ... }
API.getUserRole()      // 'admin' | 'segreteria' | 'user'
```

---

## 🎯 Priority Order

Aggiorna in questo ordine per testare progressivamente:

1. ✅ **login.html** (già fatto)
2. **dashboard-user.html** (più semplice)
3. **dashboard-admin.html** (stats + users list)
4. **user-detail.html** (dettaglio completo)
5. **check-entry.html** (registrazione ingresso)
6. **dashboard-segreteria.html** (più complessa)
7. **qr-verify.html** (router QR)

---

**Buon lavoro! 🚀**
