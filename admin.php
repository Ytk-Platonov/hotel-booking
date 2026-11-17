<?php
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Обработка действий
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if ($bookingId) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);
            $message = 'Заявка одобрена';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);
            $message = 'Заявка удалена';
            $messageType = 'info';
        }
    }
}

// Получаем все заявки
$stmt = $pdo->query("SELECT b.*, rc.name AS category_name 
                     FROM bookings b 
                     JOIN rooms r ON b.room_id = r.id 
                     JOIN room_categories rc ON r.category_id = rc.id 
                     ORDER BY b.created_at DESC");
$bookings = $stmt->fetchAll();

$statusLabels = [
    'new' => 'Новая',
    'approved' => 'Одобрена',
    'rejected' => 'Отклонена'
];

$statusClasses = [
    'new' => 'warning',
    'approved' => 'success',
    'rejected' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Светлые Сны</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header bg-dark text-white py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="site-title mb-0">
            <a href="index.php" class="text-white text-decoration-none">Светлые Сны</a>
        </h1>
        <div class="admin-info">
            <span class="me-3">Администратор: <?= htmlspecialchars($_SESSION['admin_login']) ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Выйти</a>
        </div>
        <p class="slogan mb-0 fst-italic">Приезжайте как гости, уезжайте как друзья!</p>
    </div>
</header>

<main class="container my-4">
    <h2 class="mb-4">Панель администратора</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">Заявок на бронирование пока нет.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>№</th>
                        <th>Категория</th>
                        <th>Фамилия</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата заезда</th>
                        <th>Дата выезда</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= $booking['id'] ?></td>
                            <td><?= htmlspecialchars($booking['category_name']) ?></td>
                            <td><?= htmlspecialchars($booking['last_name']) ?></td>
                            <td><?= htmlspecialchars($booking['first_name']) ?></td>
                            <td><?= htmlspecialchars($booking['phone']) ?></td>
                            <td><?= htmlspecialchars($booking['email']) ?></td>
                            <td><?= date('d.m.Y', strtotime($booking['check_in'])) ?></td>
                            <td><?= date('d.m.Y', strtotime($booking['check_out'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $statusClasses[$booking['status']] ?>">
                                    <?= $statusLabels[$booking['status']] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'new'): ?>
                                    <form method="POST" action="admin.php" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Одобрить</button>
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Вы уверены, что хотите удалить заявку?')">Удалить</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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

</body>
</html>
