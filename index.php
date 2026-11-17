<?php
require_once 'config/db.php';

$filterCategories = $_GET['category'] ?? [];
if (!is_array($filterCategories)) {
    $filterCategories = [$filterCategories];
}

$sql = "SELECT r.id, r.price_per_day, r.image, r.description, rc.name AS category_name, 
               GROUP_CONCAT(a.name SEPARATOR ', ') AS amenities
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        LEFT JOIN room_amenities ra ON r.id = ra.room_id
        LEFT JOIN amenities a ON ra.amenity_id = a.id
        WHERE r.is_active = 1";

$params = [];

if (!empty($filterCategories)) {
    $placeholders = [];
    foreach ($filterCategories as $index => $category) {
        $paramName = ':cat_' . $index;
        $placeholders[] = $paramName;
        $params[$paramName] = $category;
    }
    $sql .= " AND rc.name IN (" . implode(',', $placeholders) . ")";
}

$sql .= " GROUP BY r.id ORDER BY RAND() LIMIT 9";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$categories = $pdo->query("SELECT name FROM room_categories ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Светлые Сны - Каталог номеров</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header bg-dark text-white py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="site-title mb-0">
            <a href="index.php" class="text-white text-decoration-none">Светлые Сны</a>
        </h1>
        <nav>
            <a href="index.php" class="text-white me-3">Каталог</a>
            <a href="login.php" class="text-white">Вход</a>
        </nav>
        <p class="slogan mb-0 fst-italic">Приезжайте как гости, уезжайте как друзья!</p>
    </div>
</header>

<main class="container my-4">
    <h2 class="mb-4">Каталог номеров</h2>

    <form method="GET" action="index.php" class="filter-form bg-light p-3 rounded mb-4">
        <div class="filter-group mb-3">
            <label class="form-label fw-bold">Категории</label>
            <div>
                <?php foreach ($categories as $cat): ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="category[]" 
                               id="cat_<?= md5($cat) ?>"
                               value="<?= htmlspecialchars($cat) ?>"
                               <?= in_array($cat, $filterCategories) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="cat_<?= md5($cat) ?>">
                            <?= htmlspecialchars($cat) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Применить</button>
        <a href="index.php" class="btn btn-secondary">Сбросить фильтр</a>
    </form>

    <div class="row g-4">
        <?php if (empty($rooms)): ?>
            <div class="col-12">
                <p class="no-results text-center">Номера по заданным условиям не найдены.</p>
            </div>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="assets/images/<?= htmlspecialchars($room['image']) ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($room['category_name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title">Категория: <?= htmlspecialchars($room['category_name']) ?></h5>
                            <p class="card-text price">Цена: <?= number_format($room['price_per_day'], 0, ',', ' ') ?> ₽ / чел</p>
                            <p class="card-text"><strong>Характеристики:</strong></p>
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
                            <a href="order.php?room_id=<?= $room['id'] ?>" class="btn btn-primary w-100">Забронировать</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
