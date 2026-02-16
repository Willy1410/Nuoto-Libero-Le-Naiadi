<?php
// =====================================================
// USERS CONTROLLER - Gestione Utenti
// =====================================================

require_once '../config.php';

session_start();

$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Router
switch ($action) {
    case 'list':
        if ($method === 'GET') getUsers($db);
        break;
    
    case 'detail':
        if ($method === 'GET') getUserDetail($db);
        break;
    
    case 'update':
        if ($method === 'PUT') updateUser($db);
        break;
    
    case 'delete':
        if ($method === 'DELETE') deleteUser($db);
        break;
    
    case 'stats':
        if ($method === 'GET') getUserStats($db);
        break;
    
    default:
        jsonResponse(false, 'Azione non valida', null, 404);
}

// =====================================================
// GET USERS LIST
// =====================================================
function getUsers($db) {
    checkRole(2); // Minimo bagnino
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
    
    $offset = ($page - 1) * $limit;
    
    try {
        // Build query
        $where = ["u.attivo = TRUE"];
        $params = [];
        
        if ($search) {
            $where[] = "(u.nome LIKE :search OR u.cognome LIKE :search OR u.email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        if ($role) {
            $where[] = "r.nome = :role";
            $params[':role'] = $role;
        }
        
        $where_sql = implode(' AND ', $where);
        
        // Count total
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM utenti u
            JOIN ruoli r ON u.ruolo_id = r.id
            WHERE {$where_sql}
        ");
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // Get users
        $stmt = $db->prepare("
            SELECT u.id, u.email, u.nome, u.cognome, u.telefono, u.data_nascita,
                   u.citta, u.codice_fiscale, u.attivo, u.email_verified, u.created_at,
                   r.nome as ruolo, r.livello as role_level
            FROM utenti u
            JOIN ruoli r ON u.ruolo_id = r.id
            WHERE {$where_sql}
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        jsonResponse(true, 'Utenti recuperati', [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
        
    } catch(PDOException $e) {
        error_log("Get users error: " . $e->getMessage());
        jsonResponse(false, 'Errore recupero utenti', null, 500);
    }
}

// =====================================================
// GET USER DETAIL
// =====================================================
function getUserDetail($db) {
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$user_id) {
        jsonResponse(false, 'ID utente mancante', null, 400);
    }
    
    // Check permission
    if ($_SESSION['user_id'] != $user_id) {
        checkRole(2); // Solo staff può vedere altri utenti
    }
    
    try {
        // Get user
        $stmt = $db->prepare("
            SELECT u.*, r.nome as ruolo, r.livello as role_level
            FROM utenti u
            JOIN ruoli r ON u.ruolo_id = r.id
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(false, 'Utente non trovato', null, 404);
        }
        
        unset($user['password_hash']);
        
        // Get acquisti attivi
        $stmt = $db->prepare("
            SELECT a.*, p.nome as pacchetto_nome
            FROM acquisti a
            JOIN pacchetti p ON a.pacchetto_id = p.id
            WHERE a.user_id = :user_id
              AND a.stato_pagamento = 'confermato'
              AND a.ingressi_rimanenti > 0
              AND a.data_scadenza >= CURDATE()
            ORDER BY a.data_scadenza DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        $acquisti = $stmt->fetchAll();
        
        // Get documenti status
        $documenti_status = documentiCompleti($db, $user_id);
        
        // Get total check-ins
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM check_ins WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $total_checkins = $stmt->fetch()['total'];
        
        jsonResponse(true, 'Dettagli utente', [
            'user' => $user,
            'acquisti_attivi' => $acquisti,
            'documenti_status' => $documenti_status,
            'total_checkins' => $total_checkins
        ]);
        
    } catch(PDOException $e) {
        error_log("Get user detail error: " . $e->getMessage());
        jsonResponse(false, 'Errore recupero dettagli', null, 500);
    }
}

// =====================================================
// UPDATE USER
// =====================================================
function updateUser($db) {
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$user_id) {
        jsonResponse(false, 'ID utente mancante', null, 400);
    }
    
    // Check permission
    if ($_SESSION['user_id'] != $user_id) {
        checkRole(3); // Solo ufficio/admin può modificare altri
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $fields = [];
        $params = [':id' => $user_id];
        
        // Campi modificabili
        $allowed = ['nome', 'cognome', 'telefono', 'data_nascita', 'indirizzo', 'citta', 'cap', 'codice_fiscale', 'note'];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = sanitizeInput($data[$field]);
            }
        }
        
        // Admin può cambiare ruolo
        if (isset($data['ruolo_id']) && $_SESSION['role_level'] >= 4) {
            $fields[] = "ruolo_id = :ruolo_id";
            $params[':ruolo_id'] = (int)$data['ruolo_id'];
        }
        
        if (empty($fields)) {
            jsonResponse(false, 'Nessun campo da aggiornare', null, 400);
        }
        
        $sql = "UPDATE utenti SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        logActivity($db, $_SESSION['user_id'], 'update_user', 'utenti', $user_id, $data);
        
        jsonResponse(true, 'Utente aggiornato');
        
    } catch(PDOException $e) {
        error_log("Update user error: " . $e->getMessage());
        jsonResponse(false, 'Errore aggiornamento utente', null, 500);
    }
}

// =====================================================
// DELETE USER (soft delete)
// =====================================================
function deleteUser($db) {
    checkRole(4); // Solo admin
    
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$user_id) {
        jsonResponse(false, 'ID utente mancante', null, 400);
    }
    
    if ($user_id == $_SESSION['user_id']) {
        jsonResponse(false, 'Non puoi disattivare te stesso', null, 400);
    }
    
    try {
        $stmt = $db->prepare("UPDATE utenti SET attivo = FALSE WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        
        logActivity($db, $_SESSION['user_id'], 'delete_user', 'utenti', $user_id);
        
        jsonResponse(true, 'Utente disattivato');
        
    } catch(PDOException $e) {
        error_log("Delete user error: " . $e->getMessage());
        jsonResponse(false, 'Errore disattivazione utente', null, 500);
    }
}

// =====================================================
// USER STATS
// =====================================================
function getUserStats($db) {
    checkRole(3); // Ufficio/Admin
    
    try {
        // Total users
        $stmt = $db->query("SELECT COUNT(*) as total FROM utenti WHERE attivo = TRUE");
        $total_users = $stmt->fetch()['total'];
        
        // Users by role
        $stmt = $db->query("
            SELECT r.nome, COUNT(u.id) as count
            FROM ruoli r
            LEFT JOIN utenti u ON r.id = u.ruolo_id AND u.attivo = TRUE
            GROUP BY r.id, r.nome
        ");
        $by_role = $stmt->fetchAll();
        
        // New users this month
        $stmt = $db->query("
            SELECT COUNT(*) as count
            FROM utenti
            WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $new_this_month = $stmt->fetch()['count'];
        
        // Active packages
        $stmt = $db->query("
            SELECT COUNT(DISTINCT user_id) as count
            FROM acquisti
            WHERE stato_pagamento = 'confermato'
              AND ingressi_rimanenti > 0
              AND data_scadenza >= CURDATE()
        ");
        $active_packages = $stmt->fetch()['count'];
        
        jsonResponse(true, 'Statistiche utenti', [
            'total_users' => $total_users,
            'by_role' => $by_role,
            'new_this_month' => $new_this_month,
            'active_packages' => $active_packages
        ]);
        
    } catch(PDOException $e) {
        error_log("User stats error: " . $e->getMessage());
        jsonResponse(false, 'Errore recupero statistiche', null, 500);
    }
}

?>
