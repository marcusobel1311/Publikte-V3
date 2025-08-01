<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$page_title = 'Test de Interfaz de Soporte';
include 'includes/header.php';
?>

<div class="support-container">
    <div class="support-header">
        <h1>Test de Interfaz de Soporte</h1>
        <p>Esta es una página de prueba para verificar que el botón y modal funcionan correctamente.</p>
    </div>

    <button class="new-conversation-btn" onclick="openNewConversationModal()">
        + Nueva conversación (Test)
    </button>

    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
        <h3>Instrucciones de prueba:</h3>
        <ol>
            <li>Haz clic en el botón "Nueva conversación" arriba</li>
            <li>Debería abrirse un modal con un formulario</li>
            <li>Completa el asunto y mensaje</li>
            <li>Haz clic en "Enviar"</li>
            <li>Revisa la consola del navegador (F12) para ver los logs</li>
        </ol>
    </div>
</div>

<!-- Modal para nueva conversación -->
<div id="newConversationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Nueva conversación (Test)</h2>
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

<style>
.support-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.support-header {
    text-align: center;
    margin-bottom: 2rem;
}

.support-header h1 {
    color: var(--gray-800);
    margin-bottom: 0.5rem;
}

.support-header p {
    color: var(--gray-600);
}

.new-conversation-btn {
    background: var(--primary-color);
    color: white;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    margin-bottom: 2rem;
    transition: background 0.3s;
    display: inline-block;
    text-decoration: none;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.new-conversation-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.new-conversation-btn:active {
    transform: translateY(0);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(2px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
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
    background: var(--primary-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
    width: 100%;
    font-weight: 500;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
</style>

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
            
            console.log('Asunto:', subject);
            console.log('Mensaje:', message);
            
            // Simular envío (para prueba)
            alert('Formulario enviado correctamente!\nAsunto: ' + subject + '\nMensaje: ' + message);
            closeNewConversationModal();
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

<?php include 'includes/footer.php'; ?> 