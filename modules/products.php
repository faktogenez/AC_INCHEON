<?php
// Модуль: Витрина товаров (из БД)
global $pdo, $PROJECT_CONFIG, $lang, $lang_strings;

$table = $PROJECT_CONFIG['table_name'];
$stmt = $pdo->query("SELECT * FROM $table ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function normalizeDigits(string $value): string {
    return preg_replace('/\D+/u', '', $value) ?? '';
}

function formatPrice(string $value): string {
    $digits = normalizeDigits($value);
    if ($digits === '') return '';
    return number_format((int)$digits, 0, '.', ',');
}

function pyeongToSqm(string $value): ?string {
    if (preg_match('/㎡/u', $value)) {
        return null;
    }

    $raw = str_replace([' ', "\t"], '', $value);
    $pyeong = null;

    if (preg_match('/^(\d+(?:[.,]\d+)?)$/u', $raw, $m)) {
        $pyeong = (float)str_replace(',', '.', $m[1]);
    } elseif (preg_match('/^(\d+(?:[.,]\d+)?)(?:평|py|pyeong)\b/iu', $raw, $m)) {
        $pyeong = (float)str_replace(',', '.', $m[1]);
    } elseif (preg_match('/^(\d+(?:[.,]\d+)?)(?:\+(\d+(?:[.,]\d+)?))+평/iu', $raw)) {
        $sum = 0.0;
        foreach (preg_split('/\+/', preg_replace('/평/iu', '', $raw)) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $sum += (float)str_replace(',', '.', $part);
        }
        if ($sum > 0) {
            $pyeong = $sum;
        }
    }

    if ($pyeong === null || $pyeong <= 0) {
        return null;
    }

    $sqm = (int)round($pyeong * 3.305785);
    return $sqm . '㎡';
}

function parseProductSpecs(string $text, string $lang): array {
    $raw = preg_replace("/\r\n|\r/u", "\n", trim($text));
    $lines = preg_split("/\n/u", $raw);

    $specs = [];
    $rest = [];
    $condition = null;
    $short = null;
    $type = null;

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

        if (preg_match('/^\s*(Новые модели|새\\s*모델|신제품)\s*$/iu', $line)) {
            continue;
        }
        if (preg_match('/^\s*(Б\\/У|БУ|중고)\s*$/iu', $line)) {
            continue;
        }

        if ($short === null && preg_match('/^(Кратко|Краткое описание)\s*:\s*(.+)$/iu', $line, $m)) {
            $short = trim($m[2]);
            continue;
        }
        if ($short === null && preg_match('/^(간단\s*설명|요약)\s*:\s*(.+)$/iu', $line, $m)) {
            $short = trim($m[2]);
            continue;
        }

        if (preg_match('/^Цена\\s*:\\s*/iu', $line)) {
            continue;
        }
        if (preg_match('/^🔄/u', $line)) {
            continue;
        }

        if (preg_match('/^(Площадь помещения|Площадь|면적|평수)\\s*:\\s*(.+)$/iu', $line, $m)) {
            $areaValue = trim($m[2]);
            $sqm = pyeongToSqm($areaValue);
            if ($sqm !== null) {
                $areaValue .= ' (' . $sqm . ')';
            }
            $specs[] = [
                'key' => 'area',
                'label' => $lang === 'ru' ? 'Площадь' : '면적',
                'value' => $areaValue,
            ];
            continue;
        }

        if ($type === null && preg_match('/^(Тип|유형|타입)\\s*:\\s*(.+)$/iu', $line, $m)) {
            $type = trim($m[2]);
            $specs[] = [
                'key' => 'type',
                'label' => $lang === 'ru' ? 'Тип' : '유형',
                'value' => $type,
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
        'short' => $short,
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
                $short = (string)($parsed['short'] ?? '');
                $descRest = (string)($parsed['rest'] ?? '');
                $condition = $parsed['condition'] ?? null;
                $priceFormatted = formatPrice((string)($prod['price'] ?? ''));
                ?>
                <article class="product-card">
                    <div class="product-media">
                        <?php
                        $imgSrc = null;
                        if (!empty($prod['image'])) {
                            $thumbPath = $PROJECT_CONFIG['thumb_dir'] . $prod['image'];
                            $uploadPath = $PROJECT_CONFIG['upload_dir'] . $prod['image'];
                            if (file_exists($thumbPath)) {
                                $imgSrc = $PROJECT_CONFIG['thumb_dir'] . $prod['image'];
                            } elseif (file_exists($uploadPath)) {
                                $imgSrc = $PROJECT_CONFIG['upload_dir'] . $prod['image'];
                            }
                        }
                        ?>
                        <?php if ($imgSrc !== null): ?>
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($prod["name_$lang"]); ?>" class="product-img" loading="lazy">
                        <?php else: ?>
                            <div class="no-img" aria-hidden="true">📷</div>
                        <?php endif; ?>
                        <div class="product-badges">
                            <?php if ($condition === 'new'): ?>
                                <span class="badge badge-new"><?php echo $lang === 'ru' ? 'Новое' : '신제품'; ?></span>
                            <?php elseif ($condition === 'used'): ?>
                                <span class="badge badge-used"><?php echo $lang === 'ru' ? 'Б/У' : '중고'; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($prod["name_$lang"]); ?></h3>
                        <div class="product-meta">
                            <p class="price"><span class="price-label"><?php echo t('price_label'); ?>:</span> <?php echo htmlspecialchars($priceFormatted !== '' ? $priceFormatted : (string)$prod['price']); ?> 원</p>
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
                        <?php if ($short !== ''): ?>
                            <p class="product-short"><?php echo htmlspecialchars($short); ?></p>
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
