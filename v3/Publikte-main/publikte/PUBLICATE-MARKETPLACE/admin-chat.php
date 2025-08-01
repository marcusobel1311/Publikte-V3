<?php
session_start();
require_once 'config/config.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Obtener conversaciones de soporte
$stmt = $conn->prepare("
    SELECT sc.*, 
           u.name as user_name,
           u.email as user_email,
           (SELECT COUNT(*) FROM support_messages sm WHERE sm.conversation_id = sc.id AND sm.is_read = 0 AND sm.sender_type = 'user') as unread_count,
           (SELECT message FROM support_messages sm WHERE sm.conversation_id = sc.id ORDER BY sm.created_at DESC LIMIT 1) as last_message,
           (SELECT created_at FROM support_messages sm WHERE sm.conversation_id = sc.id ORDER BY sm.created_at DESC LIMIT 1) as last_message_time
    FROM support_conversations sc 
    LEFT JOIN users u ON sc.user_id = u.id
    ORDER BY 
        CASE WHEN sc.status = 'open' THEN 0 ELSE 1 END,
        sc.updated_at DESC
");
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Chat - PUBLICATE</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-chat-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .admin-chat-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .admin-chat-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .conversations-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            height: calc(100vh - 200px);
        }
        .conversations-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .conversations-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: var(--primary-color);
            color: white;
        }
        .conversations-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        .conversations-content {
            flex: 1;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }
        .conversation-item:hover {
            background: #f8f9fa;
        }
        .conversation-item.active {
            background: var(--primary-color);
            color: white;
        }
        .conversation-item.active .conversation-subject,
        .conversation-item.active .conversation-meta,
        .conversation-item.active .conversation-user {
            color: white;
        }
        .conversation-item:last-child {
            border-bottom: none;
        }
        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .conversation-subject {
            font-weight: bold;
            color: var(--gray-800);
        }
        .conversation-status {
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
        .conversation-item.active .status-open {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .conversation-item.active .status-closed {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .conversation-user {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        .last-message {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .unread-badge {
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .chat-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            background: var(--gray-50);
        }
        .chat-header h2 {
            margin: 0 0 0.5rem 0;
            color: var(--gray-800);
        }
        .chat-user-info {
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            max-height: 400px;
        }
        .message {
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        .message.user {
            align-items: flex-start;
        }
        .message.admin {
            align-items: flex-end;
        }
        .message-content {
            max-width: 70%;
            padding: 1rem;
            border-radius: 12px;
            position: relative;
        }
        .message.user .message-content {
            background: #f1f3f4;
            color: var(--gray-800);
            border-bottom-left-radius: 4px;
        }
        .message.admin .message-content {
            background: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message-sender {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--gray-600);
        }
        .message-time {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
        }
        .message.admin .message-time {
            color: rgba(255,255,255,0.8);
        }
        .chat-input {
            padding: 1.5rem;
            border-top: 1px solid #eee;
        }
        .input-group {
            display: flex;
            gap: 1rem;
        }
        .message-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            resize: none;
            height: 50px;
        }
        .send-btn {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .send-btn:hover {
            background: var(--primary-dark);
        }
        .send-btn:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }
        .no-conversation {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray-600);
            text-align: center;
        }
        .close-conversation-btn {
            background: var(--gray-600);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            margin-left: 1rem;
        }
        .close-conversation-btn:hover {
            background: var(--gray-700);
        }
        .no-messages {
            text-align: center;
            color: var(--gray-600);
            padding: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="admin-chat-container">
        <div class="admin-chat-header">
            <h1>Panel de Chat de Soporte</h1>
            <p>Gestiona las conversaciones de soporte con los usuarios</p>
        </div>

        <div class="conversations-grid">
            <div class="conversations-list">
                <div class="conversations-header">
                    <h2>Conversaciones</h2>
                </div>
                <div class="conversations-content">
                    <?php if (empty($conversations)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--gray-600);">
                            <p>No hay conversaciones de soporte</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <div class="conversation-item" onclick="loadConversation(<?php echo $conversation['id']; ?>)">
                                <div class="conversation-header">
                                    <span class="conversation-subject"><?php echo htmlspecialchars($conversation['subject']); ?></span>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                        <?php endif; ?>
                                        <span class="conversation-status status-<?php echo $conversation['status']; ?>">
                                            <?php echo $conversation['status'] === 'open' ? 'Abierta' : 'Cerrada'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="conversation-user">
                                    <?php echo htmlspecialchars($conversation['user_name']); ?> (<?php echo htmlspecialchars($conversation['user_email']); ?>)
                                </div>
                                <div class="conversation-meta">
                                    <span class="last-message"><?php echo htmlspecialchars($conversation['last_message']); ?></span>
                                    <span><?php echo date('d/m/Y H:i', strtotime($conversation['last_message_time'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chat-panel">
                <div id="noConversation" class="no-conversation">
                    <div>
                        <h3>Selecciona una conversación</h3>
                        <p>Elige una conversación de la lista para comenzar a chatear</p>
                    </div>
                </div>
                
                <div id="chatInterface" style="display: none;">
                    <div class="chat-header">
                        <h2 id="chatSubject"></h2>
                        <div class="chat-user-info" id="chatUserInfo"></div>
                        <button id="closeConversationBtn" class="close-conversation-btn" onclick="closeConversation()">Cerrar conversación</button>
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <div class="no-messages">
                            <p>Cargando mensajes...</p>
                        </div>
                    </div>
                    
                    <div class="chat-input">
                        <form id="messageForm">
                            <div class="input-group">
                                <textarea 
                                    id="messageInput" 
                                    class="message-input" 
                                    placeholder="Escribe tu respuesta..." 
                                    required
                                    onkeypress="handleKeyPress(event)"
                                ></textarea>
                                <button type="submit" class="send-btn" id="sendBtn">Enviar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        let currentConversationId = null;
        let lastMessageId = 0;

        function loadConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Actualizar UI
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.conversation-item').classList.add('active');
            
            document.getElementById('noConversation').style.display = 'none';
            document.getElementById('chatInterface').style.display = 'flex';
            
            // Cargar información de la conversación
            fetch(`api/get-conversation-info.php?id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('chatSubject').textContent = data.conversation.subject;
                    document.getElementById('chatUserInfo').textContent = `${data.conversation.user_name} (${data.conversation.user_email})`;
                    loadMessages(conversationId);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function loadMessages(conversationId) {
            fetch(`api/get-conversation-messages.php?id=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.messages);
                    if (data.messages.length > 0) {
                        lastMessageId = Math.max(...data.messages.map(m => m.id));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function displayMessages(messages) {
            const chatMessages = document.getElementById('chatMessages');
            
            if (messages.length === 0) {
                chatMessages.innerHTML = '<div class="no-messages"><p>No hay mensajes en esta conversación.</p></div>';
                return;
            }
            
            chatMessages.innerHTML = messages.map(message => `
                <div class="message ${message.sender_type}">
                    <div class="message-sender">${message.sender_type === 'admin' ? 'Soporte' : message.sender_name}</div>
                    <div class="message-content">
                        ${message.message.replace(/\n/g, '<br>')}
                        <div class="message-time">${formatTime(message.created_at)}</div>
                    </div>
                </div>
            `).join('');
            
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function sendMessage() {
            if (!currentConversationId) return;
            
            const message = document.getElementById('messageInput').value.trim();
            if (!message) return;

            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.textContent = 'Enviando...';

            const formData = new FormData();
            formData.append('conversation_id', currentConversationId);
            formData.append('message', message);

            fetch('api/admin-send-message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('messageInput').value = '';
                    // Recargar mensajes
                    loadMessages(currentConversationId);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar el mensaje');
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Enviar';
            });
        }

        function closeConversation() {
            if (!currentConversationId) return;
            
            if (confirm('¿Estás seguro de que quieres cerrar esta conversación?')) {
                fetch('api/close-conversation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `conversation_id=${currentConversationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Recargar para actualizar la lista
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cerrar la conversación');
                });
            }
        }

        // Manejar envío de mensajes
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Polling para nuevos mensajes (cada 5 segundos)
        setInterval(function() {
            if (currentConversationId) {
                fetch(`api/get-new-messages.php?conversation_id=${currentConversationId}&last_message_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        // Agregar solo los nuevos mensajes
                        const chatMessages = document.getElementById('chatMessages');
                        data.messages.forEach(message => {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message ${message.sender_type}`;
                            messageDiv.innerHTML = `
                                <div class="message-sender">${message.sender_type === 'admin' ? 'Soporte' : message.sender_name}</div>
                                <div class="message-content">
                                    ${message.message.replace(/\n/g, '<br>')}
                                    <div class="message-time">${formatTime(message.created_at)}</div>
                                </div>
                            `;
                            chatMessages.appendChild(messageDiv);
                        });
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        lastMessageId = Math.max(...data.messages.map(m => m.id));
                    }
                })
                .catch(error => {
                    console.error('Error polling messages:', error);
                });
            }
        }, 5000);
    </script>
</body>
</html> 