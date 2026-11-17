<?php
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);

    if ($bookingId) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected' WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);
        }
    }
}

// Получаем все заявки
$stmt = $pdo->query("SELECT b.*, r.category_id, rc.name AS category_name 
                     FROM bookings b 
                     JOIN rooms r ON b.room_id = r.id 
                     JOIN room_categories rc ON r.category_id = rc.id 
                     ORDER BY b.created_at DESC");
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Светлые Сны</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title"><a href="index.php">Светлые Сны</a></h1>
            <p class="slogan">Приезжайте как гости, уезжайте как друзья!</p>
            <div class="admin-info">
                <span>Администратор: <?= htmlspecialchars($_SESSION['admin_login']) ?></span>
                <a href="logout.php" class="btn btn-secondary">Выйти</a>
            </div>
        </div>
    </header>

    <main class="container">
        <h2>Панель администратора</h2>

        <?php if (empty($bookings)): ?>
            <p>Заявок на бронирование пока нет.</p>
        <?php else: ?>
            <div class="bookings-list">
                <?php foreach ($bookings as $booking): ?>
                    <article class="booking-card <?= $booking['status'] ?>">
                        <div class="booking-info">
                            <p><strong>Категория:</strong> <?= htmlspecialchars($booking['category_name']) ?></p>
                            <p><strong>Фамилия:</strong> <?= htmlspecialchars($booking['last_name']) ?></p>
                            <p><strong>Имя:</strong> <?= htmlspecialchars($booking['first_name']) ?></p>
                            <p><strong>Телефон:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($booking['email']) ?></p>
                            <p><strong>Дата заезда:</strong> <?= date('d.m.Y', strtotime($booking['check_in'])) ?></p>
                            <p><strong>Дата выезда:</strong> <?= date('d.m.Y', strtotime($booking['check_out'])) ?></p>
                            <p><strong>Статус:</strong> 
                                <?php
                                $statusLabels = [
                                    'new' => 'Новая',
                                    'approved' => 'Одобрена',
                                    'rejected' => 'Отклонена'
                                ];
                                echo $statusLabels[$booking['status']] ?? $booking['status'];
                                ?>
                            </p>
                        </div>
                        
                        <?php if ($booking['status'] === 'new'): ?>
                            <div class="booking-actions">
                                <form method="POST" action="admin.php" style="display: inline;">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success">Одобрить</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger">Отклонить</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
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
