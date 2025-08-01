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
    
    // Estadísticas de usuarios
    $query = "SELECT COUNT(*) as total_users FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
    
    // Estadísticas de productos
    $query = "SELECT COUNT(*) as total_products FROM products WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    // Estadísticas de ventas
    $query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                SUM(commission) as total_commissions
              FROM orders 
              WHERE status IN ('delivered', 'shipped')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $salesStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estadísticas de soporte
    $query = "SELECT COUNT(*) as open_conversations FROM support_conversations WHERE status = 'open'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $openConversations = $stmt->fetch(PDO::FETCH_ASSOC)['open_conversations'];
    
    $query = "SELECT COUNT(*) as closed_conversations FROM support_conversations WHERE status = 'closed'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $closedConversations = $stmt->fetch(PDO::FETCH_ASSOC)['closed_conversations'];
    
    $query = "SELECT COUNT(*) as unread_messages FROM support_messages WHERE is_read = 0 AND sender_type = 'user'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['unread_messages'];
    
    $stats = [
        'total_users' => (int)$totalUsers,
        'total_products' => (int)$totalProducts,
        'total_sales' => (float)($salesStats['total_sales'] ?? 0),
        'total_commissions' => (float)($salesStats['total_commissions'] ?? 0),
        'total_orders' => (int)($salesStats['total_orders'] ?? 0),
        'open_conversations' => (int)$openConversations,
        'closed_conversations' => (int)$closedConversations,
        'unread_messages' => (int)$unreadMessages
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 