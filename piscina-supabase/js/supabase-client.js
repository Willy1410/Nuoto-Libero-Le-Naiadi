// =====================================================
// SUPABASE CLIENT - Configurazione
// =====================================================
// Sistema Gestione Piscina - Frontend
// =====================================================

// Importa Supabase JS Client da CDN
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

// =====================================================
// CONFIGURAZIONE
// =====================================================

// Variabili ambiente (production: leggere da .env)
const SUPABASE_URL = 'http://localhost:54321'  // Locale, in prod: https://your-project.supabase.co
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'  // Sostituire con anon key reale

// Inizializza client Supabase
export const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: true
  }
})

// =====================================================
// UTILITY: Ottieni sessione corrente
// =====================================================
export async function getSession() {
  const { data: { session }, error } = await supabase.auth.getSession()
  if (error) {
    console.error('Errore recupero sessione:', error)
    return null
  }
  return session
}

// =====================================================
// UTILITY: Ottieni utente con profilo e ruolo
// =====================================================
export async function getCurrentUser() {
  try {
    const session = await getSession()
    if (!session) return null

    // Fetch profilo con ruolo
    const { data: profilo, error } = await supabase
      .from('profili')
      .select(`
        *,
        ruolo:ruoli(nome, livello)
      `)
      .eq('id', session.user.id)
      .single()

    if (error) throw error

    return {
      ...session.user,
      profilo: profilo,
      ruolo: profilo.ruolo.nome,
      livello: profilo.ruolo.livello
    }
  } catch (error) {
    console.error('Errore recupero utente:', error)
    return null
  }
}

// =====================================================
// UTILITY: Verifica permessi
// =====================================================
export async function checkPermission(requiredLevel) {
  const user = await getCurrentUser()
  if (!user) return false
  return user.livello >= requiredLevel
}

// Livelli ruoli
export const ROLES = {
  UTENTE: 1,
  BAGNINO: 2,
  UFFICIO: 3,
  ADMIN: 4
}

// =====================================================
// UTILITY: Redirect se non autenticato
// =====================================================
export async function requireAuth(requiredLevel = ROLES.UTENTE) {
  const user = await getCurrentUser()
  
  if (!user) {
    // Non autenticato → redirect login
    window.location.href = '/login.html?redirect=' + encodeURIComponent(window.location.pathname)
    return null
  }

  if (user.livello < requiredLevel) {
    // Permessi insufficienti → redirect dashboard corretto
    redirectToDashboard(user.ruolo)
    return null
  }

  return user
}

// =====================================================
// UTILITY: Redirect dashboard basato su ruolo
// =====================================================
export function redirectToDashboard(roleName) {
  const dashboards = {
    'utente': '/utente/dashboard.html',
    'bagnino': '/bagnino/dashboard.html',
    'ufficio': '/ufficio/dashboard.html',
    'admin': '/admin/dashboard.html'
  }
  window.location.href = dashboards[roleName] || '/login.html'
}

// =====================================================
// UTILITY: Logout
// =====================================================
export async function logout() {
  try {
    const { error } = await supabase.auth.signOut()
    if (error) throw error
    window.location.href = '/login.html'
  } catch (error) {
    console.error('Errore logout:', error)
    alert('Errore durante il logout: ' + error.message)
  }
}

// =====================================================
// UTILITY: Formatta data italiana
// =====================================================
export function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('it-IT', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric' 
  })
}

