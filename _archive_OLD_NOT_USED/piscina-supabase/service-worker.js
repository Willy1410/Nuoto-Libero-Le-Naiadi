// =====================================================
// SERVICE WORKER - PWA Offline Support
// =====================================================
// Sistema Gestione Piscina
// Version: 1.0.0
// =====================================================

const CACHE_NAME = 'piscina-v1'
const RUNTIME_CACHE = 'piscina-runtime-v1'

// File statici da cachare all'installazione
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/login.html',
  '/css/style.css',
  '/css/auth.css',
  '/css/dashboard.css',
  '/js/supabase-client.js',
  '/assets/logo.png',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-512x512.png',
  '/manifest.json'
]

// URL che vanno sempre in rete (Supabase, API esterne)
const NETWORK_ONLY_URLS = [
  'supabase.co',
  'auth.supabase.co',
  'api.brevo.com'
]

// =====================================================
// INSTALL: Cache file statici
// =====================================================
self.addEventListener('install', (event) => {
  console.log('[SW] Installazione Service Worker v1.0.0')
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[SW] Cache file statici')
        return cache.addAll(STATIC_ASSETS)
      })
      .then(() => self.skipWaiting()) // Attiva immediatamente
  )
})

// =====================================================
// ACTIVATE: Pulisci cache vecchie
// =====================================================
self.addEventListener('activate', (event) => {
  console.log('[SW] Attivazione Service Worker v1.0.0')
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((name) => {
              // Rimuovi cache vecchie
              return name.startsWith('piscina-') && 
                     name !== CACHE_NAME && 
                     name !== RUNTIME_CACHE
            })
            .map((name) => {
              console.log('[SW] Rimozione cache vecchia:', name)
              return caches.delete(name)
            })
        )
      })
      .then(() => self.clients.claim()) // Prendi controllo immediatamente
  )
})

// =====================================================
// FETCH: Strategia cache
// =====================================================
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)

  // Solo GET requests
  if (request.method !== 'GET') return

  // Network-only per Supabase e API esterne
  if (NETWORK_ONLY_URLS.some((u) => url.href.includes(u))) {
    return event.respondWith(fetch(request))
  }

  // Strategia: Cache-First per asset statici
  if (STATIC_ASSETS.includes(url.pathname)) {
    event.respondWith(
      caches.match(request)
        .then((cached) => cached || fetch(request))
    )
    return
  }

  // Strategia: Network-First con fallback cache per HTML
  if (request.headers.get('Accept').includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Cache pagine HTML visitate
          const clonedResponse = response.clone()
          caches.open(RUNTIME_CACHE)
            .then((cache) => cache.put(request, clonedResponse))
          return response
        })
        .catch(() => {
          // Fallback: cerca in cache
          return caches.match(request)
            .then((cached) => {
              if (cached) return cached
              // Fallback finale: pagina offline
              return caches.match('/offline.html')
            })
        })
    )
    return
  }

  // Strategia: Cache-First con update in background per immagini
  if (request.headers.get('Accept').includes('image')) {
    event.respondWith(
      caches.match(request)
        .then((cached) => {
          // Restituisci cache + fetch in background
          const fetchPromise = fetch(request)
            .then((response) => {
              const clonedResponse = response.clone()
              caches.open(RUNTIME_CACHE)
                .then((cache) => cache.put(request, clonedResponse))
              return response
            })
          
          return cached || fetchPromise
        })
    )
    return
  }

  // Default: Network-First
  event.respondWith(
    fetch(request)
      .catch(() => caches.match(request))
  )
})

// =====================================================
// BACKGROUND SYNC (opzionale)
// =====================================================
self.addEventListener('sync', (event) => {
  console.log('[SW] Background sync:', event.tag)
  
  if (event.tag === 'sync-check-ins') {
    event.waitUntil(
      // Sincronizza check-in offline con server
      syncPendingCheckIns()
    )
  }
})

async function syncPendingCheckIns() {
  // Recupera check-in salvati offline
  const cache = await caches.open('pending-check-ins')
  const requests = await cache.keys()
  
  for (const request of requests) {
    try {
      await fetch(request)
      await cache.delete(request)
      console.log('[SW] Check-in sincronizzato:', request.url)
    } catch (error) {
      console.error('[SW] Errore sync check-in:', error)
    }
  }
}

// =====================================================
// PUSH NOTIFICATIONS (opzionale)
// =====================================================
self.addEventListener('push', (event) => {
  console.log('[SW] Push notification ricevuta')
  
  const data = event.data ? event.data.json() : {}
  const title = data.title || 'Sistema Piscina'
  const options = {
    body: data.body || 'Hai una nuova notifica',
    icon: '/assets/icons/icon-192x192.png',
    badge: '/assets/icons/badge-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      url: data.url || '/'
    },
    actions: [
      { action: 'open', title: 'Apri' },
      { action: 'close', title: 'Chiudi' }
    ]
  }
  
  event.waitUntil(
    self.registration.showNotification(title, options)
  )
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  
  if (event.action === 'open' || !event.action) {
    const url = event.notification.data.url
    event.waitUntil(
      clients.openWindow(url)
    )
  }
})

// =====================================================
// MESSAGE: Comunicazione con client
// =====================================================
self.addEventListener('message', (event) => {
  console.log('[SW] Messaggio ricevuto:', event.data)
  
  if (event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
  
  if (event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(RUNTIME_CACHE)
        .then((cache) => cache.addAll(event.data.urls))
    )
  }
  
  if (event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: '1.0.0' })
  }
})

// =====================================================
// UTILITY: Log info Service Worker
// =====================================================
console.log('[SW] Service Worker registrato:', {
  version: '1.0.0',
  caches: [CACHE_NAME, RUNTIME_CACHE],
  staticAssets: STATIC_ASSETS.length
})
