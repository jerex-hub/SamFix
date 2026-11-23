-- migration.sql: upgrades an existing samfix database WITHOUT deleting data
USE samfix;

ALTER TABLE repair_requests
  MODIFY status ENUM('Pending','In Progress','Ready for pickup','Completed','Cancelled')
  NOT NULL DEFAULT 'Pending';

CREATE TABLE IF NOT EXISTS technicians (
  technician_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(100) NOT NULL DEFAULT '',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE repair_requests
  ADD COLUMN technician_id  INT UNSIGNED NULL AFTER service_id,
  ADD COLUMN estimated_cost DECIMAL(10,2) NULL AFTER notes,
  ADD CONSTRAINT fk_rr_technician FOREIGN KEY (technician_id)
      REFERENCES technicians(technician_id) ON DELETE SET NULL;