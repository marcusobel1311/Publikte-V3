<?php
require_once '../../config/config.php';
$database = new Database();
$db = $database->getConnection();

$seller_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($seller_id <= 0) {
    echo '<div style="color:red; padding:2rem;">Vendedor no especificado.</div>';
    exit;
}
// Obtener datos del vendedor
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $seller_id);
$stmt->execute();
$seller = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$seller) {
    echo '<div style="color:red; padding:2rem;">Vendedor no encontrado.</div>';
    exit;
}
// Obtener reseñas recibidas por el vendedor (de todos sus productos)
$reviews_query = "SELECT r.*, u.username, u.full_name, p.title as product_title FROM reviews r JOIN users u ON r.reviewer_id = u.id JOIN products p ON r.product_id = p.id WHERE p.user_id = :seller_id ORDER BY r.created_at DESC";
$stmt = $db->prepare($reviews_query);
$stmt->bindParam(':seller_id', $seller_id);
$stmt->execute();
$seller_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Obtener productos activos del vendedor
$products_query = "SELECT * FROM products WHERE user_id = :seller_id AND status = 'active' ORDER BY created_at DESC";
$stmt = $db->prepare($products_query);
$stmt->bindParam(':seller_id', $seller_id);
$stmt->execute();
$seller_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil público de <?php echo htmlspecialchars($seller['full_name']); ?> - PUBLICATE</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .public-profile-container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 2rem; }
        .public-profile-header { display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; }
        .public-profile-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--primary-100); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--primary-700); font-weight: bold; }
        .public-profile-info h1 { margin: 0; font-size: 2rem; font-weight: 800; }
        .public-profile-info .location { color: #888; font-size: 1rem; }
        .public-profile-section { margin-bottom: 2.5rem; }
        .public-profile-section h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: 1rem; }
        .public-profile-products { display: flex; flex-wrap: wrap; gap: 1.5rem; }
        .public-profile-product { width: 210px; border: 1px solid #eee; border-radius: 0.5rem; overflow: hidden; background: #fafbfc; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .public-profile-product img { width: 100%; height: 130px; object-fit: cover; }
        .public-profile-product .title { font-weight: 600; color: var(--primary-600); margin: 0.5rem 0; text-decoration: none; display: block; }
        .public-profile-product .price { color: var(--primary-700); font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; }
        .public-profile-review { border-bottom: 1px solid #eee; padding: 1rem 0; }
        .public-profile-review:last-child { border-bottom: none; }
        .public-profile-review .reviewer { font-weight: 600; color: var(--primary-600); }
        .public-profile-review .product-title { color: #999; font-size: 0.95em; }
        .public-profile-review .stars { color: #fbbf24; }
        .public-profile-review .date { color: #aaa; font-size: 0.85em; }
    </style>
</head>
<body style="background: #f5f6fa;">
    <div class="public-profile-container">
        <div class="public-profile-header">
            <div class="public-profile-avatar"><?php echo strtoupper(substr($seller['full_name'], 0, 1)); ?></div>
            <div class="public-profile-info">
                <h1><?php echo htmlspecialchars($seller['full_name']); ?></h1>
                <div class="location">Ubicación: <?php echo htmlspecialchars($seller['location']); ?></div>
                <div class="since">Miembro desde: <?php echo date('Y', strtotime($seller['member_since'])); ?></div>
                <?php
                // Calcular calificación general
                $avg_rating = 0;
                $total_reviews = count($seller_reviews);
                if ($total_reviews > 0) {
                    $sum = 0;
                    foreach ($seller_reviews as $review) {
                        $sum += $review['rating'];
                    }
                    $avg_rating = $sum / $total_reviews;
                }
                ?>
                <div class="seller-rating" style="margin-top:0.5rem; font-size:1.1rem; color:#fbbf24; display:flex; align-items:center; gap:0.5rem;">
                    <?php
                    $rounded = round($avg_rating);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $rounded ? '★' : '☆';
                    }
                    ?>
                    <span style="color:#222; font-weight:600; margin-left:0.5rem;"><?php echo number_format($avg_rating, 2); ?></span>
                    <span style="color:#888; font-size:0.95em;">(<?php echo $total_reviews; ?> reseñas)</span>
                </div>
            </div>
        </div>
        <div class="public-profile-section">
            <h2>Reseñas recibidas</h2>
            <?php if (count($seller_reviews) > 0): ?>
                <?php foreach ($seller_reviews as $review): ?>
                    <div class="public-profile-review">
                        <div class="reviewer">
                            <?php echo htmlspecialchars($review['full_name']); ?>
                            <span class="product-title">sobre <?php echo htmlspecialchars($review['product_title']); ?></span>
                        </div>
                        <div class="stars">
                            <?php for ($i=1; $i<=5; $i++) echo $i <= $review['rating'] ? '★' : '☆'; ?>
                        </div>
                        <div style="color:#555; margin:0.5rem 0;">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                        <div class="date">
                            <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="color:#888; padding:1rem;">Este vendedor aún no tiene reseñas.</div>
            <?php endif; ?>
        </div>
        <div class="public-profile-section">
            <h2>Productos en venta</h2>
            <?php if (count($seller_products) > 0): ?>
                <div class="public-profile-products">
                    <?php foreach ($seller_products as $product): ?>
                        <?php
                        $img_stmt = $db->prepare("SELECT image_url FROM product_images WHERE product_id = :pid ORDER BY is_primary DESC, sort_order ASC LIMIT 1");
                        $img_stmt->bindParam(':pid', $product['id']);
                        $img_stmt->execute();
                        $img = $img_stmt->fetch(PDO::FETCH_ASSOC);
                        $img_url = $img ? $img['image_url'] : '../../assets/images/placeholder.jpg';
                        ?>
                        <div class="public-profile-product">
                            <a href="../../product.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                            </a>
                            <a href="../../product.php?id=<?php echo $product['id']; ?>" class="title">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </a>
                            <div class="price">
                                <?php echo number_format($product['price'], 2); ?> $
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="color:#888; padding:1rem;">Este vendedor no tiene productos activos en venta.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 