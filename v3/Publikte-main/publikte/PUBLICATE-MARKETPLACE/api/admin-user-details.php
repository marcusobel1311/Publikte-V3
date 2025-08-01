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

// Verificar que se proporcione un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
    exit;
}

$userId = (int)$_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener detalles del usuario
    $query = "SELECT 
                id,
                username,
                email,
                full_name,
                location,
                phone,
                avatar,
                wallet_balance,
                rating,
                total_reviews,
                total_sales,
                member_since,
                is_active,
                created_at,
                updated_at
              FROM users 
              WHERE id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 