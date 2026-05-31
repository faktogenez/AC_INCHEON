<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

// Язык (админка всегда на русском)
$lang = 'ru';
$_SESSION['lang'] = $lang;

$strings = [
    'ru' => [
        'title' => 'Админ-панель',
        'add_product' => 'Добавить кондиционер',
        'edit_product' => 'Редактировать кондиционер',
        'name_ru' => 'Название (Русский)',
        'name_ko' => 'Название (한국어)',
        'price' => 'Цена (KRW)',
        'condition' => 'Состояние',
        'condition_new' => 'Новое',
        'condition_used' => 'Б/У',
        'short_ru' => 'Краткое описание (Русский)',
        'short_ko' => '간단 설명 (한국어)',
        'area' => 'Площадь помещения',
        'type' => 'Тип',
        'year' => 'Год (опционально)',
        'required_fields' => 'Заполните обязательные поля',
        'image' => 'Фото',
        'image_current' => 'Текущее фото',
        'image_replace' => 'Заменить фото (опционально)',
        'submit' => 'Добавить',
        'update' => 'Сохранить',
        'cancel' => 'Отмена',
        'existing' => 'Существующие товары',
        'edit' => 'Редактировать',
        'delete' => 'Удалить',
        'logout' => 'Выход',
        'home' => 'На главную',
    ],
    'ko' => [
        'title' => '관리자 패널',
        'add_product' => '에어컨 추가',
        'edit_product' => '에어컨 수정',
        'name_ru' => '제품명 (러시아어)',
        'name_ko' => '제품명 (한국어)',
        'price' => '가격 (원)',
        'condition' => '상태',
        'condition_new' => '신제품',
        'condition_used' => '중고',
        'short_ru' => '간단 설명 (러시아어)',
        'short_ko' => '간단 설명 (한국어)',
        'area' => '면적',
        'type' => '유형',
        'year' => '제조 (선택)',
        'required_fields' => '필수 항목을 입력하세요',
        'image' => '이미지',
        'image_current' => '현재 이미지',
        'image_replace' => '이미지 교체 (선택)',
        'submit' => '추가하기',
        'update' => '저장',
        'cancel' => '취소',
        'existing' => '기존 제품',
        'edit' => '수정',
        'delete' => '삭제',
        'logout' => '로그아웃',
        'home' => '홈으로',
    ]
];

function t($key) {
    global $strings, $lang;
    return $strings[$lang][$key] ?? $key;
}

function normalizeDigits(string $value): string {
    return preg_replace('/\D+/u', '', $value) ?? '';
}

function formatPrice(string $value): string {
    $digits = normalizeDigits($value);
    if ($digits === '') return '';
    return number_format((int)$digits, 0, '.', ',');
}

function extractPyeong(string $value): ?float {
    $raw = trim($value);
    if ($raw === '') return null;

    $rawNoSpaces = preg_replace('/\s+/u', '', $raw);
    if ($rawNoSpaces === null) {
        $rawNoSpaces = $raw;
    }

    if (preg_match('/㎡/u', $rawNoSpaces)) {
        return null;
    }

    if (preg_match('/^(\d+(?:[.,]\d+)?)$/u', $rawNoSpaces, $m)) {
        return (float)str_replace(',', '.', $m[1]);
    }

    if (preg_match('/^(\d+(?:[.,]\d+)?)(?:평|py|pyeong)\b/iu', $rawNoSpaces, $m)) {
        return (float)str_replace(',', '.', $m[1]);
    }

    if (preg_match('/^(\d+(?:[.,]\d+)?)(?:\+(\d+(?:[.,]\d+)?))+평/iu', $rawNoSpaces)) {
        $sum = 0.0;
        foreach (preg_split('/\+/', preg_replace('/평/iu', '', $rawNoSpaces)) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            $sum += (float)str_replace(',', '.', $part);
        }
        return $sum > 0 ? $sum : null;
    }

    return null;
}

