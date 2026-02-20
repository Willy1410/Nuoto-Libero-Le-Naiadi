<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
appEnforceFullSiteAccess();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
    <link rel="shortcut icon" href="favicon.ico">
<title>Verifica QR - Nuoto Libero</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f3f4f6;color:#111827;padding:16px}
.wrap{max-width:700px;margin:0 auto}
.card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
h1{font-size:24px;margin-bottom:10px}.alert{padding:10px;border-radius:8px;margin-bottom:10px;font-weight:600}.success{background:#d1fae5;color:#065f46}.error{background:#fee2e2;color:#991b1b}.info{background:#e0f2fe;color:#075985}
.btn{border:none;border-radius:8px;padding:9px 12px;font-weight:600;cursor:pointer;color:#fff;font-size:13px}.btn-primary{background:#2563eb}.btn-success{background:#059669}.btn-danger{background:#dc2626}
.tools{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
</style>
</head>
<body>
<div class="wrap">
<div class="card">
<h1>Verifica QR</h1>
<div id="status" class="alert info">Caricamento...</div>
<div id="content"></div>
<div class="tools"><button class="btn btn-primary" onclick="reload()">Aggiorna</button><button class="btn btn-danger" onclick="location.href='/login.php'">Login</button></div>
</div>
</div>
<script>
const API='/api';
const qsQr=new URLSearchParams(location.search).get('qr')||'';
const pathQr=((location.pathname.match(/\/q\/([A-Za-z0-9\-_]{16,128})/i)||[])[1]||'').trim();
const qr=(qsQr||pathQr||'').trim();
const token=localStorage.getItem('token');
let loading=false;
function setStatus(msg,cls='info'){status.className=`alert ${cls}`;status.textContent=msg;}

async function callApi(url,opt={}){const headers={...(opt.headers||{})};if(token){headers.Authorization=`Bearer ${token}`;}const r=await fetch(url,{...opt,headers});const t=await r.text();let d={};try{d=JSON.parse(t);}catch(e){throw new Error('Risposta API non valida')}if(!r.ok&&d.success!==true)throw new Error(d.message||`Errore ${r.status}`);return d;}

async function load(){if(loading)return;loading=true;try{if(!qr){setStatus('Parametro qr mancante','error');content.innerHTML='';return;}const d=await callApi(`${API}/checkin.php?qr=${encodeURIComponent(qr)}`);const u=d.utente||{},a=d.acquisto||{};setStatus(d.message||'OK',d.valid?'success':'error');const role=d.role||'guest';const ro=d.read_only!==false;const can=d.can_checkin===true;content.innerHTML=`<p><strong>Codice:</strong> ${qr}</p><p><strong>Ruolo attuale:</strong> ${role}</p><p><strong>Utente:</strong> ${u.nome||'-'} ${u.cognome||''}</p><p><strong>Telefono:</strong> ${u.telefono||'-'}</p><p><strong>Pacchetto:</strong> ${a.pacchetto_nome||'-'}</p><p><strong>Ingressi rimanenti:</strong> ${a.ingressi_rimanenti ?? '-'}</p><p><strong>Scadenza:</strong> ${a.data_scadenza||'-'}</p><p><strong>Modalita:</strong> ${ro?'read-only':'check-in abilitato'}</p>`;
if(can){const box=document.createElement('div');box.className='tools';box.innerHTML='<button class="btn btn-success" id="checkBtn">Conferma check-in</button>';content.appendChild(box);document.getElementById('checkBtn').addEventListener('click',doCheckin);} }
catch(e){setStatus(e.message,'error');content.innerHTML='';}
finally{loading=false;}}

async function doCheckin(){try{const d=await callApi(`${API}/checkin.php`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({qr_code:qr})});setStatus(`Check-in registrato. Ingressi rimasti: ${d.ingressi_rimanenti}`,'success');await load();}catch(e){setStatus(e.message,'error');}}
function reload(){load();}
load();
</script>
</body>
</html>
