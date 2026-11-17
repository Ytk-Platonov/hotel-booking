<?php
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_login'] = $user['login'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Светлые Сны</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header bg-dark text-white py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="site-title mb-0">
            <a href="index.php" class="text-white text-decoration-none">Светлые Сны</a>
        </h1>
        <p class="slogan mb-0 fst-italic">Приезжайте как гости, уезжайте как друзья!</p>
    </div>
</header>

<main class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Вход в панель администратора</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="login" class="form-label">Логин</label>
                    <input type="text" class="form-control" id="login" name="login" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary">Войти</button>
                <a href="index.php" class="btn btn-secondary ms-2">На главную</a>
            </form>
        </div>
    </div>
</main>

<footer class="site-footer bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-3"><p>г. Москва, ул. Ивовая, 48</p></div>
            <div class="col-md-3"><p>Время работы: Пн-Пт, с 8:00-17:00</p></div>
            <div class="col-md-3"><p>тел. <a href="tel:88005553535" class="text-white">8 (800) 555-35-35</a></p></div>
            <div class="col-md-3"><p>Email: <a href="mailto:обращения@СветлыеСны.рф" class="text-white">обращения@СветлыеСны.рф</a></p></div>
        </div>
    </div>
</footer>

</body>
</html>
