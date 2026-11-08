CREATE DATABASE IF NOT EXISTS corewebber CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE corewebber;

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('client','partner','admin') NOT NULL DEFAULT 'client',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 service VARCHAR(160) NOT NULL,
 title VARCHAR(220) NOT NULL,
 details TEXT NOT NULL,
 status ENUM('New','In review','In progress','Completed','Closed') NOT NULL DEFAULT 'New',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_requests_user (user_id),
 INDEX idx_requests_status (status)
) ENGINE=InnoDB;

-- After registering your own account, promote it to administrator:
-- UPDATE users SET role='admin' WHERE email='YOUR-EMAIL@example.com';
