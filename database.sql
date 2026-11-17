-- Создание базы данных
CREATE DATABASE IF NOT EXISTS hotel_booking 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE hotel_booking;

-- Таблица категорий номеров (используем ENUM для строгой типизации)
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Стандартный', 'Студия', 'Люкс') NOT NULL,
    price_per_day DECIMAL(10, 2) NOT NULL,
    capacity TINYINT NOT NULL,
    area DECIMAL(5, 2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Таблица удобств
CREATE TABLE amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Связующая таблица (многие-ко-многим)
CREATE TABLE room_amenities (
    room_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (room_id, amenity_id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);

-- Таблица заявок на бронирование
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('NEW', 'APPROVED', 'REJECTED') DEFAULT 'NEW',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Тестовые данные
INSERT INTO amenities (name) VALUES 
('Кондиционер'), ('Телевизор'), ('Холодильник'), ('Wi-Fi'), ('Сейф'), ('Фен');

INSERT INTO rooms (category, price_per_day, capacity, area, image) VALUES
('Стандартный', 3500.00, 2, 18.5, 'room_standard.jpg'),
('Студия', 5500.00, 3, 32.0, 'room_studio.jpg'),
('Люкс', 9000.00, 2, 45.0, 'room_luxury.jpg'),
('Стандартный', 3800.00, 1, 16.0, 'room_standard_2.jpg'),
('Студия', 6000.00, 4, 38.5, 'room_studio_2.jpg'),
('Люкс', 12000.00, 4, 60.0, 'room_luxury_2.jpg'),
('Стандартный', 4000.00, 2, 20.0, 'room_standard_3.jpg'),
('Люкс', 10000.00, 3, 50.0, 'room_luxury_3.jpg');
