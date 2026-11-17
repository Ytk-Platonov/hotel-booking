<?php
require_once 'config/db.php';

// Получаем выбранные категории из GET-параметра
$filterCategories = $_GET['category'] ?? [];
if (!is_array($filterCategories)) {
    $filterCategories = [$filterCategories];
}

// Формируем SQL-запрос с фильтрацией на стороне сервера
$sql = "SELECT r.id, r.price_per_day, r.image, rc.name AS category_name, 
               GROUP_CONCAT(a.name SEPARATOR ', ') AS amenities
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        LEFT JOIN room_amenities ra ON r.id = ra.room_id
        LEFT JOIN amenities a ON ra.amenity_id = a.id
        WHERE r.is_active = 1";

$params = [];

// Если категории выбраны, добавляем условие IN
if (!empty($filterCategories)) {
    $placeholders = [];
    foreach ($filterCategories as $index => $category) {
        $paramName = ':cat_' . $index;
        $placeholders[] = $paramName;
        $params[$paramName] = $category;
    }
    $sql .= " AND rc.name IN (" . implode(',', $placeholders) . ")";
}

// Выводим 9 случайных номеров (по 3 на каждую категорию, как в макете)
$sql .= " GROUP BY r.id ORDER BY RAND() LIMIT 9";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Получаем список всех категорий для чекбоксов
$categories = $pdo->query("SELECT name FROM room_categories ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог номеров - Светлые Сны</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header class="site-header">
        <div class="container">
            <h1 class="site-title"><a href="index.php">Светлые Сны</a></h1>
            <p class="slogan"><a href="#">Приезжайте как гости, уезжайте как друзья!</a></p>
        </div>
    </header>

    <main class="container">
        <h2>Каталог номеров</h2>

        <!-- Форма фильтрации (серверная) -->
        <form method="GET" action="index.php" class="filter-form">
            <div class="filter-group">
                <label>Категории</label>
                <?php foreach ($categories as $cat): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="category[]" 
                               value="<?= htmlspecialchars($cat) ?>"
                               <?= in_array($cat, $filterCategories) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Применить</button>
            <a href="index.php" class="btn btn-secondary">Сбросить фильтр</a>
        </form>

        <!-- Сетка номеров -->
        <div class="rooms-grid">
            <?php if (empty($rooms)): ?>
                <p class="no-results">Номера по заданным условиям не найдены.</p>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <article class="room-card">
                        <img src="assets/images/<?= htmlspecialchars($room['image']) ?>" 
                             alt="<?= htmlspecialchars($room['category_name']) ?>">
                        
                        <p class="room-category">Категория: <?= htmlspecialchars($room['category_name']) ?></p>
                        <p class="room-price">Цена: <?= number_format($room['price_per_day'], 0, ',', ' ') ?> ₽ / чел</p>
                        
                        <p class="room-features-title">Характеристики:</p>
                        <ul class="room-features">
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
                        
                        <a href="order.php?room_id=<?= $room['id'] ?>" class="btn btn-primary">Забронировать</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <a href="#">ул. г.Москва, ул. Ивовая, 48</a>
            <a href="#">Время работы: Пн-Пт, с 8:00-17:00</a>
            <a href="tel:88005553535">тел. 8 (800) 555 - 35 - 35</a>
            <a href="mailto:обращения@СветлыеСны.рф">Email: обращения@СветлыеСны.рф</a>
        </div>
    </footer>

</body>
</html>
