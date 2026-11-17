<?php
$pageTitle = 'Бронирование номера — Отель «Уют»';
include 'includes/header.php';
include 'config/db.php';

$success = false;
$errors = [];

// Получаем ID номера из GET-параметра
$roomId = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация на стороне сервера (дублирует клиентскую)
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $checkIn = trim($_POST['check_in'] ?? '');
    $checkOut = trim($_POST['check_out'] ?? '');

    // Проверка имени и фамилии (кириллица, пробелы, точки, тире)
    $namePattern = '/^[А-Яа-яЁё\s\.\-]+$/u';
    if (!preg_match($namePattern, $firstName)) {
        $errors[] = 'Имя может содержать только символы кириллицы, пробелы, точки и тире.';
    }
    if (!preg_match($namePattern, $lastName)) {
        $errors[] = 'Фамилия может содержать только символы кириллицы, пробелы, точки и тире.';
    }

    // Проверка телефона +7(XXX)XXX-XX-XX
    $phonePattern = '/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/';
    if (!preg_match($phonePattern, $phone)) {
        $errors[] = 'Телефон должен быть в формате +7(XXX)XXX-XX-XX';
    }

    // Проверка email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат E-mail.';
    }

    // Проверка дат в формате ДД.ММ.ГГГГ
    $datePattern = '/^\d{2}\.\d{2}\.\d{4}$/';
    if (!preg_match($datePattern, $checkIn) || !preg_match($datePattern, $checkOut)) {
        $errors[] = 'Даты должны быть в формате ДД.ММ.ГГГГ.';
    } else {
        // Дополнительная проверка валидности дат
        $inDate = DateTime::createFromFormat('d.m.Y', $checkIn);
        $outDate = DateTime::createFromFormat('d.m.Y', $checkOut);
        if (!$inDate || !$outDate || $inDate >= $outDate) {
            $errors[] = 'Дата заезда должна быть раньше даты выезда.';
        }
    }

    if (empty($errors)) {
        // Вставка заявки в БД с использованием подготовленных выражений
        $sql = "INSERT INTO bookings (room_id, first_name, last_name, phone, email, 
                check_in, check_out, status, created_at) 
                VALUES (:room_id, :fn, :ln, :phone, :email, :ci, :co, 'NEW', NOW())";
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
?>

<section class="booking">
    <h2>Бронирование номера</h2>

    <?php if ($success): ?>
        <div class="success-message">
            ✅ Ваша заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="booking.php?room_id=<?= $roomId ?>" 
              id="bookingForm" novalidate>
            
            <label for="first_name">Имя: *</label>
            <input type="text" id="first_name" name="first_name" required
                   pattern="[А-Яа-яЁё\s\.\-]+" 
                   title="Только кириллица, пробелы, точки и тире"
                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

            <label for="last_name">Фамилия: *</label>
            <input type="text" id="last_name" name="last_name" required
                   pattern="[А-Яа-яЁё\s\.\-]+"
                   title="Только кириллица, пробелы, точки и тире"
                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">

            <label for="phone">Телефон: *</label>
            <input type="tel" id="phone" name="phone" required
                   pattern="\+7\(\d{3}\)\d{3}-\d{2}-\d{2}"
                   placeholder="+7(999)123-45-67"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

            <label for="email">E-mail: *</label>
            <input type="email" id="email" name="email" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="check_in">Дата заезда: *</label>
            <input type="text" id="check_in" name="check_in" required
                   pattern="\d{2}\.\d{2}\.\d{4}"
                   placeholder="ДД.ММ.ГГГГ"
                   value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>">

            <label for="check_out">Дата выезда: *</label>
            <input type="text" id="check_out" name="check_out" required
                   pattern="\d{2}\.\d{2}\.\d{4}"
                   placeholder="ДД.ММ.ГГГГ"
                   value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>">

            <button type="submit" class="btn btn-primary">Отправить заявку</button>
        </form>
    <?php endif; ?>
</section>

<script src="assets/js/validation.js"></script>
<?php include 'includes/footer.php'; ?>
