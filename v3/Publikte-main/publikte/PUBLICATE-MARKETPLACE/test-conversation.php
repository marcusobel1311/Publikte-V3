<?php
require_once 'config/config.php';

echo "<h2>Test de Creación de Conversación</h2>";

if (!isLoggedIn()) {
    echo "<p>❌ Usuario no logueado</p>";
    exit();
}

echo "<p>✅ Usuario logueado - ID: " . $_SESSION['user_id'] . "</p>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p>✅ Conexión a base de datos exitosa</p>";
    
    // Verificar si las tablas existen
    $stmt = $conn->prepare("SHOW TABLES LIKE 'support_conversations'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ Tabla support_conversations NO existe</p>";
        echo "<p>Ejecute el archivo chat_tables.sql en su base de datos</p>";
    } else {
        echo "<p>✅ Tabla support_conversations existe</p>";
    }
    
    $stmt = $conn->prepare("SHOW TABLES LIKE 'support_messages'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ Tabla support_messages NO existe</p>";
    } else {
        echo "<p>✅ Tabla support_messages existe</p>";
    }
    
    // Verificar estructura de la tabla
    $stmt = $conn->prepare("DESCRIBE support_conversations");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Columnas de support_conversations:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 