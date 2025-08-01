<?php
require_once 'config/config.php';

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$conversation_id = intval($_GET['id'] ?? 0);

if ($conversation_id <= 0) {
    header('Location: support.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Verificar que la conversación existe y pertenece al usuario
$stmt = $conn->prepare("
    SELECT sc.*, u.username 
    FROM support_conversations sc 
    JOIN users u ON sc.user_id = u.id 
    WHERE sc.id = ? AND sc.user_id = ?
");
$stmt->execute([$conversation_id, $_SESSION['user_id']]);
$conversation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conversation) {
    header('Location: support.php');
    exit();
}

// Obtener mensajes de la conversación
$stmt = $conn->prepare("
    SELECT sm.*, u.username as sender_name
    FROM support_messages sm
    LEFT JOIN users u ON sm.sender_id = u.id
    WHERE sm.conversation_id = ?
    ORDER BY sm.created_at ASC
");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marcar mensajes del admin como leídos
$stmt = $conn->prepare("
    UPDATE support_messages 
    SET is_read = 1 
    WHERE conversation_id = ? AND sender_type = 'admin' AND is_read = 0
");
$stmt->execute([$conversation_id]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat de Soporte - PUBLICATE</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background: white;
            padding: 1rem;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-subject {
            font-weight: bold;
            color: #1f2937;
        }
        .chat-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-open {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status-closed {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .messages-container {
            flex: 1;
            background: white;
            overflow-y: auto;
            padding: 1rem;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
        }
        .message {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
        }
        .message.user {
            align-items: flex-end;
        }
        .message.admin {
            align-items: flex-start;
        }
        .message-content {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            word-wrap: break-word;
        }
        .message.user .message-content {
            background: #f97316;
            color: white;
        }
        .message.admin .message-content {
            background: #f1f3f4;
            color: #1f2937;
        }
        .message-info {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        .message-input-container {
            background: white;
            padding: 1rem;
            border-radius: 0 0 12px 12px;
            border-top: 1px solid #eee;
        }
        .message-input-form {
            display: flex;
            gap: 1rem;
        }
        .message-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            resize: none;
            font-family: inherit;
        }
        .send-btn {
            background: #f97316;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
        }
        .send-btn:hover {
            background: #ea580c;
        }
        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .back-btn {
            background: #6b7280;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
        }
        .back-btn:hover {
            background: #4b5563;
        }
        .no-messages {
            text-align: center;
            color: #6b7280;
            padding: 2rem;
        }
        
        /* Asegurar que todos los textos sean visibles */
        .messages-container {
            color: #1f2937;
        }
        
        .message-content {
            color: inherit;
        }
        
        /* Mejorar la apariencia del textarea */
        .message-input {
            color: #1f2937;
            background: white;
        }
        
        .message-input::placeholder {
            color: #9ca3af;
        }
        
        /* Mejorar contraste para mensajes del admin */
        .message.admin .message-content {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1f2937;
        }
        
        /* Mejorar contraste para mensajes del usuario */
        .message.user .message-content {
            background: #f97316;
            color: white;
            border: 1px solid #ea580c;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="chat-container">
        <div class="chat-header">
            <div>
                <div class="chat-subject"><?php echo htmlspecialchars($conversation['subject']); ?></div>
                <div style="font-size: 0.875rem; color: #6b7280;">
                    Conversación con soporte
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="chat-status status-<?php echo $conversation['status']; ?>">
                    <?php echo $conversation['status'] === 'open' ? 'Abierta' : 'Cerrada'; ?>
                </span>
                <a href="support.php" class="back-btn">← Volver</a>
            </div>
        </div>

        <div class="messages-container" id="messagesContainer">
            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <p>No hay mensajes en esta conversación.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo $message['sender_type']; ?>">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        <div class="message-info">
                            <?php echo $message['sender_name']; ?> • 
                            <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($conversation['status'] === 'open'): ?>
            <div class="message-input-container">
                <form class="message-input-form" id="messageForm">
                    <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                    <textarea 
                        name="message" 
                        class="message-input" 
                        placeholder="Escribe tu mensaje..." 
                        rows="3" 
                        required
                        id="messageInput"
                    ></textarea>
                    <button type="submit" class="send-btn" id="sendBtn">Enviar</button>
                </form>
            </div>
        <?php else: ?>
            <div class="message-input-container">
                            <div style="text-align: center; color: #6b7280; padding: 1rem;">
                Esta conversación está cerrada. No puedes enviar más mensajes.
            </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Auto-scroll al final de los mensajes
        const messagesContainer = document.getElementById('messagesContainer');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Manejar envío de mensajes
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');

        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const message = messageInput.value.trim();
                if (!message) return;

                // Deshabilitar botón mientras se envía
                sendBtn.disabled = true;
                sendBtn.textContent = 'Enviando...';

                const formData = new FormData();
                formData.append('conversation_id', <?php echo $conversation_id; ?>);
                formData.append('message', message);

                fetch('api/send-support-message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Limpiar input
                        messageInput.value = '';
                        
                        // Recargar la página para mostrar el nuevo mensaje
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al enviar mensaje');
                })
                .finally(() => {
                    // Rehabilitar botón
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Enviar';
                });
            });
        }

        // Auto-resize del textarea
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
    </script>
</body>
</html> 