<?php
require_once 'config/db.php';

$roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);
$success = false;
$errors = [];
$bookedPeriods = [];

// Получаем занятые периоды для этого номера (одобренные брони)
if ($roomId) {
    $stmt = $pdo->prepare("
        SELECT check_in, check_out FROM bookings 
        WHERE room_id = :room_id AND status = 'approved'
        ORDER BY check_in
    ");
    $stmt->execute([':room_id' => $roomId]);
    $bookedPeriods = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');

    // Валидация имени
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

    // Валидация телефона
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

    // Валидация дат
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    $inDate = null;
    $outDate = null;

    if (empty($checkIn)) {
        $errors['check_in'] = 'Пожалуйста, введите дату заезда';
    } else {
        $inDate = DateTime::createFromFormat('Y-m-d', $checkIn);
        if (!$inDate) {
            $errors['check_in'] = 'Некорректная дата заезда';
        } elseif ($inDate < $today) {
            $errors['check_in'] = 'Дата заезда не может быть раньше текущей даты';
        }
    }

    if (empty($checkOut)) {
        $errors['check_out'] = 'Пожалуйста, введите дату выезда';
    } else {
        $outDate = DateTime::createFromFormat('Y-m-d', $checkOut);
        if (!$outDate) {
            $errors['check_out'] = 'Некорректная дата выезда';
        } elseif ($inDate && $outDate <= $inDate) {
            $errors['check_out'] = 'Дата выезда должна быть позже даты заезда';
        }
    }

    // Проверка пересечения с занятыми периодами (одобренные брони)
    if (empty($errors['check_in']) && empty($errors['check_out']) && $roomId && $inDate && $outDate) {
        foreach ($bookedPeriods as $booked) {
            $bookedIn = new DateTime($booked['check_in']);
            $bookedOut = new DateTime($booked['check_out']);
            
            // Пересечение: заезд <= конец брони И выезд >= начало брони
            if ($inDate < $bookedOut && $outDate > $bookedIn) {
                $errors['check_in'] = 'Выбранный период уже забронирован и одобрен администратором. Пожалуйста, выберите другие даты.';
                $errors['check_out'] = 'Выбранный период уже забронирован и одобрен администратором. Пожалуйста, выберите другие даты.';
                break;
            }
        }
    }

    // Сохранение в БД
    if (empty($errors)) {
        $sql = "INSERT INTO bookings (room_id, first_name, last_name, phone, email, check_in, check_out, status) 
                VALUES (:room_id, :fn, :ln, :phone, :email, :ci, :co, 'new')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':room_id' => $roomId,
            ':fn' => $firstName,
            ':ln' => $lastName,
            ':phone' => $phone,
            ':email' => $email,
            ':ci' => $checkIn,
            ':co' => $checkOut
        ]);

        $success = true;
    }
}

// Формируем JSON с занятыми периодами для клиента
$bookedPeriodsJson = json_encode(array_map(function($p) {
    return ['start' => $p['check_in'], 'end' => $p['check_out']];
}, $bookedPeriods));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование номера - Светлые Сны</title>
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
    <?php if ($success): ?>
        <div class="alert alert-success">
            <h2>Заявка успешно отправлена!</h2>
            <p>Мы свяжемся с вами в ближайшее время для подтверждения бронирования.</p>
            <a href="index.php" class="btn btn-primary">Вернуться в каталог</a>
        </div>
    <?php else: ?>
        <h2 class="mb-4">Бронирование номера</h2>
        
        <?php if (!empty($errors) && !isset($errors['check_in'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="order.php?room_id=<?= $roomId ?>" class="booking-form" id="bookingForm" novalidate>
            <div class="mb-3">
                <label for="first_name" class="form-label">Имя *</label>
                <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                       id="first_name" name="first_name" 
                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                <?php if (isset($errors['first_name'])): ?>
                    <div class="invalid-feedback"><?= $errors['first_name'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="last_name" class="form-label">Фамилия *</label>
                <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                       id="last_name" name="last_name" 
                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                <?php if (isset($errors['last_name'])): ?>
                    <div class="invalid-feedback"><?= $errors['last_name'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Телефон *</label>
                <input type="tel" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                       id="validationCustomPhone" name="phone" 
                       placeholder="+7(XXX)XXX-XX-XX"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?= $errors['phone'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Почта *</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                       id="email" name="email" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= $errors['email'] ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="check_in" class="form-label">Дата заезда *</label>
                <input type="date" class="form-control <?= isset($errors['check_in']) ? 'is-invalid' : '' ?>" 
                       id="check_in" name="check_in" 
                       value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>" required>
                <?php if (isset($errors['check_in'])): ?>
                    <div class="invalid-feedback"><?= $errors['check_in'] ?></div>
                <?php endif; ?>
                <div class="form-text">Дата заезда не может быть раньше текущей даты</div>
            </div>

            <div class="mb-3">
                <label for="check_out" class="form-label">Дата выезда *</label>
                <input type="date" class="form-control <?= isset($errors['check_out']) ? 'is-invalid' : '' ?>" 
                       id="check_out" name="check_out" 
                       value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>" required>
                <?php if (isset($errors['check_out'])): ?>
                    <div class="invalid-feedback"><?= $errors['check_out'] ?></div>
                <?php endif; ?>
                <div class="form-text">Дата выезда должна быть позже даты заезда</div>
            </div>

            <?php if (!empty($bookedPeriods)): ?>
                <div class="alert alert-warning">
                    <strong>Внимание!</strong> Следующие периоды уже забронированы и недоступны для выбора:
                    <ul class="mb-0 mt-2">
                        <?php foreach ($bookedPeriods as $bp): ?>
                            <li>с <?= date('d.m.Y', strtotime($bp['check_in'])) ?> по <?= date('d.m.Y', strtotime($bp['check_out'])) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Отправить заявку</button>
        </form>
    <?php endif; ?>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/jquery.inputmask.min.js"></script>
<script src="assets/js/index.js"></script>
<script>
    // Данные о занятых периодах
    const bookedPeriods = <?= $bookedPeriodsJson ?>;
    
    // Установка min даты для check_in (сегодня)
    const today = new Date().toISOString().split('T')[0];
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    
    if (checkInInput) {
        checkInInput.setAttribute('min', today);
    }
    
    // При изменении даты заезда — обновляем min для даты выезда
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            if (this.value) {
                const nextDay = new Date(this.value);
                nextDay.setDate(nextDay.getDate() + 1);
                const nextDayStr = nextDay.toISOString().split('T')[0];
                checkOutInput.setAttribute('min', nextDayStr);
                
                if (checkOutInput.value && checkOutInput.value <= this.value) {
                    checkOutInput.value = nextDayStr;
                }
            }
        });
    }
    
    // Клиентская проверка пересечения с занятыми периодами
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        const checkIn = checkInInput?.value;
        const checkOut = checkOutInput?.value;
        
        if (checkIn && checkOut && bookedPeriods.length > 0) {
            const inDate = new Date(checkIn);
            const outDate = new Date(checkOut);
            
            for (const period of bookedPeriods) {
                const bookedIn = new Date(period.start);
                const bookedOut = new Date(period.end);
                
                if (inDate < bookedOut && outDate > bookedIn) {
                    e.preventDefault();
                    alert('Выбранный период уже забронирован и одобрен администратором. Пожалуйста, выберите другие даты.');
                    return false;
                }
            }
        }
    });
</script>

</body>
</html>
