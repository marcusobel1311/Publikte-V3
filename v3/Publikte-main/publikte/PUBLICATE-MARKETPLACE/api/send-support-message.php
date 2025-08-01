<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del formulario
$conversation_id = intval($_POST['conversation_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

// Validar datos
if ($conversation_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar que la conversación existe y pertenece al usuario
    $stmt = $conn->prepare("SELECT id FROM support_conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$conversation_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Conversación no encontrada']);
        exit();
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Insertar el mensaje del usuario
    $stmt = $conn->prepare("
        INSERT INTO support_messages (conversation_id, sender_id, sender_type, message, is_read, created_at) 
        VALUES (?, ?, 'user', ?, 0, NOW())
    ");
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    
    // Actualizar la fecha de actualización de la conversación
    $stmt = $conn->prepare("
        UPDATE support_conversations 
        SET updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$conversation_id]);
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Mensaje enviado exitosamente'
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje: ' . $e->getMessage()]);
}
?> 