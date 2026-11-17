<?php
require_once 'config/db.php';

// Обработка фильтрации
$filterCategory = $_GET['category'] ?? '';

$sql = "SELECT r.*, rc.name AS category_name, 
        GROUP_CONCAT(a.name SEPARATOR ', ') AS amenities
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        LEFT JOIN room_amenities ra ON r.id = ra.room_id
        LEFT JOIN amenities a ON ra.amenity_id = a.id
        WHERE r.is_active = 1";

$params = [];

if (!empty($filterCategory)) {
    $sql .= " AND rc.name = :category";
    $params[':category'] = $filterCategory;
}

$sql .= " GROUP BY r.id ORDER BY RAND() LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Получаем категории для фильтра
$categories = $pdo->query("SELECT * FROM room_categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Светлые Сны - Каталог номеров</title>
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
        <h2>Каталог номеров</h2>

        <!-- Форма фильтрации -->
        <form method="GET" action="index.php" class="filter-form">
            <div class="filter-group">
                <label>Категории:</label>
                <?php foreach ($categories as $cat): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="category[]" 
                               value="<?= htmlspecialchars($cat['name']) ?>"
                               <?= in_array($cat['name'], is_array($_GET['category'] ?? []) ? $_GET['category'] : []) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Применить</button>
            <a href="index.php" class="btn btn-secondary">Сбросить фильтр</a>
        </form>

        <!-- Вывод номеров -->
        <div class="rooms-grid">
            <?php if (empty($rooms)): ?>
                <p class="no-results">Номера по заданным условиям не найдены.</p>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <article class="room-card">
                        <img src="assets/images/<?= htmlspecialchars($room['image']) ?>" 
                             alt="<?= htmlspecialchars($room['category_name']) ?>">
                        <h3>Категория: <?= htmlspecialchars($room['category_name']) ?></h3>
                        <p class="price">Цена: <?= number_format($room['price_per_day'], 0, ',', ' ') ?> ₽ / чел</p>
                        <div class="characteristics">
                            <p><strong>Характеристики:</strong></p>
                            <ul>
                                <?php 
                                $amenities = explode(', ', $room['amenities'] ?? '');
                                foreach ($amenities as $amenity): 
                                    if (!empty($amenity)):
                                ?>
                                    <li><?= htmlspecialchars($amenity) ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                        <a href="order.php?room_id=<?= $room['id'] ?>" class="btn btn-primary">Забронировать</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
