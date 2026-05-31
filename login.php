<?php
require_once 'config.php';
session_start();

// Константа ADMIN_SECRET теперь определена в config.php
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['ru', 'ko']) ? $_GET['lang'] : 'ru';
$_SESSION['lang'] = $lang;

// Проверяем секретный ключ
if (!isset($_GET['secret']) || $_GET['secret'] !== ADMIN_SECRET) {
    die('Доступ запрещен');
}

$admin_hash = '$2y$10$6Klu/B2Vm8GJO5CDBZfJPesV8K5OZZvggtsHVqlS5aK7klntpzBYu'; // хеш для "12345"

$strings = [
    'ru' => ['title' => 'Вход в админ-панель', 'password' => 'Пароль', 'submit' => 'Войти', 'error' => 'Неверный пароль'],
    'ko' => ['title' => '관리자 로그인', 'password' => '비밀번호', 'submit' => '로그인', 'error' => '잘못된 비밀번호']
];
function t($key) { global $strings, $lang; return $strings[$lang][$key] ?? $key; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (password_verify($password, $admin_hash)) {
        $_SESSION['admin_logged'] = true;
        header('Location: admin.php?lang=' . $lang);
        exit;
    } else {
        $error = t('error');
    }
}
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
        .auth { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .auth-card { width: min(420px, 100%); background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 22px; text-align: center; }
        .auth-title { font-size: 1.35rem; margin: 12px 0 10px; }
        .auth-form { display: grid; gap: 10px; margin-top: 14px; }
        .auth-label { text-align: left; font-weight: 700; color: var(--text); }
        .auth-input { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 12px; background: var(--surface); color: var(--text); }
        .error { color: color-mix(in srgb, #ef4444 85%, var(--text)); background: color-mix(in srgb, #ef4444 12%, var(--surface)); border: 1px solid color-mix(in srgb, #ef4444 18%, var(--border)); padding: 10px; border-radius: 12px; margin: 10px 0; }
        .lang-switch { display: flex; justify-content: center; gap: 10px; margin-bottom: 8px; }
        .lang-switch a { text-decoration: none; font-weight: 750; color: var(--text-muted); padding: 8px 10px; border-radius: 9999px; border: 1px solid transparent; }
        .lang-switch a:hover { border-color: var(--border); background: var(--surface-2); color: var(--text); }
    </style>
</head>
<body>
<div class="auth">
<div class="auth-card">
    <div class="lang-switch" aria-label="Language switch">
        <a href="?lang=ru&secret=<?php echo urlencode(ADMIN_SECRET); ?>">Рус</a> |
        <a href="?lang=ko&secret=<?php echo urlencode(ADMIN_SECRET); ?>">한국어</a>
    </div>
    <h1 class="auth-title"><?php echo t('title'); ?></h1>
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="post" class="auth-form">
        <label class="auth-label" for="password"><?php echo t('password'); ?></label>
        <input class="auth-input" id="password" type="password" name="password" autocomplete="current-password" required>
        <button class="btn btn-primary" type="submit"><?php echo t('submit'); ?></button>
    </form>
</div>
</div>
</body>
</html>
