<?php
require_once 'config/config.php';

// Verificar si el usuario está logueado y es administrador
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    showAlert('No tienes permisos para acceder al panel de administrador', 'error');
    redirect('index.php');
}

$page_title = 'Soporte - Panel de Administrador';
include 'includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>🎯 Gestión de Soporte</h1>
        <p>Responde a las consultas y solicitudes de los usuarios</p>
    </div>

    <div class="support-dashboard">
        <div class="support-stats">
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-content">
                    <h3>Conversaciones Abiertas</h3>
                    <p class="stat-number" id="open-conversations">-</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📨</div>
                <div class="stat-content">
                    <h3>Mensajes Sin Leer</h3>
                    <p class="stat-number" id="unread-messages">-</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3>Conversaciones Cerradas</h3>
                    <p class="stat-number" id="closed-conversations">-</p>
                </div>
            </div>
        </div>

        <div class="conversations-container">
            <div class="conversations-list">
                <h3>Conversaciones de Soporte</h3>
                <div class="search-box">
                    <input type="text" id="conversation-search" placeholder="Buscar conversaciones...">
                </div>
                <div id="conversations-list">
                    <!-- Las conversaciones se cargarán dinámicamente -->
                </div>
            </div>

            <div class="chat-container" id="chatContainer" style="display: none;">
                <div class="chat-header">
                    <div class="chat-info">
                        <h3 id="chat-subject">Selecciona una conversación</h3>
                        <p id="chat-user">-</p>
                    </div>
                    <div class="chat-actions">
                        <button class="btn btn-secondary" id="close-chat-btn">Cerrar Chat</button>
                    </div>
                </div>

                <div class="messages-container" id="messagesContainer">
                    <div class="no-conversation-selected">
                        <p>Selecciona una conversación para ver los mensajes</p>
                    </div>
                </div>

                <div class="message-input-container" id="messageInputContainer" style="display: none;">
                    <form id="messageForm">
                        <input type="hidden" id="current-conversation-id">
                        <div class="input-group">
                            <textarea 
                                id="messageInput" 
                                placeholder="Escribe tu respuesta..." 
                                rows="3"
                                required
                            ></textarea>
                            <button type="submit" class="btn btn-primary">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos principales con color naranja */
.admin-header {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(249, 115, 22, 0.3);
}

.admin-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: bold;
}

.admin-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.support-dashboard {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.support-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(249, 115, 22, 0.15);
}

.stat-icon {
    font-size: 2rem;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
}

.stat-content h3 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    font-size: 0.9rem;
    font-weight: 600;
}

.stat-number {
    margin: 0;
    color: #f97316;
    font-size: 1.5rem;
    font-weight: bold;
}

.conversations-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
    height: 600px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.conversations-list {
    border-right: 1px solid #eee;
    display: flex;
    flex-direction: column;
}

.conversations-list h3 {
    padding: 1rem;
    margin: 0;
    border-bottom: 1px solid #eee;
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    font-weight: 600;
}

.search-box {
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.search-box input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 6px;
}

#conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.3s;
}

.conversation-item:hover {
    background: #f8f9fa;
}

.conversation-item.active {
    background: #f97316;
    color: white;
}

