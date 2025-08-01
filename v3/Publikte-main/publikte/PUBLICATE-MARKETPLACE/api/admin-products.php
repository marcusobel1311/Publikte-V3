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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener todos los productos con información del vendedor y categoría
    $query = "SELECT 
                p.id,
                p.user_id,
                p.category_id,
                p.title,
                p.description,
                p.price,
                p.original_price,
                p.condition_type,
                p.location,
                p.shipping_type,
                p.status,
                p.views,
                p.likes,
                p.created_at,
                p.updated_at,
                u.username as seller_name,
                u.full_name as seller_full_name,
                c.name as category_name,
                pi.image_url
              FROM products p
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN categories c ON p.category_id = c.id
              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 