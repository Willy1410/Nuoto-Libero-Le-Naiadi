const db = require('../config/database');

async function migrate() {
  try {
    console.log('🚀 Avvio migrazione database...\n');

    // 1. Tabella users
    console.log('📝 Creazione tabella users...');
    await db.query(`
      CREATE TABLE IF NOT EXISTS users (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'segreteria', 'user')),
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        fiscal_code VARCHAR(16),
        birth_date DATE,
        birth_place VARCHAR(255),
        address TEXT,
        qr_code VARCHAR(50) UNIQUE,
        active BOOLEAN DEFAULT true,
        first_login BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);
    console.log('✅ Tabella users creata\n');

    // 2. Tabella packages
    console.log('📝 Creazione tabella packages...');
    await db.query(`
      CREATE TABLE IF NOT EXISTS packages (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID REFERENCES users(id) ON DELETE CASCADE,
        package_type VARCHAR(50) NOT NULL CHECK (package_type IN ('single', '10_entries', 'promo')),
        total_entries INTEGER NOT NULL,
        remaining_entries INTEGER NOT NULL,
        purchase_date DATE NOT NULL,
        expiry_date DATE NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'cash',
        active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);
    console.log('✅ Tabella packages creata\n');

    // 3. Tabella entries (log ingressi)
    console.log('📝 Creazione tabella entries...');
    await db.query(`
      CREATE TABLE IF NOT EXISTS entries (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID REFERENCES users(id) ON DELETE CASCADE,
        package_id UUID REFERENCES packages(id) ON DELETE CASCADE,
        entry_date DATE NOT NULL,
        entry_time TIME NOT NULL,
        staff_name VARCHAR(255),
        staff_id UUID REFERENCES users(id) ON DELETE SET NULL,
        remaining_after INTEGER,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);
    console.log('✅ Tabella entries creata\n');

    // 4. Tabella payments (log pagamenti)
    console.log('📝 Creazione tabella payments...');
    await db.query(`
      CREATE TABLE IF NOT EXISTS payments (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID REFERENCES users(id) ON DELETE CASCADE,
        package_id UUID REFERENCES packages(id) ON DELETE SET NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_date DATE NOT NULL,
        staff_name VARCHAR(255),
        staff_id UUID REFERENCES users(id) ON DELETE SET NULL,
        transaction_id VARCHAR(255),
        status VARCHAR(50) DEFAULT 'completed',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);
    console.log('✅ Tabella payments creata\n');

    // 5. Tabella documents
    console.log('📝 Creazione tabella documents...');
    await db.query(`
      CREATE TABLE IF NOT EXISTS documents (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID REFERENCES users(id) ON DELETE CASCADE,
        document_type VARCHAR(50) NOT NULL,
        file_path TEXT,
        upload_date DATE NOT NULL,
        expiry_date DATE,
        status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'valid', 'expired')),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );
    `);
    console.log('✅ Tabella documents creata\n');

    // 6. Indici per performance
    console.log('📝 Creazione indici...');
    await db.query(`
      CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
      CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
      CREATE INDEX IF NOT EXISTS idx_users_qr_code ON users(qr_code);
      CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
      CREATE INDEX IF NOT EXISTS idx_packages_user_id ON packages(user_id);
      CREATE INDEX IF NOT EXISTS idx_packages_active ON packages(active);
      CREATE INDEX IF NOT EXISTS idx_entries_user_id ON entries(user_id);
      CREATE INDEX IF NOT EXISTS idx_entries_date ON entries(entry_date);
      CREATE INDEX IF NOT EXISTS idx_payments_user_id ON payments(user_id);
      CREATE INDEX IF NOT EXISTS idx_payments_date ON payments(payment_date);
      CREATE INDEX IF NOT EXISTS idx_documents_user_id ON documents(user_id);
    `);
    console.log('✅ Indici creati\n');

    // 7. Trigger per updated_at
    console.log('📝 Creazione trigger updated_at...');
    await db.query(`
      CREATE OR REPLACE FUNCTION update_updated_at_column()
      RETURNS TRIGGER AS $$
      BEGIN
        NEW.updated_at = CURRENT_TIMESTAMP;
        RETURN NEW;
      END;
      $$ language 'plpgsql';

      DROP TRIGGER IF EXISTS update_users_updated_at ON users;
      CREATE TRIGGER update_users_updated_at 
        BEFORE UPDATE ON users 
        FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

      DROP TRIGGER IF EXISTS update_packages_updated_at ON packages;
      CREATE TRIGGER update_packages_updated_at 
        BEFORE UPDATE ON packages 
        FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

      DROP TRIGGER IF EXISTS update_documents_updated_at ON documents;
      CREATE TRIGGER update_documents_updated_at 
        BEFORE UPDATE ON documents 
        FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
    `);
    console.log('✅ Trigger creati\n');

    console.log('🎉 Migrazione completata con successo!\n');
    process.exit(0);
  } catch (error) {
    console.error('❌ Errore durante la migrazione:', error);
    process.exit(1);
  }
}

migrate();
