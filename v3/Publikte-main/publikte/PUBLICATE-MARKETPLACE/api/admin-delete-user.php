<?php
require_once '../config/config.php';

// Verificar si el usuario está logueado y es administrador
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$currentUser = getCurrentUser();
if ($currentUser['id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos de administrador']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['id'] ?? null;

// Validar ID
if (!$userId || !is_numeric($userId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

$userId = (int)$userId;

// Validar que no sea el administrador principal
if ($userId == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No se puede eliminar el administrador principal']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Verificar que el usuario existe
    $query = "SELECT id FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Eliminar productos del usuario
    $query = "DELETE FROM products WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Eliminar órdenes relacionadas
    $query = "DELETE FROM orders WHERE buyer_id = :user_id OR seller_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Eliminar carrito del usuario
    $query = "DELETE FROM cart WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Eliminar favoritos del usuario
    $query = "DELETE FROM favorites WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Eliminar transacciones de wallet
    $query = "DELETE FROM wallet_transactions WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Eliminar notificaciones
    $query = "DELETE FROM notifications WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Eliminar reseñas
    $query = "DELETE FROM reviews WHERE reviewer_id = :user_id OR reviewed_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Finalmente, eliminar el usuario
    $query = "DELETE FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    
    if ($stmt->execute()) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    } else {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar usuario'
        ]);
    }
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 