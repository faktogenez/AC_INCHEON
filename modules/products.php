<?php
// Модуль: Витрина товаров (из БД)
global $pdo, $PROJECT_CONFIG, $lang, $lang_strings;

$table = $PROJECT_CONFIG['table_name'];
$stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function parseProductSpecs(string $text, string $lang): array {
    $raw = preg_replace("/\r\n|\r/u", "\n", trim($text));
    $lines = preg_split("/\n/u", $raw);

    $specs = [];
    $rest = [];
    $condition = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if ($condition === null && preg_match('/\b(Б\\/У|БУ|пробегом|중고)\b/iu', $line)) {
            $condition = 'used';
        }
        if ($condition === null && preg_match('/\b(Новые модели|새\\s*모델|신제품)\b/iu', $line)) {
            $condition = 'new';
        }

        if (preg_match('/^Цена\\s*:\\s*/iu', $line)) {
            continue;
        }
        if (preg_match('/^🔄/u', $line)) {
            continue;
        }

        if (preg_match('/^(Площадь помещения|Площадь|면적|평수)\\s*:\\s*(.+)$/iu', $line, $m)) {
            $specs[] = [
                'key' => 'area',
                'label' => $lang === 'ru' ? 'Площадь' : '면적',
                'value' => trim($m[2]),
            ];
            continue;
        }

        if (preg_match('/^(Энергоэффективность|Энергоэфф\\.?|에너지\\s*효율)\\s*:\\s*(.+)$/iu', $line, $m)) {
            $specs[] = [
                'key' => 'efficiency',
                'label' => $lang === 'ru' ? 'Энергоэффективность' : '에너지 효율',
                'value' => trim($m[2]),
            ];
            continue;
        }

        if (preg_match('/^(Инверторная технология|Инвертор|인버터)\\s*:\\s*(.+)$/iu', $line, $m)) {
            $specs[] = [
                'key' => 'inverter',
                'label' => $lang === 'ru' ? 'Инвертор' : '인버터',
                'value' => trim($m[2]),
            ];
            continue;
        }

        if (preg_match('/^(Год|제조)\\s*:\\s*(.+)$/iu', $line, $m)) {
            $specs[] = [
                'key' => 'year',
                'label' => $lang === 'ru' ? 'Год' : '제조',
                'value' => trim($m[2]),
            ];
            continue;
        }

        $rest[] = $line;
    }

    return [
        'specs' => array_slice($specs, 0, 4),
        'rest' => implode("\n", $rest),
        'condition' => $condition,
    ];
}
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
                <?php
                $parsed = parseProductSpecs((string)($prod["desc_$lang"] ?? ''), (string)$lang);
                $specs = $parsed['specs'] ?? [];
                $descRest = (string)($parsed['rest'] ?? '');
                $condition = $parsed['condition'] ?? null;
                ?>
                <article class="product-card">
                    <div class="product-media">
                        <?php if (!empty($prod['image']) && file_exists($PROJECT_CONFIG['thumb_dir'] . $prod['image'])): ?>
                            <img src="<?php echo $PROJECT_CONFIG['thumb_dir'] . htmlspecialchars($prod['image']); ?>" alt="<?php echo htmlspecialchars($prod["name_$lang"]); ?>" class="product-img" loading="lazy">
                        <?php else: ?>
                            <div class="no-img" aria-hidden="true">📷</div>
                        <?php endif; ?>
                        <div class="product-badges">
                            <?php if ($condition === 'new'): ?>
                                <span class="badge badge-new"><?php echo $lang === 'ru' ? 'Новое' : '신제품'; ?></span>
                            <?php elseif ($condition === 'used'): ?>
                                <span class="badge badge-used"><?php echo $lang === 'ru' ? 'Б/У' : '중고'; ?></span>
                            <?php endif; ?>
                            <span class="badge badge-status <?php echo $prod['status'] == 'available' ? 'badge-in-stock' : 'badge-sold'; ?>">
                                <?php echo $prod['status'] == 'available' ? t('in_stock') : t('sold_out'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod["name_$lang"]); ?></h3>
                        <div class="product-meta">
                            <p class="price"><span class="price-label"><?php echo t('price_label'); ?>:</span> <?php echo htmlspecialchars($prod['price']); ?> 원</p>
                        </div>
                        <?php if (!empty($specs)): ?>
                            <ul class="product-specs">
                                <?php foreach ($specs as $spec): ?>
                                    <li class="product-spec spec-<?php echo htmlspecialchars($spec['key']); ?>">
                                        <span class="spec-label"><?php echo htmlspecialchars($spec['label']); ?></span>
                                        <span class="spec-value"><?php echo htmlspecialchars($spec['value']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($descRest !== ''): ?>
                            <p class="desc"><?php echo nl2br(htmlspecialchars($descRest)); ?></p>
                        <?php endif; ?>
                        <a href="tel:<?php echo $PROJECT_CONFIG['phone']; ?>" class="btn btn-primary btn-order"><?php echo t('btn_order'); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
