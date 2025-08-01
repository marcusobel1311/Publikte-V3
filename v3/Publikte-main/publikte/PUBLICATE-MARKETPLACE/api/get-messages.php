<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Obtener parámetros
$conversation_id = (int)($_GET['conversation_id'] ?? 0);
$last_message_id = (int)($_GET['last_message_id'] ?? 0);

// Validar datos
if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de conversación inválido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar que la conversación pertenece al usuario
    $stmt = $conn->prepare("
        SELECT id 
        FROM support_conversations 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Conversación no encontrada']);
        exit();
    }
    
    // Obtener nuevos mensajes
    $query = "
        SELECT sm.id, sm.message, sm.sender_type, sm.created_at, u.name as sender_name
        FROM support_messages sm
        LEFT JOIN users u ON sm.sender_id = u.id
        WHERE sm.conversation_id = ?
    ";
    $params = [$conversation_id];
    
    if ($last_message_id > 0) {
        $query .= " AND sm.id > ?";
        $params[] = $last_message_id;
    }
    
    $query .= " ORDER BY sm.created_at ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensajes de admin como leídos
    if (!empty($messages)) {
        $message_ids = array_column($messages, 'id');
        $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
        
        $stmt = $conn->prepare("
            UPDATE support_messages 
            SET is_read = 1 
            WHERE id IN ($placeholders) AND sender_type = 'admin'
        ");
        $stmt->execute($message_ids);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener mensajes: ' . $e->getMessage()]);
}
?> 