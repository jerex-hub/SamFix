CREATE DATABASE IF NOT EXISTS samfix
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE samfix;

-- Drop in reverse dependency order so the script can be rerun during development
DROP TABLE IF EXISTS repair_requests;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS administrators;

CREATE TABLE administrators (
  admin_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE services (
  service_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(100)  NOT NULL,
  description  TEXT,
  base_price   DECIMAL(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE repair_requests (
  request_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_code  VARCHAR(30)  NOT NULL UNIQUE,
  service_id    INT UNSIGNED NULL,
  updated_by    INT UNSIGNED NULL,
  customer_name VARCHAR(100) NOT NULL,
  email         VARCHAR(100) NOT NULL,
  phone         VARCHAR(20)  NOT NULL,
  device_brand  VARCHAR(50)  NOT NULL,
  device_model  VARCHAR(50)  NOT NULL,
  issue         VARCHAR(255) NOT NULL,
  notes         TEXT NULL,
  status        ENUM('Pending','In Progress','Completed','Cancelled')
                NOT NULL DEFAULT 'Pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_phone (phone),
  INDEX idx_status (status),
  FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE SET NULL,
  FOREIGN KEY (updated_by) REFERENCES administrators(admin_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;