// =====================================================
// UTILITY: Formatta timestamp italiano
// =====================================================
export function formatDateTime(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleString('it-IT', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// =====================================================
// UTILITY: Badge stato documenti
// =====================================================
export function getBadgeDocumento(stato) {
  const badges = {
    'da_inviare': '<span class="badge bg-danger">🔴 Da Inviare</span>',
    'in_attesa': '<span class="badge bg-warning">🟠 In Attesa</span>',
    'approvato': '<span class="badge bg-success">🟢 Approvato</span>',
    'rifiutato': '<span class="badge bg-danger">❌ Rifiutato</span>'
  }
  return badges[stato] || '<span class="badge bg-secondary">-</span>'
}

// =====================================================
// UTILITY: Badge stato pagamenti
// =====================================================
export function getBadgePagamento(stato) {
  const badges = {
    'in_attesa': '<span class="badge bg-warning">⏳ In Attesa</span>',
    'confermato': '<span class="badge bg-success">✅ Confermato</span>',
    'rifiutato': '<span class="badge bg-danger">❌ Rifiutato</span>'
  }
  return badges[stato] || '<span class="badge bg-secondary">-</span>'
}

// =====================================================
// UTILITY: Gestione errori Supabase
// =====================================================
export function handleSupabaseError(error) {
  console.error('Errore Supabase:', error)
  
  // Messaggi errore user-friendly
  const errorMessages = {
    'Invalid login credentials': 'Credenziali non valide',
    'User not found': 'Utente non trovato',
    'Email not confirmed': 'Email non confermata. Controlla la tua casella.',
    'new row violates row-level security policy': 'Permessi insufficienti per questa operazione'
  }

  const friendlyMessage = errorMessages[error.message] || error.message
  return friendlyMessage
}

// =====================================================
// UTILITY: Controllo documenti completi
// =====================================================
export async function checkDocumentiCompleti(userId) {
  try {
    // Conta documenti obbligatori
    const { data: tipiObbligatori, error: errorTipi } = await supabase
      .from('tipi_documento')
      .select('id')
      .eq('obbligatorio', true)

    if (errorTipi) throw errorTipi

    // Conta documenti approvati utente
    const { data: documentiApprovati, error: errorDoc } = await supabase
      .from('documenti_utente')
      .select('tipo_documento_id')
      .eq('user_id', userId)
      .eq('stato', 'approvato')

    if (errorDoc) throw errorDoc

    const approvatiIds = new Set(documentiApprovati.map(d => d.tipo_documento_id))
    const tuttiApprovati = tipiObbligatori.every(t => approvatiIds.has(t.id))

    return {
      completi: tuttiApprovati,
      totale: tipiObbligatori.length,
      approvati: documentiApprovati.length
    }
  } catch (error) {
    console.error('Errore controllo documenti:', error)
    return { completi: false, totale: 0, approvati: 0 }
  }
}

// =====================================================
// UTILITY: Determina fascia oraria corrente
// =====================================================
export function getFasciaOraria() {
  const ora = new Date().getHours()
  return ora < 14 ? 'mattina' : 'pomeriggio'
}

// =====================================================
// UTILITY: Verifica doppio check-in (4 ore)
// =====================================================
export async function checkDoppioCheckIn(userId) {
  try {
    const quattroOreFA = new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString()
    const fasciaCorrente = getFasciaOraria()

    const { data, error } = await supabase
      .from('check_ins')
      .select('id, timestamp')
      .eq('user_id', userId)
      .eq('fascia_oraria', fasciaCorrente)
      .gte('timestamp', quattroOreFA)
      .limit(1)
      .single()

    if (error && error.code !== 'PGRST116') throw error  // PGRST116 = no rows

    return {
      haCheckIn: !!data,
      ultimoCheckIn: data ? new Date(data.timestamp) : null
    }
  } catch (error) {
    console.error('Errore verifica doppio check-in:', error)
    return { haCheckIn: false, ultimoCheckIn: null }
  }
}

// =====================================================
// UTILITY: Activity Log
// =====================================================
export async function logActivity(azione, entita, entitaId, dettagli = {}) {
  try {
    const user = await getCurrentUser()
    if (!user) return

    await supabase
      .from('activity_log')
      .insert({
        user_id: user.id,
        azione,
        entita,
        entita_id: entitaId,
        dettagli,
        ip_address: null  // Frontend non può ottenere IP pubblico
      })
  } catch (error) {
    console.error('Errore log activity:', error)
  }
}

// =====================================================
// EXPORT GLOBALE (per compatibilità script legacy)
// =====================================================
window.supabase = supabase
window.SupabaseUtils = {
  getSession,
  getCurrentUser,
  checkPermission,
  requireAuth,
  logout,
  redirectToDashboard,
  formatDate,
  formatDateTime,
  getBadgeDocumento,
  getBadgePagamento,
  handleSupabaseError,
  checkDocumentiCompleti,
  getFasciaOraria,
  checkDoppioCheckIn,
  logActivity,
  ROLES
}

console.log('✅ Supabase Client inizializzato')