function areaWithSqm(string $value): array {
    $area = trim($value);
    if ($area === '') {
        return ['area' => '', 'sqm' => null];
    }
    if (preg_match('/㎡/u', $area)) {
        if (preg_match('/(\d+)\s*㎡/u', $area, $m)) {
            return ['area' => $area, 'sqm' => (int)$m[1]];
        }
        return ['area' => $area, 'sqm' => null];
    }

    $pyeong = extractPyeong($area);
    if ($pyeong === null || $pyeong <= 0) {
        return ['area' => $area, 'sqm' => null];
    }

    $sqm = (int)round($pyeong * 3.305785);
    return ['area' => $area . ' (' . $sqm . '㎡)', 'sqm' => $sqm];
}

function parseDescFields(string $desc, string $lang): array {
    $raw = preg_replace("/\r\n|\r/u", "\n", trim((string)$desc));
    $lines = preg_split("/\n/u", $raw ?: '');

    $out = [
        'condition' => '',
        'short' => '',
        'area' => '',
        'type' => '',
        'year' => '',
    ];

    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '') continue;

        if ($out['condition'] === '') {
            if ($lang === 'ru' && preg_match('/^\s*Новые модели\s*$/iu', $line)) $out['condition'] = 'new';
            if ($lang === 'ru' && preg_match('/^\s*Б\/У\s*$/iu', $line)) $out['condition'] = 'used';
            if ($lang === 'ko' && preg_match('/^\s*신제품\s*$/iu', $line)) $out['condition'] = 'new';
            if ($lang === 'ko' && preg_match('/^\s*중고\s*$/iu', $line)) $out['condition'] = 'used';
        }

        if ($out['short'] === '' && preg_match('/^(Кратко|Краткое описание)\s*:\s*(.+)$/iu', $line, $m)) {
            $out['short'] = trim($m[2]);
            continue;
        }
        if ($out['short'] === '' && preg_match('/^(간단\s*설명|요약)\s*:\s*(.+)$/iu', $line, $m)) {
            $out['short'] = trim($m[2]);
            continue;
        }

        if ($out['area'] === '' && preg_match('/^(Площадь помещения|Площадь|면적|평수)\s*:\s*(.+)$/iu', $line, $m)) {
            $out['area'] = trim($m[2]);
            continue;
        }
        if ($out['type'] === '' && preg_match('/^(Тип|유형|타입)\s*:\s*(.+)$/iu', $line, $m)) {
            $out['type'] = trim($m[2]);
            continue;
        }
        if ($out['year'] === '' && preg_match('/^(Год|제조)\s*:\s*(.+)$/iu', $line, $m)) {
            $yearRaw = trim($m[2]);
            if (preg_match('/(\d{4})/u', $yearRaw, $y)) {
                $out['year'] = $y[1];
            } else {
                $out['year'] = $yearRaw;
            }
            continue;
        }
    }

    return $out;
}

$db_file = __DIR__ . '/ac_shop.db';
$pdo = new PDO("sqlite:$db_file");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function typeOptions(): array {
    return [
        'wall' => [
            'ru' => 'Настенный (벽걸이형)',
            'ko' => '벽걸이형',
            'desc_ru' => 'Самый распространенный тип, легко вписывается в интерьер. Подходит для спальни или небольшого офиса.',
        ],
        'stand' => [
            'ru' => 'Напольный (스탠드형)',
            'ko' => '스탠드형',
            'desc_ru' => 'Мощные кондиционеры для больших площадей: гостиные, магазины.',
        ],
        'duct' => [
            'ru' => 'Канальный (덕트형)',
            'ko' => '덕트형',
            'desc_ru' => 'Скрывается за потолком и распределяет воздух по воздуховодам, не нарушая дизайн.',
        ],
        'cassette' => [
            'ru' => 'Кассетный (카세트형)',
            'ko' => '카세트형',
            'desc_ru' => 'Встраивается в подвесной потолок и распределяет воздух по четырем направлениям.',
        ],
        'floor_ceiling' => [
            'ru' => 'Напольно-потолочный (천장형)',
            'ko' => '천장형',
            'desc_ru' => 'Устанавливается у пола или под потолком, дает гибкость при монтаже.',
        ],
        'column' => [
            'ru' => 'Колонный (기둥형)',
            'ko' => '기둥형',
            'desc_ru' => 'Для больших помещений с высокими потолками.',
        ],
    ];
}

