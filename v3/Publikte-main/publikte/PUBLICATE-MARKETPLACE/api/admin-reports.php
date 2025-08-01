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
    
    $reports = [];
    
    // Usuarios por mes (últimos 6 meses)
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
              FROM users 
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usersByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $usersLabels = [];
    $usersData = [];
    foreach ($usersByMonth as $row) {
        $usersLabels[] = date('M Y', strtotime($row['month'] . '-01'));
        $usersData[] = (int)$row['count'];
    }
    
    $reports['users_by_month'] = [
        'labels' => $usersLabels,
        'data' => $usersData
    ];
    
    // Ventas por categoría
    $query = "SELECT 
                c.name as category_name,
                COUNT(o.id) as order_count,
                SUM(o.total_amount) as total_sales
              FROM orders o
              JOIN products p ON o.product_id = p.id
              JOIN categories c ON p.category_id = c.id
              WHERE o.status IN ('delivered', 'shipped')
              GROUP BY c.id, c.name
              ORDER BY total_sales DESC
              LIMIT 8";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $salesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $salesLabels = [];
    $salesData = [];
    foreach ($salesByCategory as $row) {
        $salesLabels[] = $row['category_name'];
        $salesData[] = (float)$row['total_sales'];
    }
    
    $reports['sales_by_category'] = [
        'labels' => $salesLabels,
        'data' => $salesData
    ];
    
    // Productos por estado
    $query = "SELECT 
                status,
                COUNT(*) as count
              FROM products 
              GROUP BY status";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $productsByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statusLabels = [];
    $statusData = [];
    $statusMap = [
        'active' => 'Activo',
        'sold' => 'Vendido',
        'archived' => 'Archivado',
        'deleted' => 'Eliminado'
    ];
    
    foreach ($productsByStatus as $row) {
        $statusLabels[] = $statusMap[$row['status']] ?? $row['status'];
        $statusData[] = (int)$row['count'];
    }
    
    $reports['products_by_status'] = [
        'labels' => $statusLabels,
        'data' => $statusData
    ];
    
    // Ingresos mensuales (comisiones)
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(commission) as total_commission
              FROM orders 
              WHERE status IN ('delivered', 'shipped')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $incomeByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $incomeLabels = [];
    $incomeData = [];
    foreach ($incomeByMonth as $row) {
        $incomeLabels[] = date('M Y', strtotime($row['month'] . '-01'));
        $incomeData[] = (float)$row['total_commission'];
    }
    
    $reports['income_by_month'] = [
        'labels' => $incomeLabels,
        'data' => $incomeData
    ];
    
    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 