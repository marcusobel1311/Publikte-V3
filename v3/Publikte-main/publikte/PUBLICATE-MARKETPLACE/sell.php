<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$database = new Database();
$db = $database->getConnection();

// Obtener categorías
$categories_query = "SELECT * FROM categories ORDER BY name";
$stmt = $db->prepare($categories_query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar saldo para publicar
$user = getCurrentUser();
$can_publish = $user['wallet_balance'] >= LISTING_COST;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$can_publish) {
        $error = 'Saldo insuficiente. Necesitas $' . LISTING_COST . ' para publicar.';
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $original_price = !empty($_POST['original_price']) ? floatval($_POST['original_price']) : null;
        $category_id = intval($_POST['category_id']);
        $condition_type = $_POST['condition_type'];
        $location = trim($_POST['location']);
        $shipping_type = $_POST['shipping_type'];
        $warranty_months = isset($_POST['warranty_months']) ? intval($_POST['warranty_months']) : 0;
        $is_barter = isset($_POST['is_barter']) ? intval($_POST['is_barter']) : 0;
        $barter_for = ($is_barter === 1 || $is_barter === 2) ? trim($_POST['barter_for']) : null;

        // Validaciones
        if (empty($title) || empty($description) || $price <= 0 || !$category_id || empty($condition_type) || empty($location)) {
            $error = 'Por favor completa todos los campos obligatorios';
        } elseif ($original_price && $original_price <= $price) {
            $error = 'El precio original debe ser mayor al precio actual';
        } else {
            try {
                $db->beginTransaction();

                // Crear producto
                $product_query = "INSERT INTO products (user_id, category_id, title, description, price, original_price, condition_type, location, shipping_type, warranty_months, is_barter, barter_for) 
                                  VALUES (:user_id, :category_id, :title, :description, :price, :original_price, :condition_type, :location, :shipping_type, :warranty_months, :is_barter, :barter_for)";
                $stmt = $db->prepare($product_query);
                $stmt->bindValue(':user_id', $_SESSION['user_id']);
                $stmt->bindValue(':category_id', $category_id);
                $stmt->bindValue(':title', $title);
                $stmt->bindValue(':description', $description);
                $stmt->bindValue(':price', $price);
                $stmt->bindValue(':original_price', $original_price);
                $stmt->bindValue(':condition_type', $condition_type);
                $stmt->bindValue(':location', $location);
                $stmt->bindValue(':shipping_type', $shipping_type);
                $stmt->bindValue(':warranty_months', $warranty_months);
                $stmt->bindValue(':is_barter', $is_barter);
                $stmt->bindValue(':barter_for', $barter_for);
                $stmt->execute();

                $product_id = $db->lastInsertId();

                // Procesar imágenes si se subieron
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $upload_dir = 'assets/uploads/products/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $error = 'No se pudo crear la carpeta de subida de imágenes.';
                            throw new Exception($error);
                        }
                    }

                    $image_count = 0;
                    $max_images = defined('MAX_IMAGES_PER_PRODUCT') ? MAX_IMAGES_PER_PRODUCT : 5;

                    foreach ($_FILES['images']['name'] as $key => $filename) {
                        if ($image_count >= $max_images)
                            break;

                        if (!empty($filename)) {
                            if ($_FILES['images']['error'][$key] !== 0) {
                                $error = 'Error al subir la imagen "' . htmlspecialchars($filename) . '": código de error ' . $_FILES['images']['error'][$key];
                                throw new Exception($error);
                            }
                            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                            if (!in_array($file_extension, $allowed_extensions)) {
                                $error = 'La imagen "' . htmlspecialchars($filename) . '" no tiene un formato permitido (jpg, jpeg, png, gif).';
                                throw new Exception($error);
                            }
                            if ($_FILES['images']['size'][$key] > 10 * 1024 * 1024) {
                                $error = 'La imagen "' . htmlspecialchars($filename) . '" supera el tamaño máximo de 10MB.';
                                throw new Exception($error);
                            }

                            $new_filename = $product_id . '_' . $image_count . '_' . time() . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;

                            if (!move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                                $error = 'No se pudo guardar la imagen "' . htmlspecialchars($filename) . '". Verifica los permisos de la carpeta.';
                                throw new Exception($error);
                            }

                            $image_query = "INSERT INTO product_images (product_id, image_url, is_primary, sort_order) 
                                            VALUES (:product_id, :image_url, :is_primary, :sort_order)";
                            $stmt = $db->prepare($image_query);
                            $stmt->bindValue(':product_id', $product_id);
                            $stmt->bindValue(':image_url', $upload_path);
                            $stmt->bindValue(':is_primary', $image_count == 0 ? 1 : 0);
                            $stmt->bindValue(':sort_order', $image_count);
                            $stmt->execute();

                            $image_count++;
                        }
                    }
                }

                // Descontar costo de publicación
                $listing_cost = defined('LISTING_COST') ? LISTING_COST : 5.00;
                $update_wallet = "UPDATE users SET wallet_balance = wallet_balance - :cost WHERE id = :user_id";
                $stmt = $db->prepare($update_wallet);
                $stmt->bindValue(':cost', $listing_cost);
                $stmt->bindValue(':user_id', $_SESSION['user_id']);
                $stmt->execute();

                // Agregar transacción
                $transaction_query = "INSERT INTO wallet_transactions (user_id, type, amount, description, reference_id, reference_type) 
                                      VALUES (:user_id, 'expense', :amount, 'Publicación de producto', :product_id, 'listing')";
                $stmt = $db->prepare($transaction_query);
                $stmt->bindValue(':user_id', $_SESSION['user_id']);
                $stmt->bindValue(':amount', $listing_cost);
                $stmt->bindValue(':product_id', $product_id);
                $stmt->execute();

                $db->commit();

                $success = '¡Producto publicado exitosamente! Se ha descontado $' . $listing_cost . ' de tu wallet.';
                // Redirigir después de un breve delay para mostrar el mensaje
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'product.php?id=" . $product_id . "';
                    }, 2000);
                </script>";

            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Error al crear la publicación: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Crear Nueva Publicación';
