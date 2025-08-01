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
    
    // Obtener información de la conversación
    $stmt = $conn->prepare("
        SELECT sc.*, u.name as user_name, u.email as user_email
        FROM support_conversations sc
        LEFT JOIN users u ON sc.user_id = u.id
        WHERE sc.id = ?
    ");
    $stmt->execute([$conversation_id]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'message' => 'Conversación no encontrada']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'conversation' => $conversation
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener información: ' . $e->getMessage()]);
}
?> 