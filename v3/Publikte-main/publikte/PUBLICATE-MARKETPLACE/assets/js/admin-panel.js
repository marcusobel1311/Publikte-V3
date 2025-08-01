// Panel de Administrador - JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el panel
    initAdminPanel();
    
    // Cargar estadísticas
    loadStats();
    
    // Cargar datos iniciales
    loadUsers();
    loadProducts();
    loadOrders();
    
    // Configurar eventos
    setupEventListeners();
});

// Variables globales
let currentDeleteId = null;
let currentDeleteType = null;
let charts = {};

// Inicializar el panel
function initAdminPanel() {
    console.log('Panel de administrador inicializado');
}

// Configurar eventos
function setupEventListeners() {
    // Pestañas
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
    
    // Búsquedas
    document.getElementById('user-search').addEventListener('input', debounce(filterUsers, 300));
    document.getElementById('product-search').addEventListener('input', debounce(filterProducts, 300));
    document.getElementById('order-search').addEventListener('input', debounce(filterOrders, 300));
    
    // Formulario de edición de usuario
    document.getElementById('edit-user-form').addEventListener('submit', handleEditUser);
    
    // Modal de eliminación
    document.getElementById('confirm-delete').addEventListener('click', handleDelete);
    
    // Cerrar modales
    document.querySelectorAll('.modal-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal.id);
        });
    });
    
    // Cerrar modal al hacer clic fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
}

// Cambiar pestañas
function switchTab(tabName) {
    // Remover clase active de todas las pestañas
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
    
    // Activar pestaña seleccionada
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(tabName).classList.add('active');
    
    // Cargar datos específicos de la pestaña
    switch(tabName) {
        case 'users':
            loadUsers();
            break;
        case 'products':
            loadProducts();
            break;
        case 'orders':
            loadOrders();
            break;
        case 'reports':
            loadReports();
            break;
    }
}

// Cargar estadísticas
async function loadStats() {
    try {
        const response = await fetch('api/admin-stats.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('total-users').textContent = data.stats.total_users;
            document.getElementById('total-products').textContent = data.stats.total_products;
            document.getElementById('total-sales').textContent = formatPrice(data.stats.total_sales);
            document.getElementById('total-commissions').textContent = formatPrice(data.stats.total_commissions);
        }
    } catch (error) {
        console.error('Error cargando estadísticas:', error);
    }
}

// Cargar usuarios
async function loadUsers() {
    try {
        const response = await fetch('api/admin-users.php');
        const data = await response.json();
        
        if (data.success) {
            renderUsersTable(data.users);
        }
    } catch (error) {
        console.error('Error cargando usuarios:', error);
    }
}

