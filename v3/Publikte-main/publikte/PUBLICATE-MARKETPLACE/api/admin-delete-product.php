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
$productId = $input['id'] ?? null;

// Validar ID
if (!$productId || !is_numeric($productId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
    exit;
}

$productId = (int)$productId;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Verificar que el producto existe
    $query = "SELECT id, user_id FROM products WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    
    // Obtener imágenes del producto para eliminarlas físicamente
    $query = "SELECT image_url FROM product_images WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Eliminar imágenes físicamente
    foreach ($images as $image) {
        $imagePath = '../' . $image['image_url'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Eliminar imágenes de la base de datos
    $query = "DELETE FROM product_images WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    // Eliminar del carrito
    $query = "DELETE FROM cart WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    // Eliminar de favoritos
    $query = "DELETE FROM favorites WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    // Eliminar reseñas relacionadas
    $query = "DELETE FROM reviews WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    // Finalmente, eliminar el producto
    $query = "DELETE FROM products WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    
    if ($stmt->execute()) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);
    } else {
        $db->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar producto'
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