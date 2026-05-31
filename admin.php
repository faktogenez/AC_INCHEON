<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: login.php');
    exit;
}

// Язык
$available_langs = ['ru', 'ko'];
$lang = isset($_GET['lang']) && in_array($_GET['lang'], $available_langs) ? $_GET['lang'] : 'ru';
$_SESSION['lang'] = $lang;

$strings = [
    'ru' => [
        'title' => 'Админ-панель',
        'add_product' => 'Добавить кондиционер',
        'name_ru' => 'Название (Русский)',
        'name_ko' => 'Название (한국어)',
        'price' => 'Цена (KRW)',
        'condition' => 'Состояние',
        'condition_new' => 'Новое',
        'condition_used' => 'Б/У',
        'short_ru' => 'Краткое описание (Русский)',
        'short_ko' => '간단 설명 (한국어)',
        'area' => 'Площадь помещения',
        'efficiency' => 'Энергоэффективность',
        'inverter' => 'Инверторная технология',
        'year' => 'Год (опционально)',
        'required_fields' => 'Заполните обязательные поля',
        'image' => 'Фото',
        'submit' => 'Добавить',
        'existing' => 'Существующие товары',
        'delete' => 'Удалить',
        'logout' => 'Выход',
        'home' => 'На главную',
    ],
    'ko' => [
        'title' => '관리자 패널',
        'add_product' => '에어컨 추가',
        'name_ru' => '제품명 (러시아어)',
        'name_ko' => '제품명 (한국어)',
        'price' => '가격 (원)',
        'condition' => '상태',
        'condition_new' => '신제품',
        'condition_used' => '중고',
        'short_ru' => '간단 설명 (러시아어)',
        'short_ko' => '간단 설명 (한국어)',
        'area' => '면적',
        'efficiency' => '에너지 효율',
        'inverter' => '인버터',
        'year' => '제조 (선택)',
        'required_fields' => '필수 항목을 입력하세요',
        'image' => '이미지',
        'submit' => '추가하기',
        'existing' => '기존 제품',
        'delete' => '삭제',
        'logout' => '로그아웃',
        'home' => '홈으로',
    ]
];

function t($key) {
    global $strings, $lang;
    return $strings[$lang][$key] ?? $key;
}

