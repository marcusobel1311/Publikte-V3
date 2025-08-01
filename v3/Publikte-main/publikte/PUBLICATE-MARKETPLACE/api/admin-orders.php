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
    
    // Obtener todas las órdenes con información del comprador, vendedor y producto
    $query = "SELECT 
                o.id,
                o.buyer_id,
                o.seller_id,
                o.product_id,
                o.quantity,
                o.total_amount,
                o.status,
                o.tracking_number,
                o.shipping_address,
                o.payment_method,
                o.commission,
                o.created_at,
                o.updated_at,
                buyer.username as buyer_username,
                buyer.full_name as buyer_name,
                seller.username as seller_username,
                seller.full_name as seller_name,
                p.title as product_title,
                p.price as product_price
              FROM orders o
              LEFT JOIN users buyer ON o.buyer_id = buyer.id
              LEFT JOIN users seller ON o.seller_id = seller.id
              LEFT JOIN products p ON o.product_id = p.id
              ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 