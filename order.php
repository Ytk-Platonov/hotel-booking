<?php
require_once 'config/db.php';

$roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные формы
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');

    // Валидация имени (кириллица, пробелы, точки, тире)
    $namePattern = '/^[А-Яа-яЁё\s\.\-]+$/u';
    if (empty($firstName)) {
        $errors['first_name'] = 'Пожалуйста, введите имя';
    } elseif (!preg_match($namePattern, $firstName)) {
        $errors['first_name'] = 'Имя может содержать только символы кириллицы, пробелы, точки и тире';
    }

    // Валидация фамилии
    if (empty($lastName)) {
        $errors['last_name'] = 'Пожалуйста, введите фамилию';
    } elseif (!preg_match($namePattern, $lastName)) {
        $errors['last_name'] = 'Фамилия может содержать только символы кириллицы, пробелы, точки и тире';
    }

    // Валидация телефона +7(XXX)XXX-XX-XX
    $phonePattern = '/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/';
    if (empty($phone)) {
        $errors['phone'] = 'Пожалуйста, введите номер телефона';
    } elseif (!preg_match($phonePattern, $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7(XXX)XXX-XX-XX';
    }

    // Валидация email
    if (empty($email)) {
        $errors['email'] = 'Пожалуйста, введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный формат email';
    }

    // Валидация дат ДД.ММ.ГГГГ
    $datePattern = '/^\d{2}\.\d{2}\.\d{4}$/';
    if (empty($checkIn)) {
        $errors['check_in'] = 'Пожалуйста, введите дату заезда';
    } elseif (!preg_match($datePattern, $checkIn)) {
        $errors['check_in'] = 'Дата должна быть в формате ДД.ММ.ГГГГ';
    }

    if (empty($checkOut)) {
        $errors['check_out'] = 'Пожалуйста, введите дату выезда';
    } elseif (!preg_match($datePattern, $checkOut)) {
        $errors['check_out'] = 'Дата должна быть в формате ДД.ММ.ГГГГ';
    }

    // Проверка корректности дат
    if (empty($errors['check_in']) && empty($errors['check_out'])) {
        $inDate = DateTime::createFromFormat('d.m.Y', $checkIn);
        $outDate = DateTime::createFromFormat('d.m.Y', $checkOut);
        
        if (!$inDate || !$outDate) {
            $errors['check_in'] = 'Некорректная дата';
        } elseif ($inDate >= $outDate) {
            $errors['check_out'] = 'Дата выезда должна быть позже даты заезда';
        }
    }

    // Если нет ошибок - сохраняем в БД
    if (empty($errors)) {
        // Конвертируем даты в формат MySQL
        $checkInMySQL = $inDate->format('Y-m-d');
        $checkOutMySQL = $outDate->format('Y-m-d');

        $sql = "INSERT INTO bookings (room_id, first_name, last_name, phone, email, check_in, check_out, status) 
                VALUES (:room_id, :fn, :ln, :phone, :email, :ci, :co, 'new')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':room_id' => $roomId,
            ':fn' => $firstName,
            ':ln' => $lastName,
            ':phone' => $phone,
            ':email' => $email,
            ':ci' => $checkInMySQL,
            ':co' => $checkOutMySQL
        ]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование номера - Светлые Сны</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title"><a href="index.php">Светлые Сны</a></h1>
            <p class="slogan">Приезжайте как гости, уезжайте как друзья!</p>
        </div>
    </header>

    <main class="container">
        <?php if ($success): ?>
            <div class="success-message">
                <h2>Заявка успешно отправлена!</h2>
                <p>Мы свяжемся с вами в ближайшее время для подтверждения бронирования.</p>
            </div>
        <?php else: ?>
            <h2>Бронирование номера</h2>
            
            <form method="POST" action="order.php?room_id=<?= $roomId ?>" class="booking-form">
                <div class="form-group">
                    <label for="first_name">Имя</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    <?php if (isset($errors['first_name'])): ?>
                        <span class="error"><?= $errors['first_name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Фамилия</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    <?php if (isset($errors['last_name'])): ?>
                        <span class="error"><?= $errors['last_name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="+7(XXX)XXX-XX-XX"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    <?php if (isset($errors['phone'])): ?>
                        <span class="error"><?= $errors['phone'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Почта</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="check_in">Дата заезда</label>
                    <input type="text" id="check_in" name="check_in" 
                           placeholder="ДД.ММ.ГГГГ"
                           value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>">
                    <?php if (isset($errors['check_in'])): ?>
                        <span class="error"><?= $errors['check_in'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="check_out">Дата выезда</label>
                    <input type="text" id="check_out" name="check_out" 
                           placeholder="ДД.ММ.ГГГГ"
                           value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>">
                    <?php if (isset($errors['check_out'])): ?>
                        <span class="error"><?= $errors['check_out'] ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Отправить заявку</button>
            </form>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>г. Москва, ул. Ивовая, 48</p>
            <p>Время работы: Пн-Пт, с 8:00-17:00</p>
            <p>тел. 8 (800) 555-35-35</p>
            <p>Email: обращения@СветлыеСны.рф</p>
        </div>
    </footer>
</body>
</html>
