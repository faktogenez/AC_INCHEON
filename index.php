<?php
require_once 'config.php';

session_start();

$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ru', 'ko']) ? $_GET['lang'] : 'ru';
$_SESSION['lang'] = $lang;

function t($key) {
    global $lang_strings, $lang;
    return $lang_strings[$lang][$key] ?? $key;
}

// Подключение к SQLite для витрины товаров
try {
    $pdo = new PDO("sqlite:" . $PROJECT_CONFIG['db_file']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $table = $PROJECT_CONFIG['table_name'];
    $pdo->exec("CREATE TABLE IF NOT EXISTS $table (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name_ru TEXT NOT NULL,
        name_ko TEXT NOT NULL,
        price TEXT NOT NULL,
        desc_ru TEXT,
        desc_ko TEXT,
        image TEXT,
        status TEXT DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}

// Делаем переменные глобальными для доступа в модулях
global $PROJECT_CONFIG, $lang, $lang_strings, $pdo;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="light dark">
    <title><?php echo $PROJECT_CONFIG['site_name'][$lang]; ?> | <?php echo $PROJECT_CONFIG['site_subtitle'][$lang]; ?></title>
    <link rel="stylesheet" href="style.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/swiper-bundle.min.css">
    <script src="js/swiper-bundle.min.js" defer></script>

</head>
<body>
<a class="skip-link" href="#main"><?php echo $lang === 'ru' ? 'Перейти к содержимому' : '본문으로 이동'; ?></a>
<header>
    <div class="container header-inner">
        <div class="logo">
            <h1><?php echo $PROJECT_CONFIG['site_name'][$lang]; ?></h1>
        </div>
        <div class="header-controls">
            <div class="lang-switch">
                <a href="?lang=ru">Рус</a> | <a href="?lang=ko">한국어</a>
            </div>
            <div class="contact-info">
                <a href="tel:<?php echo $PROJECT_CONFIG['phone']; ?>" class="header-phone">📞 <?php echo $PROJECT_CONFIG['phone']; ?></a>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Instagram">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="TikTok">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>
                        </svg>
                    </a>
                    <a href="#" class="social-link" aria-label="Facebook">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<main class="container" id="main">
    <?php
    // Подключаем модули в нужном порядке
    include __DIR__ . '/modules/hero-slider.php';
    include __DIR__ . '/modules/products.php';
    include __DIR__ . '/modules/advantages.php';
    include __DIR__ . '/modules/faq.php';
    include __DIR__ . '/modules/map.php';
    ?>
</main>

<footer>
    <div class="container">
        <p>© <?php echo date('Y'); ?> <?php echo $PROJECT_CONFIG['site_name'][$lang]; ?> | <?php echo $PROJECT_CONFIG['address']; ?></p>
    </div>
</footer>
<script src="js/init-slider.js" defer></script>
</body>
</html>
