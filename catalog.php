<?php
$pageTitle = 'Каталог номеров — Отель «Уют»';
include 'includes/header.php';
include 'config/db.php';

// Обработка фильтра
$filterCategory = $_GET['category'] ?? '';
$sql = "SELECT r.*, GROUP_CONCAT(a.name SEPARATOR ', ') AS amenities 
        FROM rooms r 
        LEFT JOIN room_amenities ra ON r.id = ra.room_id
        LEFT JOIN amenities a ON ra.amenity_id = a.id";
$params = [];

if (!empty($filterCategory)) {
    $sql .= " WHERE r.category = :category";
    $params[':category'] = $filterCategory;
}

$sql .= " GROUP BY r.id ORDER BY RAND() LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Получаем список категорий для фильтра
$categories = $pdo->query("SELECT DISTINCT category FROM rooms")->fetchAll(PDO::FETCH_COLUMN);
?>

<section class="catalog">
    <h2>Каталог номеров</h2>
    
    <!-- Форма фильтрации -->
    <form method="GET" action="catalog.php" class="filter-form">
        <label for="category">Категория:</label>
        <select name="category" id="category">
            <option value="">— Все категории —</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" 
                    <?= $filterCategory === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Применить</button>
        <a href="catalog.php" class="btn btn-secondary">Сбросить фильтр</a>
    </form>

    <!-- Вывод номеров -->
    <div class="rooms-grid">
        <?php if (empty($rooms)): ?>
            <p class="no-results">Номера по заданным условиям не найдены.</p>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <article class="room-card">
                    <img src="assets/images/<?= htmlspecialchars($room['image']) ?>" 
                         alt="<?= htmlspecialchars($room['category']) ?>">
                    <h3><?= htmlspecialchars($room['category']) ?></h3>
                    <p class="price"><?= number_format($room['price_per_day'], 0, ',', ' ') ?> ₽/сутки</p>
                    <p class="features">
                        Мест: <?= $room['capacity'] ?> | 
                        Площадь: <?= $room['area'] ?> м²
                    </p>
                    <p class="amenities">
                        <strong>Удобства:</strong> <?= htmlspecialchars($room['amenities'] ?? '—') ?>
                    </p>
                    <a href="booking.php?room_id=<?= $room['id'] ?>" class="btn btn-primary">
                        Забронировать
                    </a>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
