<?php
// Модуль: Витрина товаров (из БД)
global $pdo, $PROJECT_CONFIG, $lang, $lang_strings;

$table = $PROJECT_CONFIG['table_name'];
$stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Функция t уже объявлена в index.php, не нужно её повторять
// Используем её как есть
?>
<div class="products-section">
    <h2 class="section-title"><?php echo $lang == 'ru' ? 'Кондиционеры в наличии' : '재고 있는 에어컨'; ?></h2>
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <p><?php echo t('no_products'); ?></p>
        <?php else: ?>
            <?php foreach ($products as $prod): ?>
                <div class="product-card">
                    <?php if (!empty($prod['image']) && file_exists($PROJECT_CONFIG['thumb_dir'] . $prod['image'])): ?>
                        <img src="<?php echo $PROJECT_CONFIG['thumb_dir'] . htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod["name_$lang"]); ?>" class="product-img" loading="lazy">
                    <?php else: ?>
                        <div class="no-img">📷</div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($prod["name_$lang"]); ?></h3>
                        <p class="price"><?php echo t('price_label'); ?>: <?php echo htmlspecialchars($prod['price']); ?> 원</p>
                        <p class="desc"><?php echo nl2br(htmlspecialchars($prod["desc_$lang"])); ?></p>
                        <span class="status <?php echo $prod['status'] == 'available' ? 'in-stock' : 'sold'; ?>">
                            <?php echo $prod['status'] == 'available' ? t('in_stock') : t('sold_out'); ?>
                        </span>
                        <a href="tel:<?php echo $PROJECT_CONFIG['phone']; ?>" class="btn-order"><?php echo t('btn_order'); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
