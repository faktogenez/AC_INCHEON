<?php
// Модуль: Витрина товаров (из БД)
global $pdo, $PROJECT_CONFIG, $lang, $lang_strings;

$table = $PROJECT_CONFIG['table_name'];
$stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Функция t уже объявлена в index.php, не нужно её повторять
// Используем её как есть
?>
<section class="section products-section" aria-labelledby="products-title">
    <div class="section-head">
        <h2 class="section-title" id="products-title"><?php echo $lang == 'ru' ? 'Кондиционеры в наличии' : '재고 있는 에어컨'; ?></h2>
    </div>
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <p class="empty-state"><?php echo t('no_products'); ?></p>
        <?php else: ?>
            <?php foreach ($products as $prod): ?>
                <article class="product-card">
                    <?php if (!empty($prod['image']) && file_exists($PROJECT_CONFIG['thumb_dir'] . $prod['image'])): ?>
                        <img src="<?php echo $PROJECT_CONFIG['thumb_dir'] . htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod["name_$lang"]); ?>" class="product-img" loading="lazy">
                    <?php else: ?>
                        <div class="no-img" aria-hidden="true">📷</div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod["name_$lang"]); ?></h3>
                        <div class="product-meta">
                            <span class="status <?php echo $prod['status'] == 'available' ? 'in-stock' : 'sold'; ?>">
                                <?php echo $prod['status'] == 'available' ? t('in_stock') : t('sold_out'); ?>
                            </span>
                            <p class="price"><span class="price-label"><?php echo t('price_label'); ?>:</span> <?php echo htmlspecialchars($prod['price']); ?> 원</p>
                        </div>
                        <p class="desc"><?php echo nl2br(htmlspecialchars($prod["desc_$lang"])); ?></p>
                        <a href="tel:<?php echo $PROJECT_CONFIG['phone']; ?>" class="btn btn-primary btn-order"><?php echo t('btn_order'); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
