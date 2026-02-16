# Cartella Assets - PDF e File Scaricabili

Questa cartella deve contenere i file PDF che gli utenti possono scaricare dalla pagina "Moduli".

## File Richiesti

Crea i seguenti file PDF e inseriscili in questa cartella:

1. **modulo-iscrizione.pdf**
   - Modulo di iscrizione al nuoto libero
   - Deve includere: dati anagrafici, recapiti, consenso GDPR

2. **regolamento-piscina.pdf**
   - Regolamento completo della struttura
   - Norme di comportamento, sicurezza, igiene

3. **informativa-privacy.pdf**
   - Informativa completa ai sensi del GDPR
   - Trattamento dati personali

4. **certificato-medico-info.pdf**
   - Informazioni su come ottenere il certificato medico
   - Elenco medici convenzionati (opzionale)

5. **liberatoria-minori.pdf**
   - Liberatoria per minori di 18 anni
   - Da firmare da entrambi i genitori/tutore

6. **listino-prezzi.pdf**
   - Listino prezzi completo e dettagliato
   - Eventuali promozioni e convenzioni

## Come Creare i PDF

### Opzione 1: Microsoft Word/LibreOffice
1. Crea il documento con il contenuto desiderato
2. File > Esporta come PDF
3. Salva con il nome corretto

### Opzione 2: Google Docs
1. Crea il documento su Google Docs
2. File > Scarica > PDF
3. Rinomina con il nome corretto

### Opzione 3: Template Online
- Usa servizi come Canva, Adobe Express
- Cerca template per moduli, regolamenti, ecc.
- Esporta come PDF

## Note Importanti

⚠️ **I nomi dei file devono corrispondere ESATTAMENTE** a quelli elencati sopra, altrimenti i link nella pagina `moduli.html` non funzioneranno.

✅ Per modificare i nomi dei file, edita anche i link in `moduli.html`:

```html
<a href="assets/NOME-FILE.pdf" class="btn btn-primary" download>
```

## Dimensioni Consigliate

- Mantieni i PDF sotto i 2 MB per download veloce
- Risoluzione: 150-300 DPI sufficiente per schermo
- Formato: A4 (21 x 29.7 cm) standard italiano

## Privacy e Sicurezza

❗ **Non includere nei PDF:**
- Dati sensibili reali di utenti
- Informazioni bancarie
- Password o credenziali

✅ **Puoi includere:**
- Logo e branding aziendale
- Informazioni di contatto pubbliche
- Istruzioni e linee guida generali

## Aggiornamento PDF

Quando aggiorni un PDF:
1. Sostituisci il file con la nuova versione
2. Mantieni lo stesso nome file
3. Aggiorna la data in `moduli.html` se necessario:

```html
<span><i class="fas fa-calendar"></i> Ultima revisione: GG/MM/AAAA</span>
```

---

**Hai bisogno di aiuto per creare i PDF?**
Consulta il README.md principale per maggiori informazioni o contatta il supporto tecnico.
