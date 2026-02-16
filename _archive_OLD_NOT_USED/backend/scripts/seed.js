const db = require('../config/database');
const bcrypt = require('bcrypt');

async function seed() {
  try {
    console.log('🌱 Avvio seeding database...\n');

    // 1. Hash password
    const adminPassword = await bcrypt.hash('admin123', 10);
    const segreteriaPassword = await bcrypt.hash('segreteria123', 10);
    const userPassword = await bcrypt.hash('password123', 10);

    // 2. Inserisci Admin
    console.log('👤 Creazione utente Admin...');
    const adminResult = await db.query(`
      INSERT INTO users (username, email, password_hash, role, name, phone, qr_code)
      VALUES ($1, $2, $3, $4, $5, $6, $7)
      ON CONFLICT (username) DO NOTHING
      RETURNING id;
    `, ['admin', 'admin@glisqualetti.it', adminPassword, 'admin', 'Amministratore', '+39 123 456 789', 'ADM001']);
    console.log('✅ Admin creato\n');

    // 3. Inserisci Segreteria
    console.log('👤 Creazione utente Segreteria...');
    const segreteriaResult = await db.query(`
      INSERT INTO users (username, email, password_hash, role, name, phone, qr_code)
      VALUES ($1, $2, $3, $4, $5, $6, $7)
      ON CONFLICT (username) DO NOTHING
      RETURNING id;
    `, ['segreteria', 'segreteria@glisqualetti.it', segreteriaPassword, 'segreteria', 'Maria Bianchi', '+39 345 678 901', 'STF001']);
    console.log('✅ Segreteria creata\n');

    // 4. Inserisci Utenti Test
    console.log('👥 Creazione utenti test...\n');

    // Mario Rossi
    const marioResult = await db.query(`
      INSERT INTO users (
        username, email, password_hash, role, name, phone, fiscal_code, 
        birth_date, address, qr_code
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      ON CONFLICT (username) DO NOTHING
      RETURNING id;
    `, [
      'mario.rossi',
      'mario.rossi@email.it',
      userPassword,
      'user',
      'Mario Rossi',
      '+39 333 1234567',
      'RSSMRA85M01H501Z',
      '1985-08-01',
      'Via Roma 123, Pescara',
      'USR001'
    ]);

    if (marioResult.rows.length > 0) {
      const marioId = marioResult.rows[0].id;
      await db.query(`
        INSERT INTO packages (
          user_id, package_type, total_entries, remaining_entries,
          purchase_date, expiry_date, price, payment_method
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      `, [marioId, '10_entries', 10, 8, '2026-01-15', '2026-07-15', 100.00, 'card']);
      console.log('  ✅ Mario Rossi (8/10 ingressi)');
    }

    // Laura Bianchi
    const lauraResult = await db.query(`
      INSERT INTO users (
        username, email, password_hash, role, name, phone, fiscal_code,
        birth_date, address, qr_code
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      ON CONFLICT (username) DO NOTHING
      RETURNING id;
    `, [
      'laura.bianchi',
      'laura.b@email.it',
      userPassword,
      'user',
      'Laura Bianchi',
      '+39 345 9876543',
      'BNCLRA90D45H501K',
      '1990-04-05',
      'Via Garibaldi 45, Spoltore',
      'USR002'
    ]);

    if (lauraResult.rows.length > 0) {
      const lauraId = lauraResult.rows[0].id;
      await db.query(`
        INSERT INTO packages (
          user_id, package_type, total_entries, remaining_entries,
          purchase_date, expiry_date, price, payment_method
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      `, [lauraId, '10_entries', 10, 2, '2025-12-20', '2026-06-20', 100.00, 'cash']);
      console.log('  ✅ Laura Bianchi (2/10 ingressi)');
    }

    // Giuseppe Verdi
    const giuseppeResult = await db.query(`
      INSERT INTO users (
        username, email, password_hash, role, name, phone, fiscal_code,
        birth_date, address, qr_code
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      ON CONFLICT (username) DO NOTHING
      RETURNING id;
    `, [
      'giuseppe.verdi',
      'g.verdi@email.it',
      userPassword,
      'user',
      'Giuseppe Verdi',
      '+39 338 5551234',
      'VRDGPP80A01H501M',
      '1980-01-01',
      'Corso Umberto 78, Pescara',
      'USR003'
    ]);

    if (giuseppeResult.rows.length > 0) {
      const giuseppeId = giuseppeResult.rows[0].id;
      await db.query(`
        INSERT INTO packages (
          user_id, package_type, total_entries, remaining_entries,
          purchase_date, expiry_date, price, payment_method
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      `, [giuseppeId, '10_entries', 10, 0, '2025-08-10', '2026-02-10', 100.00, 'cash']);
      console.log('  ✅ Giuseppe Verdi (0/10 ingressi - SCADUTO)');
    }

    // Anna Ferrari
    const annaResult = await db.query(`
      INSERT INTO users (
        username, email, password_hash, role, name, phone, fiscal_code,
        birth_date, address, qr_code
      )
      VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
      ON CONFLICT (username) DO NOTHING
      RETURNING id;
    `, [
      'anna.ferrari',
      'anna.ferrari@email.it',
      userPassword,
      'user',
      'Anna Ferrari',
      '+39 340 7778899',
      'FRRNN88T48H501R',
      '1988-12-08',
      'Via Venezia 56, Montesilvano',
      'USR004'
    ]);

    if (annaResult.rows.length > 0) {
      const annaId = annaResult.rows[0].id;
      await db.query(`
        INSERT INTO packages (
          user_id, package_type, total_entries, remaining_entries,
          purchase_date, expiry_date, price, payment_method
        )
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
      `, [annaId, 'promo', 3, 3, '2026-02-10', '2026-08-31', 30.00, 'cash']);
      console.log('  ✅ Anna Ferrari (3/3 promo)');
    }

    console.log('\n🎉 Seeding completato con successo!\n');
    console.log('📊 Riepilogo:');
    console.log('  - 1 Admin');
    console.log('  - 1 Segreteria');
    console.log('  - 4 Utenti con pacchetti\n');
    
    process.exit(0);
  } catch (error) {
    console.error('❌ Errore durante il seeding:', error);
    process.exit(1);
  }
}

seed();
