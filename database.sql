CREATE DATABASE IF NOT EXISTS `randevulu_cekici` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `randevulu_cekici`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin') NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_code` VARCHAR(20) NOT NULL UNIQUE,
  `brand_model` VARCHAR(100) NOT NULL,
  `plate` VARCHAR(20) NOT NULL,
  `issue_type` VARCHAR(100) NOT NULL,
  `pickup_location` VARCHAR(255) NOT NULL,
  `dropoff_location` VARCHAR(255) NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `distance_km` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `pickup_latitude` DECIMAL(10,6) NULL,
  `pickup_longitude` DECIMAL(10,6) NULL,
  `dropoff_latitude` DECIMAL(10,6) NULL,
  `dropoff_longitude` DECIMAL(10,6) NULL,
  `status` ENUM('pending', 'on_way', 'completed', 'canceled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_title', 'Henox Çekici Hizmetleri'),
('contact_phone', '+90 555 123 45 67'),
('contact_email', 'destek@cekici.com'),
('company_address', 'İstanbul'),
('tow_base_price', '0')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$wEkgkUfC/s1Y3/Kj6eB24e.XzB9P2jV1c/N7h5M1G9iZlD3G/Zt7q', 'admin')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `role` = VALUES(`role`);
