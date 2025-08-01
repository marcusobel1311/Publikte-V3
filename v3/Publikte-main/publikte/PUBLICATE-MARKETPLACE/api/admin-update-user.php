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

// Obtener datos del formulario
$userId = $_POST['id'] ?? null;
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$fullName = $_POST['full_name'] ?? '';
$location = $_POST['location'] ?? '';
$walletBalance = $_POST['wallet_balance'] ?? 0;
$isActive = $_POST['is_active'] ?? 1;

// Validar datos requeridos
if (!$userId || !$username || !$email || !$fullName) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

// Validar que no sea el administrador principal
if ($userId == 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No se puede modificar el administrador principal']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el usuario existe
    $query = "SELECT id FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Verificar que el username no esté en uso por otro usuario
    $query = "SELECT id FROM users WHERE username = :username AND id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
        exit;
    }
    
    // Verificar que el email no esté en uso por otro usuario
    $query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El email ya está en uso']);
        exit;
    }
    
    // Actualizar usuario
    $query = "UPDATE users SET 
                username = :username,
                email = :email,
                full_name = :full_name,
                location = :location,
                wallet_balance = :wallet_balance,
                is_active = :is_active,
                updated_at = NOW()
              WHERE id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':wallet_balance', $walletBalance);
    $stmt->bindParam(':is_active', $isActive);
    $stmt->bindParam(':user_id', $userId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Usuario actualizado correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar usuario'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?> 