<?php
require_once 'config/config.php';

echo "<h2>Test de Admin Access</h2>";

if (!isLoggedIn()) {
    echo "<p>❌ Usuario no logueado</p>";
    exit();
}

echo "<p>✅ Usuario logueado</p>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";

$currentUser = getCurrentUser();
if (!$currentUser) {
    echo "<p>❌ No se pudo obtener información del usuario</p>";
    exit();
}

echo "<p>✅ Información del usuario obtenida</p>";
echo "<p>Username: " . $currentUser['username'] . "</p>";
echo "<p>Role: " . ($currentUser['role'] ?? 'NO DEFINIDO') . "</p>";

if ($currentUser['role'] === 'admin') {
    echo "<p>✅ Usuario es admin</p>";
} else {
    echo "<p>❌ Usuario NO es admin</p>";
}

echo "<p>ID del usuario: " . $currentUser['id'] . "</p>";
echo "<p>¿Es ID 1?: " . ($currentUser['id'] == 1 ? 'SÍ' : 'NO') . "</p>";
?> 