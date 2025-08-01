<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Obtener parámetros
$conversation_id = (int)($_GET['id'] ?? 0);

// Validar datos
if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de conversación inválido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener todos los mensajes de la conversación
    $stmt = $conn->prepare("
        SELECT sm.id, sm.message, sm.sender_type, sm.created_at, u.name as sender_name
        FROM support_messages sm
        LEFT JOIN users u ON sm.sender_id = u.id
        WHERE sm.conversation_id = ?
        ORDER BY sm.created_at ASC
    ");
    $stmt->execute([$conversation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensajes de usuario como leídos
    $stmt = $conn->prepare("
        UPDATE support_messages 
        SET is_read = 1 
        WHERE conversation_id = ? AND sender_type = 'user' AND is_read = 0
    ");
    $stmt->execute([$conversation_id]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener mensajes: ' . $e->getMessage()]);
}
?> 