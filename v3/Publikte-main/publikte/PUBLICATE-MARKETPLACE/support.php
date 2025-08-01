<?php
require_once 'config/config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Obtener conversaciones del usuario
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT sc.*, 
           (SELECT COUNT(*) FROM support_messages sm WHERE sm.conversation_id = sc.id AND sm.is_read = 0 AND sm.sender_type = 'admin') as unread_count,
           (SELECT message FROM support_messages sm WHERE sm.conversation_id = sc.id ORDER BY sm.created_at DESC LIMIT 1) as last_message
    FROM support_conversations sc 
    WHERE sc.user_id = ? 
    ORDER BY sc.updated_at DESC
");
$stmt->execute([$user_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - PUBLICATE</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Estilos principales similares a la página principal */
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Hero section para soporte */
        .support-hero {
            background: linear-gradient(135deg, var(--primary-500), var(--secondary-500));
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 1rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .support-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .support-hero-content {
            position: relative;
            z-index: 1;
        }
        
        .support-hero h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .support-hero p {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        /* Botón de nueva conversación mejorado */
        .new-conversation-btn {
            background: white;
            color: var(--primary-600);
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .new-conversation-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .new-conversation-btn:hover::before {
            left: 100%;
        }
        
        .new-conversation-btn:hover {
            background: var(--primary-50);
            color: var(--primary-700);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .new-conversation-btn:active {
            transform: translateY(0);
        }
        
        .new-conversation-btn::after {
            content: '💬';
            font-size: 1.25rem;
        }
        .conversations-list {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid var(--gray-100);
        }
        
        .conversations-list h3 {
            padding: 1.5rem;
            margin: 0;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .conversation-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .conversation-item:hover {
            background: var(--primary-50);
            transform: translateX(4px);
        }
        
        .conversation-item:last-child {
            border-bottom: none;
        }
        
        .conversation-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-500);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .conversation-item:hover::before {
            transform: scaleY(1);
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
        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        .last-message {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .unread-badge {
            background: var(--primary-color);
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
        .no-conversations {
            text-align: center;
            padding: 3rem;
            color: var(--gray-600);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2.5rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.4s ease-out;
            border: 1px solid var(--gray-100);
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-600);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-800);
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        .submit-btn {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .submit-btn:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .submit-btn:disabled::before {
            display: none;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .support-hero {
                padding: 2rem 1rem;
                margin-bottom: 2rem;
            }
            
            .support-hero h1 {
                font-size: 2rem;
            }
            
            .support-hero p {
                font-size: 1rem;
            }
            
            .new-conversation-btn {
                padding: 0.875rem 2rem;
                font-size: 1rem;
            }
            
            .modal-content {
                margin: 10% auto;
                padding: 1.5rem;
                width: 95%;
            }
            
            .conversations-list {
                margin: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="support-container">
        <!-- Hero Section para Soporte -->
        <div class="support-hero">
            <div class="support-hero-content">
                <h1>Centro de Soporte</h1>
                <p>¿Necesitas ayuda? Nuestro equipo está aquí para asistirte con cualquier consulta o problema que tengas.</p>
                <button class="new-conversation-btn" onclick="openNewConversationModal()">
                    Iniciar nueva conversación
                </button>
            </div>
        </div>

        <div class="conversations-list">
            <h3>Mis Conversaciones de Soporte</h3>
            <?php if (empty($conversations)): ?>
                <div class="no-conversations">
                    <h3>No tienes conversaciones de soporte</h3>
                    <p>Inicia una nueva conversación para obtener ayuda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conversation): ?>
                    <div class="conversation-item" onclick="openChat(<?php echo $conversation['id']; ?>)">
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
                        <div class="conversation-meta">
                            <span class="last-message"><?php echo htmlspecialchars($conversation['last_message']); ?></span>
                            <span><?php echo date('d/m/Y H:i', strtotime($conversation['updated_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para nueva conversación -->
    <div id="newConversationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Nueva conversación</h2>
                <span class="close" onclick="closeNewConversationModal()">&times;</span>
            </div>
            <form id="newConversationForm">
                <div class="form-group">
                    <label for="subject">Asunto</label>
                    <input type="text" id="subject" name="subject" required placeholder="Describe brevemente tu consulta">
                </div>
                <div class="form-group">
                    <label for="message">Mensaje</label>
                    <textarea id="message" name="message" required placeholder="Describe tu problema o consulta en detalle"></textarea>
                </div>
                <button type="submit" class="submit-btn">Enviar</button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Función para abrir el modal de nueva conversación
        function openNewConversationModal() {
            console.log('Abriendo modal de nueva conversación');
            const modal = document.getElementById('newConversationModal');
            if (modal) {
                modal.style.display = 'block';
                // Limpiar el formulario
                document.getElementById('newConversationForm').reset();
            } else {
                console.error('Modal no encontrado');
            }
        }

        // Función para cerrar el modal
        function closeNewConversationModal() {
            console.log('Cerrando modal');
            const modal = document.getElementById('newConversationModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Función para abrir el chat de una conversación
        function openChat(conversationId) {
            console.log('Abriendo chat para conversación:', conversationId);
            window.location.href = 'chat.php?id=' + conversationId;
        }

        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('newConversationModal');
            if (event.target === modal) {
                closeNewConversationModal();
            }
        }

        // Cerrar modal con la tecla Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeNewConversationModal();
            }
        });

        // Manejar envío del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('newConversationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Enviando formulario de nueva conversación');
                    
                    const subject = document.getElementById('subject').value.trim();
                    const message = document.getElementById('message').value.trim();
                    
                    if (!subject || !message) {
                        alert('Por favor completa todos los campos');
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('subject', subject);
                    formData.append('message', message);

                    // Mostrar indicador de carga
                    const submitBtn = form.querySelector('.submit-btn');
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Enviando...';
                    submitBtn.disabled = true;

                    fetch('api/create-conversation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Respuesta del servidor:', response);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos de respuesta:', data);
                        if (data.success) {
                            alert('Conversación creada exitosamente');
                            closeNewConversationModal();
                            // Recargar la página para mostrar la nueva conversación
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al crear la conversación. Revisa la consola para más detalles.');
                    })
                    .finally(() => {
                        // Restaurar botón
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    });
                });
            } else {
                console.error('Formulario no encontrado');
            }
        });

        // Verificar que todos los elementos estén presentes
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Verificando elementos de la página...');
            
            const modal = document.getElementById('newConversationModal');
            const form = document.getElementById('newConversationForm');
            const btn = document.querySelector('.new-conversation-btn');
            
            console.log('Modal encontrado:', !!modal);
            console.log('Formulario encontrado:', !!form);
            console.log('Botón encontrado:', !!btn);
        });
    </script>
</body>
</html> 