$db_file = __DIR__ . '/ac_shop.db';
$pdo = new PDO("sqlite:$db_file");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Обработка добавления товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name_ru = trim((string)($_POST['name_ru'] ?? ''));
    $name_ko = trim((string)($_POST['name_ko'] ?? ''));
    $price = trim((string)($_POST['price'] ?? ''));

    $condition = (string)($_POST['condition'] ?? '');
    $short_ru = trim((string)($_POST['short_ru'] ?? ''));
    $short_ko = trim((string)($_POST['short_ko'] ?? ''));
    $area = trim((string)($_POST['area'] ?? ''));
    $efficiency = trim((string)($_POST['efficiency'] ?? ''));
    $inverter = trim((string)($_POST['inverter'] ?? ''));
    $year = trim((string)($_POST['year'] ?? ''));

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

    if ($area !== '') {
        $descRuLines[] = 'Площадь помещения: ' . $area;
        $descKoLines[] = '면적: ' . $area;
    }
    if ($efficiency !== '') {
        $descRuLines[] = 'Энергоэффективность: ' . $efficiency;
        $descKoLines[] = '에너지 효율: ' . $efficiency;
    }
    if ($inverter !== '') {
        $descRuLines[] = 'Инверторная технология: ' . $inverter;
        $descKoLines[] = '인버터: ' . $inverter;
    }
    if ($year !== '') {
        $descRuLines[] = 'Год: ' . $year;
        $descKoLines[] = '제조: ' . $year . '년';
    }

    $desc_ru = implode("\n", $descRuLines);
    $desc_ko = implode("\n", $descKoLines);
    
    $image_name = null;
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
        } else {
            $error = "Неверный формат или размер >5MB";
        }
    }
    
    if ($name_ru === '' || $name_ko === '' || $price === '' || !in_array($condition, ['new', 'used'], true) || $area === '' || $efficiency === '' || $inverter === '') {
        $error = t('required_fields');
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
    header('Location: admin.php?lang='.$lang);
    exit;
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
        .admin-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
        .admin-actions a { text-decoration: none; }
        .admin-item { background: color-mix(in srgb, var(--surface) 82%, transparent); border: 1px solid var(--border); padding: 12px; margin: 10px 0; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .admin-item a { color: color-mix(in srgb, #ef4444 85%, var(--text)); text-decoration: none; font-weight: 850; }
        .error { color: color-mix(in srgb, #ef4444 85%, var(--text)); background: color-mix(in srgb, #ef4444 12%, var(--surface)); border: 1px solid color-mix(in srgb, #ef4444 18%, var(--border)); padding: 10px; border-radius: 12px; margin: 10px 0; }
        .success { color: color-mix(in srgb, #22c55e 85%, var(--text)); background: color-mix(in srgb, #22c55e 10%, var(--surface)); border: 1px solid color-mix(in srgb, #22c55e 18%, var(--border)); padding: 10px; border-radius: 12px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="admin-actions">
        <a class="btn btn-ghost" href="?lang=ru">Рус</a>
        <a class="btn btn-ghost" href="?lang=ko">한국어</a>
        <a class="btn btn-ghost" href="index.php?lang=<?php echo $lang; ?>"><?php echo t('home'); ?></a>
        <a class="btn btn-ghost" href="logout.php"><?php echo t('logout'); ?></a>
    </div>
    
    <h1><?php echo t('title'); ?></h1>
    
    <h2><?php echo t('add_product'); ?></h2>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
    <form method="post" enctype="multipart/form-data">
        <label for="name_ru"><?php echo t('name_ru'); ?></label>
        <input id="name_ru" type="text" name="name_ru" required>
        
        <label for="name_ko"><?php echo t('name_ko'); ?></label>
        <input id="name_ko" type="text" name="name_ko" required>
        
        <label for="price"><?php echo t('price'); ?></label>
        <input id="price" type="text" name="price" placeholder="예: 350000 또는 협의" required>
        
        <label for="condition"><?php echo t('condition'); ?></label>
        <select id="condition" name="condition" required>
            <option value="new"><?php echo t('condition_new'); ?></option>
            <option value="used"><?php echo t('condition_used'); ?></option>
        </select>
        
        <label for="short_ru"><?php echo t('short_ru'); ?></label>
        <input id="short_ru" type="text" name="short_ru" placeholder="Напр.: тихий, экономичный, быстро охлаждает">

        <label for="short_ko"><?php echo t('short_ko'); ?></label>
        <input id="short_ko" type="text" name="short_ko" placeholder="예: 조용하고 전기요금 절약, 빠른 냉방">

        <label for="area"><?php echo t('area'); ?></label>
        <input id="area" type="text" name="area" placeholder="예: 18평 (59㎡)" required>

        <label for="efficiency"><?php echo t('efficiency'); ?></label>
        <input id="efficiency" type="text" name="efficiency" placeholder="예: 2등급" required>

        <label for="inverter"><?php echo t('inverter'); ?></label>
        <input id="inverter" type="text" name="inverter" placeholder="예: ✅ (듀얼 인버터)" required>

        <label for="year"><?php echo t('year'); ?></label>
        <input id="year" type="number" name="year" inputmode="numeric" min="1990" max="2100">
        
        <label for="image"><?php echo t('image'); ?></label>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
        
        <button class="btn btn-primary" type="submit" name="add_product"><?php echo t('submit'); ?></button>
    </form>
    
    <h2><?php echo t('existing'); ?></h2>
    <?php foreach ($products as $prod): ?>
        <div class="admin-item">
            <span><strong><?php echo htmlspecialchars($prod['name_ru']); ?></strong> / <?php echo htmlspecialchars($prod['name_ko']); ?><br>
            <?php echo htmlspecialchars($prod['price']); ?> 원</span>
            <a href="?delete=<?php echo $prod['id']; ?>&lang=<?php echo $lang; ?>" onclick="return confirm('Удалить?')">🗑️ <?php echo t('delete'); ?></a>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