// Удалить все товары
if (isset($_GET['delete_all'])) {
    $imgs = $pdo->query("SELECT image FROM products WHERE image IS NOT NULL AND image != ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($imgs as $img) {
        if ($img) {
            if (file_exists('uploads/' . $img)) unlink('uploads/' . $img);
            if (file_exists('thumbnails/' . $img)) unlink('thumbnails/' . $img);
        }
    }
    $pdo->exec("DELETE FROM products");
    header('Location: admin.php');
    exit;
}

// Обработка добавления/редактирования товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_product']) || isset($_POST['update_product']))) {
    $isUpdate = isset($_POST['update_product']);
    $productId = (int)($_POST['product_id'] ?? 0);

    $name_ru = trim((string)($_POST['name_ru'] ?? ''));
    $name_ko = trim((string)($_POST['name_ko'] ?? ''));
    $price = normalizeDigits(trim((string)($_POST['price'] ?? '')));

    $condition = (string)($_POST['condition'] ?? '');
    $short_ru = trim((string)($_POST['short_ru'] ?? ''));
    $short_ko = trim((string)($_POST['short_ko'] ?? ''));
    $area = trim((string)($_POST['area'] ?? ''));
    $typeKey = (string)($_POST['type'] ?? '');
    $year = trim((string)($_POST['year'] ?? ''));

    $areaResult = areaWithSqm($area);
    $areaNormalized = $areaResult['area'];

    $descRuLines = [];
    $descKoLines = [];

    if ($condition === 'new') {
        $descRuLines[] = 'Новые модели';
        $descKoLines[] = '신제품';
    } elseif ($condition === 'used') {
        $descRuLines[] = 'Б/У';
        $descKoLines[] = '중고';
    }

    if ($short_ru !== '') {
        $descRuLines[] = 'Кратко: ' . $short_ru;
    }
    if ($short_ko !== '') {
        $descKoLines[] = '간단 설명: ' . $short_ko;
    }

    if ($areaNormalized !== '') {
        $descRuLines[] = 'Площадь помещения: ' . $areaNormalized;
        $descKoLines[] = '면적: ' . $areaNormalized;
    }
    $types = typeOptions();
    if ($typeKey !== '' && isset($types[$typeKey])) {
        $descRuLines[] = 'Тип: ' . $types[$typeKey]['ru'];
        $descKoLines[] = '유형: ' . $types[$typeKey]['ko'];
    }
    if ($year !== '') {
        $descRuLines[] = 'Год: ' . $year;
        $descKoLines[] = '제조: ' . $year . '년';
    }

    $desc_ru = implode("\n", $descRuLines);
    $desc_ko = implode("\n", $descKoLines);
    
    $image_name = null;
    $existingImage = null;
    if ($isUpdate && $productId > 0) {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $existingImage = $stmt->fetchColumn() ?: null;
    }
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $_FILES['image']['tmp_name']) ?: null;
                finfo_close($finfo);
            }
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($_FILES['image']['tmp_name']) ?: null;
        } else {
            $imgInfo = @getimagesize($_FILES['image']['tmp_name']);
            if (is_array($imgInfo) && isset($imgInfo['mime'])) {
                $mime = $imgInfo['mime'];
            }
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if ($mime !== null && in_array($mime, $allowed, true) && $_FILES['image']['size'] < 5*1024*1024) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0775, true);
            }
            if (!is_dir('thumbnails')) {
                mkdir('thumbnails', 0775, true);
            }

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
            makeThumbnail('uploads/' . $image_name, 'thumbnails/' . $image_name, 300);

            if ($isUpdate && $existingImage) {
                if (file_exists('uploads/' . $existingImage)) unlink('uploads/' . $existingImage);
                if (file_exists('thumbnails/' . $existingImage)) unlink('thumbnails/' . $existingImage);
            }
        } else {
            $error = "Неверный формат или размер >5MB";
        }
    }
    
    if ($name_ru === '' || $name_ko === '' || $price === '') {
        $error = t('required_fields');
    } else {
        if ($isUpdate) {
            if ($productId <= 0) {
                $error = "Ошибка редактирования";
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name_ru = ?, name_ko = ?, price = ?, desc_ru = ?, desc_ko = ?, image = COALESCE(?, image) WHERE id = ?");
                if ($stmt->execute([$name_ru, $name_ko, $price, $desc_ru, $desc_ko, $image_name, $productId])) {
                    $success = "Товар обновлен";
                    header('Location: admin.php');
                    exit;
                } else {
                    $error = "Ошибка редактирования";
                }
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (name_ru, name_ko, price, desc_ru, desc_ko, image) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name_ru, $name_ko, $price, $desc_ru, $desc_ko, $image_name])) {
                $success = "Товар добавлен";
            } else {
                $error = "Ошибка добавления";
            }
        }
    }
}

