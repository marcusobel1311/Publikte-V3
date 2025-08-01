<?php
require_once 'config/config.php';

// Verificar si el usuario está logueado y es administrador
if (!isLoggedIn()) {
    redirect('login.php');
}

$currentUser = getCurrentUser();
if ($currentUser['id'] != 1) { // El usuario con ID 1 es el administrador
    showAlert('No tienes permisos para acceder al panel de administrador', 'error');
    redirect('index.php');
}

$page_title = 'Panel de Administrador';
include 'includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Panel de Administrador</h1>
        <p>Gestiona usuarios, productos y configuración del sistema</p>
    </div>

    <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>Usuarios Registrados</h3>
                <p class="stat-number" id="total-users">-</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <h3>Productos Activos</h3>
                <p class="stat-number" id="total-products">-</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Ventas Totales</h3>
                <p class="stat-number" id="total-sales">-</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3>Comisiones</h3>
                <p class="stat-number" id="total-commissions">-</p>
            </div>
        </div>
    </div>

    <div class="admin-tabs">
        <button class="tab-button active" data-tab="users">Usuarios</button>
        <button class="tab-button" data-tab="products">Productos</button>
        <button class="tab-button" data-tab="orders">Órdenes</button>
        <button class="tab-button" data-tab="reports">Reportes</button>
        <a href="admin-support.php" class="tab-button support-link">Soporte</a>
    </div>

    <div class="tab-content">
        <!-- Tab de Usuarios -->
        <div id="users" class="tab-panel active">
            <div class="panel-header">
                <h2>Gestión de Usuarios</h2>
                <div class="search-box">
                    <input type="text" id="user-search" placeholder="Buscar usuarios...">
                </div>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Nombre Completo</th>
                            <th>Ubicación</th>
                            <th>Wallet</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <!-- Los usuarios se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab de Productos -->
        <div id="products" class="tab-panel">
            <div class="panel-header">
                <h2>Gestión de Productos</h2>
                <div class="search-box">
                    <input type="text" id="product-search" placeholder="Buscar productos...">
                </div>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Título</th>
                            <th>Vendedor</th>
                            <th>Precio</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Vistas</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body">
                        <!-- Los productos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab de Órdenes -->
        <div id="orders" class="tab-panel">
            <div class="panel-header">
                <h2>Gestión de Órdenes</h2>
                <div class="search-box">
                    <input type="text" id="order-search" placeholder="Buscar órdenes...">
                </div>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Comprador</th>
                            <th>Vendedor</th>
                            <th>Producto</th>
                            <th>Total</th>
                            <th>Comisión</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body">
                        <!-- Las órdenes se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab de Reportes -->
        <div id="reports" class="tab-panel">
            <div class="panel-header">
                <h2>Reportes del Sistema</h2>
            </div>
            <div class="reports-grid">
                <div class="report-card">
                    <h3>Usuarios por Mes</h3>
                    <div class="chart-container">
                        <canvas id="users-chart"></canvas>
                    </div>
                </div>
                <div class="report-card">
                    <h3>Ventas por Categoría</h3>
                    <div class="chart-container">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
                <div class="report-card">
                    <h3>Productos por Estado</h3>
                    <div class="chart-container">
                        <canvas id="products-chart"></canvas>
                    </div>
                </div>
                <div class="report-card">
                    <h3>Ingresos Mensuales</h3>
                    <div class="chart-container">
                        <canvas id="income-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab de Soporte -->
        <div id="support" class="tab-panel">
            <div class="panel-header">
                <h2>Chat de Soporte</h2>
                <a href="admin-chat.php" class="btn btn-primary">Ir al Panel de Chat</a>
            </div>
            <div class="support-overview">
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
                <div class="recent-conversations">
                    <h3>Conversaciones Recientes</h3>
                    <div id="recent-conversations-list">
                        <!-- Las conversaciones se cargarán dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div id="edit-user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Usuario</h3>
            <button class="modal-close">&times;</button>
        </div>
        <form id="edit-user-form">
            <input type="hidden" id="edit-user-id">
            <div class="form-group">
                <label for="edit-username">Usuario</label>
                <input type="text" id="edit-username" required>
            </div>
            <div class="form-group">
                <label for="edit-email">Email</label>
                <input type="email" id="edit-email" required>
            </div>
            <div class="form-group">
                <label for="edit-full-name">Nombre Completo</label>
                <input type="text" id="edit-full-name" required>
            </div>
            <div class="form-group">
                <label for="edit-location">Ubicación</label>
                <input type="text" id="edit-location">
            </div>
            <div class="form-group">
                <label for="edit-wallet">Wallet Balance</label>
                <input type="number" id="edit-wallet" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label for="edit-status">Estado</label>
                <select id="edit-status">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('edit-user-modal')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div id="delete-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Eliminación</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p id="delete-message">¿Estás seguro de que quieres eliminar este elemento?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('delete-modal')">Cancelar</button>
            <button class="btn btn-destructive" id="confirm-delete">Eliminar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/admin-panel.js"></script>

<?php include 'includes/footer.php'; ?> 