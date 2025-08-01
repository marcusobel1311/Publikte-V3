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
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validar datos
if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar si las tablas existen
    $stmt = $conn->prepare("SHOW TABLES LIKE 'support_conversations'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Error: Las tablas de soporte no existen. Ejecute chat_tables.sql']);
        exit();
    }
    
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Crear la conversación
    $stmt = $conn->prepare("
        INSERT INTO support_conversations (user_id, subject, status, created_at, updated_at) 
        VALUES (?, ?, 'open', NOW(), NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $subject]);
    $conversation_id = $conn->lastInsertId();
    
    // Crear el primer mensaje
    $stmt = $conn->prepare("
        INSERT INTO support_messages (conversation_id, sender_id, sender_type, message, is_read, created_at) 
        VALUES (?, ?, 'user', ?, 0, NOW())
    ");
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Conversación creada exitosamente',
        'conversation_id' => $conversation_id
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => 'Error al crear la conversación: ' . $e->getMessage()]);
}
?> 