// Удаление товара
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Получаем имя фото перед удалением
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img) {
        if (file_exists('uploads/' . $img)) unlink('uploads/' . $img);
        if (file_exists('thumbnails/' . $img)) unlink('thumbnails/' . $img);
    }
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin.php');
    exit;
}

// Редактирование товара
$editProduct = null;
$editFields = [
    'condition' => 'new',
    'short_ru' => '',
    'short_ko' => '',
    'area' => '',
    'type' => '',
    'year' => '',
];

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$editProduct) {
        header('Location: admin.php?lang='.$lang);
        exit;
    }

    $ruFields = parseDescFields((string)($editProduct['desc_ru'] ?? ''), 'ru');
    $koFields = parseDescFields((string)($editProduct['desc_ko'] ?? ''), 'ko');

    $editFields['condition'] = $ruFields['condition'] !== '' ? $ruFields['condition'] : ($koFields['condition'] !== '' ? $koFields['condition'] : 'new');
    $editFields['short_ru'] = (string)($ruFields['short'] ?? '');
    $editFields['short_ko'] = (string)($koFields['short'] ?? '');
    $editFields['area'] = (string)($ruFields['area'] !== '' ? $ruFields['area'] : ($koFields['area'] ?? ''));
    $editFields['type'] = (string)($ruFields['type'] !== '' ? $ruFields['type'] : ($koFields['type'] ?? ''));
    $editFields['year'] = (string)($ruFields['year'] !== '' ? $ruFields['year'] : ($koFields['year'] ?? ''));
}

// Функция создания миниатюры
function makeThumbnail($src, $dst, $width) {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
        return false;
    }

    $info = getimagesize($src);
    if (!$info) return false;
    $type = $info[2];
    switch ($type) {
        case IMAGETYPE_JPEG:
            if (!function_exists('imagecreatefromjpeg')) return false;
            $source = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            if (!function_exists('imagecreatefrompng')) return false;
            $source = imagecreatefrompng($src);
            break;
        case IMAGETYPE_WEBP:
            if (!function_exists('imagecreatefromwebp')) return false;
            $source = imagecreatefromwebp($src);
            break;
        default: return false;
    }
    if (!$source) return false;
    $orig_w = imagesx($source);
    $orig_h = imagesy($source);
    if ($orig_w <= 0 || $orig_h <= 0) {
        imagedestroy($source);
        return false;
    }
    $height = intval($orig_h * $width / $orig_w);
    $thumb = imagecreatetruecolor($width, $height);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $orig_w, $orig_h);
    switch ($type) {
        case IMAGETYPE_JPEG:
            if (!function_exists('imagejpeg')) break;
            imagejpeg($thumb, $dst, 85);
            break;
        case IMAGETYPE_PNG:
            if (!function_exists('imagepng')) break;
            imagepng($thumb, $dst, 8);
            break;
        case IMAGETYPE_WEBP:
            if (!function_exists('imagewebp')) break;
            imagewebp($thumb, $dst, 85);
            break;
    }
    imagedestroy($source);
    imagedestroy($thumb);
    return true;
}