.conversation-item.unread {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.conversation-subject {
    font-weight: bold;
    font-size: 0.9rem;
}

.conversation-meta {
    font-size: 0.8rem;
    color: #6b7280;
}

.conversation-item.active .conversation-meta {
    color: rgba(255,255,255,0.8);
}

.unread-badge {
    background: #f97316;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.3);
}

.chat-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chat-header {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.chat-info h3 {
    margin: 0;
    font-size: 1.1rem;
}

.chat-info p {
    margin: 0.25rem 0 0 0;
    font-size: 0.9rem;
    color: #6b7280;
}

.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.no-conversation-selected {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
}

.message {
    margin-bottom: 1rem;
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
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
}

.message.user .message-content {
    background: #f8fafc;
    color: #1f2937;
    border: 1px solid #e2e8f0;
}

.message.admin .message-content {
    background: #f97316;
    color: white;
    border: 1px solid #ea580c;
}

.message-info {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.message-input-container {
    padding: 1rem;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.input-group {
    display: flex;
    gap: 1rem;
}

.input-group textarea {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    resize: none;
    font-family: inherit;
}

.input-group button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.875rem;
    transition: background 0.3s;
}

.btn-primary {
    background: #f97316;
    color: white;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #ea580c;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

/* Asegurar que todos los textos sean visibles */
.conversations-container {
    color: #1f2937;
}

.conversation-subject {
    color: inherit;
}

.search-box input {
    color: #1f2937;
    background: white;
}

.search-box input::placeholder {
    color: #9ca3af;
}

.input-group textarea {
    color: #1f2937;
    background: white;
}

.input-group textarea::placeholder {
    color: #9ca3af;
}

/* Mejorar contraste general */
.messages-container {
    color: #1f2937;
}

/* Estilos responsivos */
@media (max-width: 768px) {
    .conversations-container {
        grid-template-columns: 1fr;
    }
    
    .admin-header {
        padding: 1.5rem;
    }
    
    .admin-header h1 {
        font-size: 1.5rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
}
</style>

<script>
let currentConversationId = null;

// Cargar conversaciones al iniciar
document.addEventListener('DOMContentLoaded', function() {
    loadConversations();
    loadStats();
});

// Cargar estadísticas
function loadStats() {
    fetch('api/admin-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('open-conversations').textContent = data.stats.open_conversations || 0;
                document.getElementById('unread-messages').textContent = data.stats.unread_messages || 0;
                document.getElementById('closed-conversations').textContent = data.stats.closed_conversations || 0;
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Cargar conversaciones
function loadConversations() {
    fetch('api/admin-support-conversations.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversations(data.conversations);
            }
        })
        .catch(error => console.error('Error loading conversations:', error));
}

// Mostrar conversaciones
function displayConversations(conversations) {
    const container = document.getElementById('conversations-list');
    container.innerHTML = '';

    if (conversations.length === 0) {
        container.innerHTML = '<div style="padding: 2rem; text-align: center; color: #6b7280;">No hay conversaciones</div>';
        return;
    }

    conversations.forEach(conversation => {
        const item = document.createElement('div');
        item.className = `conversation-item ${conversation.unread_count > 0 ? 'unread' : ''}`;
        item.onclick = () => selectConversation(conversation);

        const lastMessage = conversation.last_message || 'Sin mensajes';
        const lastMessageTime = conversation.last_message_time ? new Date(conversation.last_message_time).toLocaleString('es-ES') : '';

        item.innerHTML = `
            <div class="conversation-header">
                <span class="conversation-subject">${conversation.subject}</span>
                ${conversation.unread_count > 0 ? `<span class="unread-badge">${conversation.unread_count}</span>` : ''}
            </div>
            <div class="conversation-meta">
                <div>${conversation.username} (${conversation.email})</div>
                <div>${lastMessage.substring(0, 50)}${lastMessage.length > 50 ? '...' : ''}</div>
                <div>${lastMessageTime}</div>
            </div>
        `;

        container.appendChild(item);
    });
}

// Seleccionar conversación
function selectConversation(conversation) {
    currentConversationId = conversation.id;
    
    // Actualizar UI
    document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    document.getElementById('chat-subject').textContent = conversation.subject;
    document.getElementById('chat-user').textContent = `${conversation.username} (${conversation.email})`;
    document.getElementById('current-conversation-id').value = conversation.id;
    
    document.getElementById('chatContainer').style.display = 'flex';
    document.getElementById('messageInputContainer').style.display = 'block';
    
    loadMessages(conversation.id);
}

// Cargar mensajes
function loadMessages(conversationId) {
    fetch(`api/get-support-messages.php?conversation_id=${conversationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMessages(data.messages);
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

// Mostrar mensajes
function displayMessages(messages) {
    const container = document.getElementById('messagesContainer');
    container.innerHTML = '';

    if (messages.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 2rem;">No hay mensajes en esta conversación</div>';
        return;
    }

    messages.forEach(message => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.sender_type}`;
        
        const messageTime = new Date(message.created_at).toLocaleString('es-ES');
        
        messageDiv.innerHTML = `
            <div class="message-content">
                ${message.message.replace(/\n/g, '<br>')}
            </div>
            <div class="message-info">
                ${message.sender_name} • ${messageTime}
            </div>
        `;
        
        container.appendChild(messageDiv);
    });
    
    // Scroll al final
    container.scrollTop = container.scrollHeight;
}

// Enviar mensaje
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const message = document.getElementById('messageInput').value.trim();
    if (!message || !currentConversationId) return;

    const formData = new FormData();
    formData.append('conversation_id', currentConversationId);
    formData.append('message', message);

    fetch('api/admin-send-support-message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('messageInput').value = '';
            loadMessages(currentConversationId);
            loadConversations(); // Recargar para actualizar contadores
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al enviar mensaje');
    });
});

// Cerrar chat
document.getElementById('close-chat-btn').addEventListener('click', function() {
    document.getElementById('chatContainer').style.display = 'none';
    document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));
    currentConversationId = null;
});

// Búsqueda de conversaciones
document.getElementById('conversation-search').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? 'block' : 'none';
    });
});

// Auto-resize del textarea
document.getElementById('messageInput').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
</script>

<?php include 'includes/footer.php'; ?> 