<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del formulario
$conversation_id = (int)($_POST['conversation_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

// Validar datos
if ($conversation_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de conversación inválido']);
    exit();
}

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar que la conversación existe y pertenece al usuario
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM support_conversations 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $_SESSION['user_id']]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversación no encontrada']);
        exit();
    }
    
    if ($conversation['status'] === 'closed') {
        echo json_encode(['success' => false, 'message' => 'Esta conversación está cerrada']);
        exit();
    }
    
    // Insertar el mensaje
    $stmt = $conn->prepare("
        INSERT INTO support_messages (conversation_id, sender_id, sender_type, message) 
        VALUES (?, ?, 'user', ?)
    ");
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    
    // Actualizar la fecha de actualización de la conversación
    $stmt = $conn->prepare("
        UPDATE support_conversations 
        SET updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$conversation_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Mensaje enviado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje: ' . $e->getMessage()]);
}
?> 