$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title><?php echo t('title'); ?></title>
    <link rel="stylesheet" href="style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 20px; font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: var(--bg); color: var(--text); }
        .admin-container { max-width: 980px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 22px; }
        h1 { font-size: 1.4rem; margin: 0 0 12px; }
        h2 { font-size: 1.15rem; margin: 18px 0 10px; }
        label { display: block; margin: 12px 0 6px; font-weight: 750; }
        input, textarea, select { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: var(--surface); color: var(--text); }
        textarea { min-height: 110px; resize: vertical; }
        .hint { margin-top: 6px; color: var(--text-muted); font-size: 0.92rem; }
        .admin-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
        .admin-actions a { text-decoration: none; }
        .admin-actions .btn { min-width: 44px; display: inline-flex; justify-content: center; align-items: center; }
        .admin-item { background: color-mix(in srgb, var(--surface) 82%, transparent); border: 1px solid var(--border); padding: 12px; margin: 10px 0; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .admin-item .admin-links { display: flex; gap: 12px; align-items: center; }
        .admin-item .admin-link { text-decoration: none; font-weight: 850; font-size: 1.1rem; line-height: 1; padding: 8px; border-radius: 10px; }
        .admin-item .admin-link:hover { background: color-mix(in srgb, var(--surface-2) 60%, transparent); }
        .admin-item .admin-link-delete { color: color-mix(in srgb, #ef4444 85%, var(--text)); }
        .admin-section-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .admin-section-head h2 { margin: 18px 0 10px; }
        .admin-icon-btn { min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--border); background: color-mix(in srgb, var(--surface) 70%, transparent); color: var(--text); text-decoration: none; }
        .admin-icon-btn:hover { background: color-mix(in srgb, var(--surface-2) 70%, transparent); }
        .admin-icon-danger { color: color-mix(in srgb, #ef4444 85%, var(--text)); }
        .error { color: color-mix(in srgb, #ef4444 85%, var(--text)); background: color-mix(in srgb, #ef4444 12%, var(--surface)); border: 1px solid color-mix(in srgb, #ef4444 18%, var(--border)); padding: 10px; border-radius: 12px; margin: 10px 0; }
        .success { color: color-mix(in srgb, #22c55e 85%, var(--text)); background: color-mix(in srgb, #22c55e 10%, var(--surface)); border: 1px solid color-mix(in srgb, #22c55e 18%, var(--border)); padding: 10px; border-radius: 12px; margin: 10px 0; }
        .admin-item-main { min-width: 0; }
        .admin-item-title { display: block; font-weight: 850; }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .admin-container { padding: 16px; }
            .admin-actions { justify-content: space-between; }
            .admin-actions .btn { padding: 10px 12px; }
            .admin-item { flex-direction: column; align-items: stretch; gap: 10px; }
            .admin-item .admin-links { justify-content: flex-end; }
            h1 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="admin-actions">
        <a class="btn btn-ghost" href="index.php?lang=ru" aria-label="На главную" title="На главную">🏠</a>
        <a class="btn btn-ghost" href="logout.php" aria-label="Выход" title="Выход">🚪</a>
    </div>
    
    <h1><?php echo t('title'); ?></h1>
    
    <h2><?php echo $editProduct ? t('edit_product') : t('add_product'); ?></h2>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
    <form method="post" enctype="multipart/form-data">
        <?php if ($editProduct): ?>
            <input type="hidden" name="product_id" value="<?php echo (int)$editProduct['id']; ?>">
        <?php endif; ?>
        <label for="name_ru"><?php echo t('name_ru'); ?></label>
        <input id="name_ru" type="text" name="name_ru" value="<?php echo htmlspecialchars((string)($editProduct['name_ru'] ?? '')); ?>" required>
        
        <label for="name_ko"><?php echo t('name_ko'); ?></label>
        <input id="name_ko" type="text" name="name_ko" value="<?php echo htmlspecialchars((string)($editProduct['name_ko'] ?? '')); ?>" required>
        
        <label for="price"><?php echo t('price'); ?></label>
        <input id="price" type="text" name="price" placeholder="예: 3,500,000" value="<?php echo htmlspecialchars((string)formatPrice((string)($editProduct['price'] ?? ''))); ?>" required>
        
        <label for="condition"><?php echo t('condition'); ?></label>
        <select id="condition" name="condition" required>
            <option value="new" <?php echo ($editProduct && $editFields['condition'] === 'new') ? 'selected' : ''; ?>><?php echo t('condition_new'); ?></option>
            <option value="used" <?php echo ($editProduct && $editFields['condition'] === 'used') ? 'selected' : ''; ?>><?php echo t('condition_used'); ?></option>
        </select>
        
        <label for="short_ru"><?php echo t('short_ru'); ?></label>
        <input id="short_ru" type="text" name="short_ru" placeholder="Напр.: тихий, экономичный, быстро охлаждает" value="<?php echo htmlspecialchars((string)($editProduct ? $editFields['short_ru'] : '')); ?>">

        <label for="short_ko"><?php echo t('short_ko'); ?></label>
        <input id="short_ko" type="text" name="short_ko" placeholder="예: 조용하고 전기요금 절약, 빠른 냉방" value="<?php echo htmlspecialchars((string)($editProduct ? $editFields['short_ko'] : '')); ?>">

        <label for="area"><?php echo t('area'); ?></label>
        <input id="area" type="text" name="area" placeholder="예: 18평 (59㎡)" value="<?php echo htmlspecialchars((string)($editProduct ? $editFields['area'] : '')); ?>">
        <div class="hint" id="areaHint"></div>

        <label for="type"><?php echo t('type'); ?></label>
        <select id="type" name="type">
            <option value=""></option>
            <?php foreach (typeOptions() as $k => $opt): ?>
                <?php
                $selected = '';
                if ($editProduct && $editFields['type'] !== '') {
                    if ($editFields['type'] === $opt['ru'] || $editFields['type'] === $opt['ko']) {
                        $selected = 'selected';
                    }
                }
                ?>
                <option value="<?php echo htmlspecialchars((string)$k); ?>" <?php echo $selected; ?> data-desc="<?php echo htmlspecialchars((string)$opt['desc_ru']); ?>">
                    <?php echo htmlspecialchars((string)$opt['ru']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="hint" id="typeHint"></div>

        <label for="year"><?php echo t('year'); ?></label>
        <input id="year" type="number" name="year" inputmode="numeric" min="1990" max="2100" value="<?php echo htmlspecialchars((string)($editProduct ? $editFields['year'] : '')); ?>">
        
        <?php if ($editProduct && !empty($editProduct['image'])): ?>
            <label><?php echo t('image_current'); ?></label>
            <div class="hint"><?php echo htmlspecialchars((string)$editProduct['image']); ?></div>
            <label for="image"><?php echo t('image_replace'); ?></label>
        <?php else: ?>
            <label for="image"><?php echo t('image'); ?></label>
        <?php endif; ?>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
        
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:12px;">
            <?php if ($editProduct): ?>
                <button class="btn btn-primary" type="submit" name="update_product"><?php echo t('update'); ?></button>
                <a class="btn btn-ghost" href="admin.php?lang=<?php echo $lang; ?>"><?php echo t('cancel'); ?></a>
            <?php else: ?>
                <button class="btn btn-primary" type="submit" name="add_product"><?php echo t('submit'); ?></button>
            <?php endif; ?>
        </div>
    </form>
    
    <div class="admin-section-head">
        <h2><?php echo t('existing'); ?></h2>
        <a class="admin-icon-btn admin-icon-danger" href="?delete_all=1"
           onclick="return confirm('Удалить ВСЕ товары?')"
           aria-label="Удалить все" title="Удалить все">🗑️</a>
    </div>
    <?php foreach ($products as $prod): ?>
        <div class="admin-item">
            <?php
            $nameRu = (string)($prod['name_ru'] ?? '');
            $nameRuShort = $nameRu;
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($nameRuShort, 'UTF-8') > 20) {
                    $nameRuShort = mb_substr($nameRuShort, 0, 20, 'UTF-8') . '…';
                }
            } else {
                if (strlen($nameRuShort) > 20) {
                    $nameRuShort = substr($nameRuShort, 0, 20) . '...';
                }
            }
            ?>
            <div class="admin-item-main">
                <span class="admin-item-title" title="<?php echo htmlspecialchars($nameRu); ?>"><?php echo htmlspecialchars($nameRuShort); ?></span>
                <span><?php echo htmlspecialchars(formatPrice((string)$prod['price'])); ?> 원</span>
            </div>
            <div class="admin-links">
                <a class="admin-link" href="?edit=<?php echo $prod['id']; ?>" aria-label="Редактировать" title="Редактировать">✏️</a>
                <a class="admin-link admin-link-delete" href="?delete=<?php echo $prod['id']; ?>" onclick="return confirm('Удалить?')" aria-label="Удалить" title="Удалить">🗑️</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<script>
(() => {
  const areaInput = document.getElementById('area');
  const hint = document.getElementById('areaHint');
  if (!areaInput || !hint) return;

  const calcSqm = (v) => {
    if (!v) return null;
    if (v.includes('㎡')) return null;
    const raw = v.replace(/\s+/g, '');
    const plusMatch = raw.match(/^(\d+(?:[.,]\d+)?)(?:\+(\d+(?:[.,]\d+)?))+평/i);
    let pyeong = null;
    if (/^\d+(?:[.,]\d+)?$/.test(raw)) pyeong = parseFloat(raw.replace(',', '.'));
    else {
      const m1 = raw.match(/^(\d+(?:[.,]\d+)?)(?:평|py|pyeong)\b/i);
      if (m1) pyeong = parseFloat(m1[1].replace(',', '.'));
      else if (plusMatch) {
        pyeong = raw.replace(/평/i, '').split('+').reduce((s, x) => s + (parseFloat(x.replace(',', '.')) || 0), 0);
      }
    }
    if (!pyeong || pyeong <= 0) return null;
    return Math.round(pyeong * 3.305785);
  };

  const render = () => {
    const sqm = calcSqm(areaInput.value);
    hint.textContent = sqm ? `≈ ${sqm}㎡` : '';
  };

  areaInput.addEventListener('input', render);
  render();
})();
</script>
<script>
(() => {
  const type = document.getElementById('type');
  const hint = document.getElementById('typeHint');
  if (!type || !hint) return;
  const render = () => {
    const opt = type.options[type.selectedIndex];
    const text = opt ? (opt.getAttribute('data-desc') || '') : '';
    hint.textContent = text;
  };
  type.addEventListener('change', render);
  render();
})();
</script>
<script>
(() => {
  const price = document.getElementById('price');
  if (!price) return;
  const format = (v) => {
    const digits = (v || '').replace(/\D+/g, '');
    if (!digits) return '';
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  };
  const onInput = () => {
    const start = price.selectionStart || 0;
    const before = price.value;
    const formatted = format(before);
    price.value = formatted;
    const delta = formatted.length - before.length;
    const next = Math.max(0, start + delta);
    try { price.setSelectionRange(next, next); } catch (e) {}
  };
  price.addEventListener('input', onInput);
})();
</script>
</body>
</html>
