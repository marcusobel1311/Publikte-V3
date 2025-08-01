<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

$conversation_id = intval($_GET['conversation_id'] ?? 0);

if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de conversación inválido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $current_user = getCurrentUser();
    
    // Verificar que el usuario tiene acceso a esta conversación
    $stmt = $conn->prepare("
        SELECT id FROM support_conversations 
        WHERE id = ? AND (user_id = ? OR ? = 'admin')
    ");
    $stmt->execute([$conversation_id, $current_user['id'], $current_user['role']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado a esta conversación']);
        exit();
    }
    
    // Obtener los mensajes de la conversación
    $stmt = $conn->prepare("
        SELECT 
            sm.*,
            u.username as sender_name
        FROM support_messages sm
        LEFT JOIN users u ON sm.sender_id = u.id
        WHERE sm.conversation_id = ?
        ORDER BY sm.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensajes como leídos si el usuario actual los está viendo
    if ($current_user['role'] !== 'admin') {
        // Si es usuario normal, marcar mensajes del admin como leídos
        $stmt = $conn->prepare("
            UPDATE support_messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_type = 'admin' AND is_read = 0
        ");
        $stmt->execute([$conversation_id]);
    } else {
        // Si es admin, marcar mensajes del usuario como leídos
        $stmt = $conn->prepare("
            UPDATE support_messages 
            SET is_read = 1 
            WHERE conversation_id = ? AND sender_type = 'user' AND is_read = 0
        ");
        $stmt->execute([$conversation_id]);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener mensajes: ' . $e->getMessage()]);
}
?> 