include 'includes/header.php';
?>

<div style="padding: 2rem 0;">
    <div style="max-width: 1000px; margin: 0 auto; padding: 0 1rem;">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2.5rem; font-weight: 900; color: var(--gray-800); margin-bottom: 1rem;">Crear Nueva
                Publicación</h1>
            <div
                style="background: linear-gradient(to right, var(--primary-100), var(--secondary-100)); padding: 1rem; border-radius: 0.75rem; border: 1px solid var(--primary-200);">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"
                        style="color: var(--primary-600);">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                        <path fill-rule="evenodd"
                            d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"
                            clip-rule="evenodd" />
                    </svg>
                    <span style="font-weight: 600; color: var(--gray-800);">Costo de publicación:
                        <?php echo defined('LISTING_COST') ? formatPrice(LISTING_COST) : '$5.00'; ?></span>
                </div>
                <p style="font-size: 0.875rem; color: var(--gray-600); margin-top: 0.25rem;">
                    Se descontará automáticamente de tu wallet al publicar
                    <?php if (!$can_publish): ?>
                        <br><strong style="color: var(--red-600);">Saldo insuficiente. <a href="wallet.php"
                                style="color: var(--primary-600);">Recarga tu wallet</a></strong>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"
                style="background: var(--red-100); color: var(--red-800); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid var(--red-200);">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"
                style="background: var(--green-100); color: var(--green-800); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid var(--green-200);">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="sellForm">
            <div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Información básica -->
                <div class="card"
                    style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--gray-200);">
                    <div class="card-header" style="padding: 1.5rem 1.5rem 0 1.5rem;">
                        <h2 class="card-title"
                            style="display: flex; align-items: center; font-size: 1.25rem; font-weight: 700; color: var(--gray-800); margin: 0;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"
                                style="margin-right: 0.5rem; color: var(--primary-600);">
                                <path fill-rule="evenodd"
                                    d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                    clip-rule="evenodd" />
                            </svg>
                            Información del Producto
                        </h2>
                    </div>
                    <div class="card-content" style="padding: 1.5rem;">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="title" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Título
                                del producto *</label>
                            <input type="text" id="title" name="title" class="form-input" required
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem;"
                                placeholder="Ej: iPhone 14 Pro Max 256GB"
                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="description" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Descripción
                                *</label>
                            <textarea id="description" name="description" class="form-textarea" required
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; min-height: 100px; resize: vertical;"
                                placeholder="Describe tu producto en detalle..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="grid grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="price" class="form-label"
                                    style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Precio
                                    *</label>
                                <input type="number" id="price" name="price" class="form-input" required
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem;"
                                    placeholder="0.00" step="0.01" min="0.01"
                                    value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="original_price" class="form-label"
                                    style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Precio
                                    original (opcional)</label>
                                <input type="number" id="original_price" name="original_price" class="form-input"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem;"
                                    placeholder="0.00" step="0.01" min="0.01"
                                    value="<?php echo isset($_POST['original_price']) ? $_POST['original_price'] : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="category_id" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Categoría
                                *</label>
                            <select id="category_id" name="category_id" class="form-select" required
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; background: white;">
                                <option value="">Selecciona una categoría</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="condition_type" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Estado
                                del producto *</label>
                            <select id="condition_type" name="condition_type" class="form-select" required
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; background: white;">
                                <option value="">Selecciona el estado</option>
                                <option value="new" <?php echo (isset($_POST['condition_type']) && $_POST['condition_type'] == 'new') ? 'selected' : ''; ?>>Nuevo</option>
                                <option value="like-new" <?php echo (isset($_POST['condition_type']) && $_POST['condition_type'] == 'like-new') ? 'selected' : ''; ?>>Como nuevo</option>
                                <option value="excellent" <?php echo (isset($_POST['condition_type']) && $_POST['condition_type'] == 'excellent') ? 'selected' : ''; ?>>Excelente estado
                                </option>
                                <option value="good" <?php echo (isset($_POST['condition_type']) && $_POST['condition_type'] == 'good') ? 'selected' : ''; ?>>Buen estado</option>
                                <option value="fair" <?php echo (isset($_POST['condition_type']) && $_POST['condition_type'] == 'fair') ? 'selected' : ''; ?>>Estado regular</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="location" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Ubicación
                                *</label>
                            <input type="text" id="location" name="location" class="form-input" required
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem;"
                                placeholder="Ciudad, Provincia"
                                value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : (isset($user['location']) ? htmlspecialchars($user['location']) : ''); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="shipping_type" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Tipo
                                de envío</label>
                            <select id="shipping_type" name="shipping_type" class="form-select"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; background: white;">
                                <option value="free" <?php echo (!isset($_POST['shipping_type']) || $_POST['shipping_type'] == 'free') ? 'selected' : ''; ?>>Envío gratis</option>
                                <option value="paid" <?php echo (isset($_POST['shipping_type']) && $_POST['shipping_type'] == 'paid') ? 'selected' : ''; ?>>Envío pago</option>
                                <option value="pickup" <?php echo (isset($_POST['shipping_type']) && $_POST['shipping_type'] == 'pickup') ? 'selected' : ''; ?>>Solo retiro</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="warranty_months" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Garantía</label>
                            <select id="warranty_months" name="warranty_months" class="form-select"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; background: white;">
                                <option value="0" <?php echo (!isset($_POST['warranty_months']) || $_POST['warranty_months'] == '0') ? 'selected' : ''; ?>>Sin garantía</option>
                                <option value="1" <?php echo (isset($_POST['warranty_months']) && $_POST['warranty_months'] == '1') ? 'selected' : ''; ?>>1 mes</option>
                                <option value="3" <?php echo (isset($_POST['warranty_months']) && $_POST['warranty_months'] == '3') ? 'selected' : ''; ?>>3 meses</option>
                                <option value="6" <?php echo (isset($_POST['warranty_months']) && $_POST['warranty_months'] == '6') ? 'selected' : ''; ?>>6 meses</option>
                                <option value="12" <?php echo (isset($_POST['warranty_months']) && $_POST['warranty_months'] == '12') ? 'selected' : ''; ?>>12 meses</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="is_barter" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">
                                ¿Aceptas trueque/cambio?
                            </label>
                            <select id="is_barter" name="is_barter" class="form-select"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem; background: white;">
                                <option value="0" <?php echo (!isset($_POST['is_barter']) || $_POST['is_barter'] == '0') ? 'selected' : ''; ?>>Solo venta</option>
                                <option value="1" <?php echo (isset($_POST['is_barter']) && $_POST['is_barter'] == '1') ? 'selected' : ''; ?>>Solo trueque/cambio</option>
                                <option value="2" <?php echo (isset($_POST['is_barter']) && $_POST['is_barter'] == '2') ? 'selected' : ''; ?>>Ambas</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem; <?php echo (isset($_POST['is_barter']) && ($_POST['is_barter'] == '1' || $_POST['is_barter'] == '2')) ? '' : 'display:none;'; ?>">
                            <label for="barter_for" class="form-label"
                                style="display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">¿Qué aceptas a cambio? <span style="color: var(--gray-500); font-weight: 400; font-size: 0.95em;">(opcional)</span></label>
                            <input type="text" id="barter_for" name="barter_for" class="form-input"
                                style="width: 100%; padding: 0.75rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; font-size: 1rem;"
                                placeholder="Ej: iPhone 13 Pro Max 256GB"
                                value="<?php echo isset($_POST['barter_for']) ? htmlspecialchars($_POST['barter_for']) : ''; ?>">
                        </div>
                        <script>
                        document.getElementById('is_barter').addEventListener('change', function() {
                            var barterField = document.getElementById('barter_for').parentNode;
                            if (this.value == '1' || this.value == '2') {
                                barterField.style.display = '';
                            } else {
                                barterField.style.display = 'none';
                            }
                        });
                        </script>
                    </div>
                </div>

                <!-- Imágenes -->
                <div class="card"
                    style="background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--gray-200);">
                    <div class="card-header" style="padding: 1.5rem 1.5rem 0 1.5rem;">
                        <h2 class="card-title"
                            style="font-size: 1.25rem; font-weight: 700; color: var(--gray-800); margin: 0;">Imágenes
                            del Producto</h2>
                        <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0.5rem 0 0 0;">Máximo
                            <?php echo defined('MAX_IMAGES_PER_PRODUCT') ? MAX_IMAGES_PER_PRODUCT : 5; ?> imágenes</p>
                    </div>
                    <div class="card-content" style="padding: 1.5rem;">
                        <div class="form-group">
                            <!-- Upload area -->
                            <div id="uploadArea"
                                style="position: relative; border: 2px dashed var(--gray-300); border-radius: 0.75rem; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.2s;">
                                <input type="file" id="images" name="images[]" multiple accept="image/*"
                                    style="position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2;">
                                <svg width="48" height="48"
                                    style="color: var(--gray-400); margin: 0 auto 1rem; display: block;" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p style="color: var(--gray-600); font-weight: 500; margin-bottom: 0.25rem;">Haz clic
                                    para subir imágenes</p>
                                <p style="font-size: 0.875rem; color: var(--gray-500); margin: 0;">PNG, JPG hasta 10MB
                                    cada una</p>
                            </div>

                            <!-- Image preview -->
                            <div id="imagePreview" style="margin-top: 1rem; display: none;">
                                <div id="previewContainer"
                                    style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit buttons -->
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn"
                    style="background: none; border: 1px solid var(--gray-300); color: var(--gray-700); padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                    Guardar como borrador
                </button>
                <button type="submit" class="btn btn-primary btn-lg" <?php echo !$can_publish ? 'disabled' : ''; ?>
                    style="background: var(--primary-600); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer; <?php echo !$can_publish ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>">
                    Publicar por <?php echo defined('LISTING_COST') ? formatPrice(LISTING_COST) : '$5.00'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Variables globales para manejo de imágenes
    let selectedFiles = [];
    const maxImages = <?php echo defined('MAX_IMAGES_PER_PRODUCT') ? MAX_IMAGES_PER_PRODUCT : 5; ?>;

    // Inicialización cuando se carga la página
    document.addEventListener('DOMContentLoaded', function () {
        setupImageUpload();
        setupFormValidation();
    });

    // Configurar la subida de imágenes
    function setupImageUpload() {
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('images');

        if (!uploadArea || !fileInput) return;

        // Click en el área de subida
        uploadArea.addEventListener('click', function () {
            fileInput.click();
        });

        // Drag and drop
        uploadArea.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.style.borderColor = 'var(--primary-400)';
            uploadArea.style.backgroundColor = 'var(--primary-50)';
        });

        uploadArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.style.borderColor = 'var(--gray-300)';
            uploadArea.style.backgroundColor = 'transparent';
        });

        uploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.style.borderColor = 'var(--gray-300)';
            uploadArea.style.backgroundColor = 'transparent';

            const files = Array.from(e.dataTransfer.files);
            handleFileSelection(files);
        });

        // Cambio en el input de archivos
        fileInput.addEventListener('change', function (e) {
            const files = Array.from(e.target.files);
            handleFileSelection(files);
        });
    }

    // Manejar la selección de archivos
    function handleFileSelection(files) {
        // Filtrar solo imágenes
        const imageFiles = files.filter(file => file.type.startsWith('image/'));
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        if (!previewContainer || !imagePreview) return;

        // Limpiar previews anteriores
        previewContainer.innerHTML = '';
        selectedFiles = [];

        if (imageFiles.length === 0) {
            showAlert('Por favor selecciona solo archivos de imagen', 'warning');
            imagePreview.style.display = 'none';
            return;
        }

        // Verificar límite de imágenes
        if (imageFiles.length > maxImages) {
            showAlert(`Máximo ${maxImages} imágenes permitidas`, 'warning');
            imagePreview.style.display = 'none';
            return;
        }

        // Verificar tamaño de archivos (10MB máximo)
        const maxSize = 10 * 1024 * 1024; // 10MB
        const oversizedFiles = imageFiles.filter(file => file.size > maxSize);
        if (oversizedFiles.length > 0) {
            showAlert('Algunas imágenes son muy grandes (máximo 10MB)', 'error');
            imagePreview.style.display = 'none';
            return;
        }

        // Mostrar el contenedor de preview
        imagePreview.style.display = 'block';

        // Agregar archivos válidos y previsualizarlos
        imageFiles.forEach((file, idx) => {
            selectedFiles.push(file);
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'image-preview-item';
                previewDiv.style.position = 'relative';
                previewDiv.style.display = 'inline-block';
                previewDiv.setAttribute('data-index', idx);
                previewDiv.innerHTML = `
                    <img src="${e.target.result}"
                         style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 2px solid var(--gray-200);"
                         alt="Preview">
                    <button type="button"
                            onclick="removeImage(${idx})"
                            style="position: absolute; top: -0.5rem; right: -0.5rem;
                                   background: var(--red-500); color: white; border: none;
                                   border-radius: 50%; width: 2rem; height: 2rem;
                                   cursor: pointer; display: flex; align-items: center;
                                   justify-content: center; font-size: 1.2rem; font-weight: bold;
                                   box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                    ${idx === 0 ? `<span style=\"position: absolute; bottom: 0.5rem; left: 0.5rem; background: var(--primary-500); color: white; padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 0.25rem; font-weight: 600;\">Principal</span>` : ''}
                `;
                previewContainer.appendChild(previewDiv);
            };
            reader.readAsDataURL(file);
        });
    }

    // Agregar preview de imagen
    function addImagePreview(file) {
        console.log('addImagePreview llamado', file);
        const previewContainer = document.getElementById('previewContainer');
        const imagePreview = document.getElementById('imagePreview');
        if (!previewContainer) {
            alert('No se encontró el contenedor de previsualización (#previewContainer)');
            return;
        }
        if (imagePreview) {
            imagePreview.style.display = 'block';
        }

        const imageIndex = selectedFiles.length - 1;

        // Crear elemento de preview
        const previewDiv = document.createElement('div');
        previewDiv.className = 'image-preview-item';
        previewDiv.style.position = 'relative';
        previewDiv.style.display = 'inline-block';
        previewDiv.setAttribute('data-index', imageIndex);

        // Leer el archivo como URL de datos
        const reader = new FileReader();
        reader.onload = function (e) {
            previewDiv.innerHTML = `
            <img src="${e.target.result}"
                 style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 2px solid var(--gray-200);"
                 alt="Preview">
            <button type="button"
                    onclick="removeImage(${imageIndex})"
                    style="position: absolute; top: -0.5rem; right: -0.5rem;
                           background: var(--red-500); color: white; border: none;
                           border-radius: 50%; width: 2rem; height: 2rem;
                           cursor: pointer; display: flex; align-items: center;
                           justify-content: center; font-size: 1.2rem; font-weight: bold;
                           box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
            ${imageIndex === 0 ? `<span style="position: absolute; bottom: 0.5rem; left: 0.5rem;
                                          background: var(--primary-500); color: white;
                                          padding: 0.25rem 0.5rem; font-size: 0.75rem;
                                          border-radius: 0.25rem; font-weight: 600;">Principal</span>` : ''}
        `;
            previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
    }

    // Remover imagen
    function removeImage(index) {
        // Remover del array
        selectedFiles.splice(index, 1);

        // Limpiar el contenedor de preview
        const previewContainer = document.getElementById('previewContainer');
        if (!previewContainer) return;

        previewContainer.innerHTML = '';

        // Volver a crear todos los previews con índices actualizados
        selectedFiles.forEach((file, newIndex) => {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'image-preview-item';
            previewDiv.style.position = 'relative';
            previewDiv.style.display = 'inline-block';
            previewDiv.setAttribute('data-index', newIndex);

            const reader = new FileReader();
            reader.onload = function (e) {
                previewDiv.innerHTML = `
                <img src="${e.target.result}" 
                     style="width: 100%; height: 8rem; object-fit: cover; border-radius: 0.5rem; border: 2px solid var(--gray-200);"
                     alt="Preview">
                <button type="button" 
                        onclick="removeImage(${newIndex})" 
                        style="position: absolute; top: -0.5rem; right: -0.5rem; 
                               background: var(--red-500); color: white; border: none; 
                               border-radius: 50%; width: 2rem; height: 2rem; 
                               cursor: pointer; display: flex; align-items: center; 
                               justify-content: center; font-size: 1.2rem; font-weight: bold;
                               box-shadow: 0 2px 4px rgba(0,0,0,0.2);">×</button>
                ${newIndex === 0 ? `<span style="position: absolute; bottom: 0.5rem; left: 0.5rem; 
                                            background: var(--primary-500); color: white; 
                                            padding: 0.25rem 0.5rem; font-size: 0.75rem; 
                                            border-radius: 0.25rem; font-weight: 600;">Principal</span>` : ''}
            `;
                <!-- Bloque de prueba de previsualización independiente -->
<div style="margin-top:2rem;">
  <input type="file" id="testImages" multiple accept="image/*">
  <div id="testPreview" style="margin-top:1rem; display:flex; gap:1rem;"></div>
</div>
<script>
document.getElementById('testImages').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const preview = document.getElementById('testPreview');
    preview.innerHTML = '';
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.style.width = '120px';
                img.style.height = '120px';
                img.style.objectFit = 'cover';
                img.style.border = '2px solid #ccc';
                img.style.borderRadius = '8px';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>Error al crear la publicación: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'warranty_months' in 'field list'