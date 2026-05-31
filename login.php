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
    <title><?php echo t('title'); ?></title>
    <style>
        body { font-family: system-ui; background: #0f172a; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 32px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 320px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #cbd5e1; border-radius: 24px; }
        button { background: #0f172a; color: white; border: none; padding: 12px; width: 100%; border-radius: 24px; cursor: pointer; }
        .error { color: #dc2626; background: #fee2e2; padding: 8px; border-radius: 16px; margin-bottom: 15px; }
        .lang-switch a { margin: 0 5px; text-decoration: none; color: #2563eb; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="lang-switch">
        <a href="?lang=ru&secret=<?php echo urlencode(ADMIN_SECRET); ?>">Рус</a> |
        <a href="?lang=ko&secret=<?php echo urlencode(ADMIN_SECRET); ?>">한국어</a>
    </div>
    <h2><?php echo t('title'); ?></h2>
    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <form method="post">
        <input type="password" name="password" placeholder="<?php echo t('password'); ?>" required>
        <button type="submit"><?php echo t('submit'); ?></button>
    </form>
</div>
</body>
</html>
