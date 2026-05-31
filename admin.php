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
        'desc_ru' => 'Характеристики (Русский)',
        'desc_ko' => '사양 (한국어)',
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
        'desc_ru' => '설명 (러시아어)',
        'desc_ko' => '설명 (한국어)',
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
    $name_ru = trim($_POST['name_ru']);
    $name_ko = trim($_POST['name_ko']);
    $price = trim($_POST['price']);
    $desc_ru = trim($_POST['desc_ru']);
    $desc_ko = trim($_POST['desc_ko']);
    
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($mime, $allowed) && $_FILES['image']['size'] < 5*1024*1024) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image_name);
            // Создаём миниатюру
            makeThumbnail('uploads/' . $image_name, 'thumbnails/' . $image_name, 300);
        } else {
            $error = "Неверный формат или размер >5MB";
        }
    }
    
    if (empty($name_ru) || empty($name_ko)) {
        $error = "Название обязательно на обоих языках";
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
    $info = getimagesize($src);
    if (!$info) return false;
    $type = $info[2];
    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG: $source = imagecreatefrompng($src); break;
        case IMAGETYPE_WEBP: $source = imagecreatefromwebp($src); break;
        default: return false;
    }
    $orig_w = imagesx($source);
    $orig_h = imagesy($source);
    $height = intval($orig_h * $width / $orig_w);
    $thumb = imagecreatetruecolor($width, $height);
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $orig_w, $orig_h);
    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $dst, 85); break;
        case IMAGETYPE_PNG: imagepng($thumb, $dst, 8); break;
        case IMAGETYPE_WEBP: imagewebp($thumb, $dst, 85); break;
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
    <title><?php echo t('title'); ?></title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1, h2 { color: #0f172a; }
        label { display: block; margin: 15px 0 5px; font-weight: 600; }
        input, textarea, button { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 12px; }
        button { background: #2563eb; color: white; border: none; cursor: pointer; font-size: 1rem; }
        button:hover { background: #1e40af; }
        .admin-item { background: #f8fafc; padding: 12px; margin: 10px 0; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; }
        .admin-item a { color: #dc2626; text-decoration: none; font-weight: bold; }
        .lang-switch { margin-bottom: 20px; text-align: right; }
        .error { color: #dc2626; background: #fee2e2; padding: 10px; border-radius: 12px; }
        .success { color: #15803d; background: #dcfce7; padding: 10px; border-radius: 12px; }
        nav a { margin-right: 15px; text-decoration: none; color: #2563eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="lang-switch">
        <a href="?lang=ru">Рус</a> | <a href="?lang=ko">한국어</a>
        <nav style="margin-top: 10px;">
            <a href="index.php?lang=<?php echo $lang; ?>"><?php echo t('home'); ?></a>
            <a href="logout.php"><?php echo t('logout'); ?></a>
        </nav>
    </div>
    
    <h1><?php echo t('title'); ?></h1>
    
    <h2><?php echo t('add_product'); ?></h2>
    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
    <form method="post" enctype="multipart/form-data">
        <label><?php echo t('name_ru'); ?></label>
        <input type="text" name="name_ru" required>
        
        <label><?php echo t('name_ko'); ?></label>
        <input type="text" name="name_ko" required>
        
        <label><?php echo t('price'); ?></label>
        <input type="text" name="price" placeholder="예: 350000 또는 협의">
        
        <label><?php echo t('desc_ru'); ?></label>
        <textarea name="desc_ru" rows="3"></textarea>
        
        <label><?php echo t('desc_ko'); ?></label>
        <textarea name="desc_ko" rows="3"></textarea>
        
        <label><?php echo t('image'); ?></label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        
        <button type="submit" name="add_product"><?php echo t('submit'); ?></button>
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