// Renderizar tabla de usuarios
function renderUsersTable(users) {
    const tbody = document.getElementById('users-table-body');
    tbody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.email}</td>
            <td>${user.full_name}</td>
            <td>${user.location || '-'}</td>
            <td>${formatPrice(user.wallet_balance)}</td>
            <td>
                <span class="status-badge ${user.is_active ? 'status-active' : 'status-inactive'}">
                    ${user.is_active ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-edit" onclick="editUser(${user.id})" title="Editar">
                        ✏️
                    </button>
                    ${user.id !== 1 ? `<button class="btn-action btn-delete" onclick="deleteUser(${user.id})" title="Eliminar">🗑️</button>` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Cargar productos
async function loadProducts() {
    try {
        const response = await fetch('api/admin-products.php');
        const data = await response.json();
        
        if (data.success) {
            renderProductsTable(data.products);
        }
    } catch (error) {
        console.error('Error cargando productos:', error);
    }
}

// Renderizar tabla de productos
function renderProductsTable(products) {
    const tbody = document.getElementById('products-table-body');
    tbody.innerHTML = '';
    
    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.id}</td>
            <td>
                <img src="${product.image_url || 'assets/images/placeholder.JPG'}" 
                     alt="${product.title}" 
                     class="product-image-small">
            </td>
            <td>${product.title}</td>
            <td>${product.seller_name}</td>
            <td>${formatPrice(product.price)}</td>
            <td>${product.category_name}</td>
            <td>
                <span class="status-badge status-${product.status}">
                    ${getStatusText(product.status)}
                </span>
            </td>
            <td>${product.views}</td>
            <td>${formatDate(product.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <a href="product.php?id=${product.id}" class="btn-action btn-view" title="Ver">
                        👁️
                    </a>
                    <button class="btn-action btn-delete" onclick="deleteProduct(${product.id})" title="Eliminar">
                        🗑️
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Cargar órdenes
async function loadOrders() {
    try {
        const response = await fetch('api/admin-orders.php');
        const data = await response.json();
        
        if (data.success) {
            renderOrdersTable(data.orders);
        }
    } catch (error) {
        console.error('Error cargando órdenes:', error);
    }
}

// Renderizar tabla de órdenes
function renderOrdersTable(orders) {
    const tbody = document.getElementById('orders-table-body');
    tbody.innerHTML = '';
    
    orders.forEach(order => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${order.id}</td>
            <td>${order.buyer_name}</td>
            <td>${order.seller_name}</td>
            <td>${order.product_title}</td>
            <td>${formatPrice(order.total_amount)}</td>
            <td>${formatPrice(order.commission)}</td>
            <td>
                <span class="status-badge status-${order.status}">
                    ${getOrderStatusText(order.status)}
                </span>
            </td>
            <td>${formatDate(order.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-view" onclick="viewOrder(${order.id})" title="Ver detalles">
                        👁️
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Cargar reportes
async function loadReports() {
    try {
        const response = await fetch('api/admin-reports.php');
        const data = await response.json();
        
        if (data.success) {
            createCharts(data.reports);
        }
    } catch (error) {
        console.error('Error cargando reportes:', error);
    }
}

// Crear gráficos
function createCharts(reports) {
    // Gráfico de usuarios por mes
    if (charts.usersChart) {
        charts.usersChart.destroy();
    }
    
    const usersCtx = document.getElementById('users-chart').getContext('2d');
    charts.usersChart = new Chart(usersCtx, {
        type: 'line',
        data: {
            labels: reports.users_by_month.labels,
            datasets: [{
                label: 'Usuarios Registrados',
                data: reports.users_by_month.data,
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de ventas por categoría
    if (charts.salesChart) {
        charts.salesChart.destroy();
    }
    
    const salesCtx = document.getElementById('sales-chart').getContext('2d');
    charts.salesChart = new Chart(salesCtx, {
        type: 'doughnut',
        data: {
            labels: reports.sales_by_category.labels,
            datasets: [{
                data: reports.sales_by_category.data,
                backgroundColor: [
                    '#f97316', '#3b82f6', '#10b981', '#f59e0b',
                    '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Gráfico de productos por estado
    if (charts.productsChart) {
        charts.productsChart.destroy();
    }
    
    const productsCtx = document.getElementById('products-chart').getContext('2d');
    charts.productsChart = new Chart(productsCtx, {
        type: 'bar',
        data: {
            labels: reports.products_by_status.labels,
            datasets: [{
                label: 'Productos',
                data: reports.products_by_status.data,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Gráfico de ingresos mensuales
    if (charts.incomeChart) {
        charts.incomeChart.destroy();
    }
    
    const incomeCtx = document.getElementById('income-chart').getContext('2d');
    charts.incomeChart = new Chart(incomeCtx, {
        type: 'bar',
        data: {
            labels: reports.income_by_month.labels,
            datasets: [{
                label: 'Ingresos',
                data: reports.income_by_month.data,
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Editar usuario
async function editUser(userId) {
    try {
        const response = await fetch(`api/admin-user-details.php?id=${userId}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.user;
            document.getElementById('edit-user-id').value = user.id;
            document.getElementById('edit-username').value = user.username;
            document.getElementById('edit-email').value = user.email;
            document.getElementById('edit-full-name').value = user.full_name;
            document.getElementById('edit-location').value = user.location || '';
            document.getElementById('edit-wallet').value = user.wallet_balance;
            document.getElementById('edit-status').value = user.is_active ? '1' : '0';
            
            openModal('edit-user-modal');
        }
    } catch (error) {
        console.error('Error cargando detalles del usuario:', error);
    }
}

// Manejar edición de usuario
async function handleEditUser(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('id', document.getElementById('edit-user-id').value);
    formData.append('username', document.getElementById('edit-username').value);
    formData.append('email', document.getElementById('edit-email').value);
    formData.append('full_name', document.getElementById('edit-full-name').value);
    formData.append('location', document.getElementById('edit-location').value);
    formData.append('wallet_balance', document.getElementById('edit-wallet').value);
    formData.append('is_active', document.getElementById('edit-status').value);
    
    try {
        const response = await fetch('api/admin-update-user.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal('edit-user-modal');
            showAlert('Usuario actualizado correctamente', 'success');
            loadUsers();
            loadStats();
        } else {
            showAlert(data.message || 'Error al actualizar usuario', 'error');
        }
    } catch (error) {
        console.error('Error actualizando usuario:', error);
        showAlert('Error al actualizar usuario', 'error');
    }
}

// Eliminar usuario
function deleteUser(userId) {
    currentDeleteId = userId;
    currentDeleteType = 'user';
    document.getElementById('delete-message').textContent = '¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.';
    openModal('delete-modal');
}

// Eliminar producto
function deleteProduct(productId) {
    currentDeleteId = productId;
    currentDeleteType = 'product';
    document.getElementById('delete-message').textContent = '¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.';
    openModal('delete-modal');
}

// Manejar eliminación
async function handleDelete() {
    if (!currentDeleteId || !currentDeleteType) return;
    
    try {
        const response = await fetch(`api/admin-delete-${currentDeleteType}.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: currentDeleteId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal('delete-modal');
            showAlert(`${currentDeleteType === 'user' ? 'Usuario' : 'Producto'} eliminado correctamente`, 'success');
            
            // Recargar datos
            switch(currentDeleteType) {
                case 'user':
                    loadUsers();
                    break;
                case 'product':
                    loadProducts();
                    break;
            }
            loadStats();
        } else {
            showAlert(data.message || 'Error al eliminar', 'error');
        }
    } catch (error) {
        console.error('Error eliminando:', error);
        showAlert('Error al eliminar', 'error');
    }
    
    currentDeleteId = null;
    currentDeleteType = null;
}

// Ver orden
function viewOrder(orderId) {
    window.open(`order-details.php?id=${orderId}`, '_blank');
}

// Filtrar usuarios
function filterUsers() {
    const searchTerm = document.getElementById('user-search').value.toLowerCase();
    const rows = document.querySelectorAll('#users-table-body tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Filtrar productos
function filterProducts() {
    const searchTerm = document.getElementById('product-search').value.toLowerCase();
    const rows = document.querySelectorAll('#products-table-body tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Filtrar órdenes
function filterOrders() {
    const searchTerm = document.getElementById('order-search').value.toLowerCase();
    const rows = document.querySelectorAll('#orders-table-body tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// Utilidades
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function formatPrice(price) {
    return '$' + parseFloat(price).toLocaleString('es-VE', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-VE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function getStatusText(status) {
    const statusMap = {
        'active': 'Activo',
        'sold': 'Vendido',
        'archived': 'Archivado',
        'deleted': 'Eliminado'
    };
    return statusMap[status] || status;
}

function getOrderStatusText(status) {
    const statusMap = {
        'pending': 'Pendiente',
        'processing': 'Procesando',
        'shipped': 'Enviado',
        'delivered': 'Entregado',
        'cancelled': 'Cancelado'
    };
    return statusMap[status] || status;
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
} 