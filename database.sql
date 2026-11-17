-- Создание базы данных
CREATE DATABASE IF NOT EXISTS sweet_dreams_hotel 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sweet_dreams_hotel;

-- Таблица администраторов
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица категорий номеров
CREATE TABLE IF NOT EXISTS room_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

-- Таблица номеров
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    price_per_day DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES room_categories(id) ON DELETE CASCADE
);

-- Таблица удобств
CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Связующая таблица (многие-ко-многим)
CREATE TABLE IF NOT EXISTS room_amenities (
    room_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (room_id, amenity_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);

-- Таблица заявок на бронирование
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('new', 'approved', 'rejected') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Категории
INSERT INTO room_categories (name) VALUES 
('Стандартный'), ('Студия'), ('Люкс');

-- Удобства
INSERT INTO amenities (name) VALUES 
('Полный санузел'), ('Двуспальная кровать'), ('Телевизор'),
('Кухонный уголок'), ('Холодильник'), ('Кондиционер'), ('Двухкомнатный');

-- Номера (по 3 на каждую категорию)
INSERT INTO rooms (category_id, price_per_day, image, description) VALUES
(1, 3000.00, 'standard1.jpg', 'Стандартный номер №1'),
(1, 3500.00, 'standard2.jpg', 'Стандартный номер №2'),
(1, 3800.00, 'standard3.jpg', 'Стандартный номер №3'),
(2, 4600.00, 'studio1.jpg', 'Студия №1'),
(2, 5400.00, 'studio2.jpg', 'Студия №2'),
(2, 6200.00, 'studio3.jpg', 'Студия №3'),
(3, 8500.00, 'luxury1.jpg', 'Люкс №1'),
(3, 11000.00, 'luxury2.jpg', 'Люкс №2'),
(3, 15000.00, 'luxury3.jpg', 'Люкс №3');

-- Связи номеров с удобствами
INSERT INTO room_amenities (room_id, amenity_id) VALUES
(1,1),(1,2),(1,3), (2,1),(2,2),(2,3), (3,1),(3,2),(3,3),
(4,1),(4,2),(4,3),(4,4),(4,5), (5,1),(5,2),(5,3),(5,4),(5,5), (6,1),(6,2),(6,3),(6,4),(6,5),
(7,1),(7,2),(7,3),(7,5),(7,6),(7,7), (8,1),(8,2),(8,3),(8,5),(8,6),(8,7), (9,1),(9,2),(9,3),(9,5),(9,6),(9,7);

-- Администратор: логин hotel123, пароль adminHotel
-- Хеш генерируется через: password_hash('adminHotel', PASSWORD_DEFAULT)
INSERT INTO admin_users (login, password_hash) VALUES 
('hotel123', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe.YYr3hFZhvvAUaHMvdh6mZDH3gxPLu6');
