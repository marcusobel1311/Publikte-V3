<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado y es administrador
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener todas las conversaciones de soporte con información del usuario
    $stmt = $conn->prepare("
        SELECT 
            sc.*,
            u.username,
            u.email,
            (SELECT COUNT(*) FROM support_messages sm WHERE sm.conversation_id = sc.id AND sm.is_read = 0 AND sm.sender_type = 'user') as unread_count,
            (SELECT message FROM support_messages sm WHERE sm.conversation_id = sc.id ORDER BY sm.created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM support_messages sm WHERE sm.conversation_id = sc.id ORDER BY sm.created_at DESC LIMIT 1) as last_message_time
        FROM support_conversations sc 
        JOIN users u ON sc.user_id = u.id
        ORDER BY sc.updated_at DESC
    ");
    $stmt->execute();
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener conversaciones: ' . $e->getMessage()]);
}
?> 