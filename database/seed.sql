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
      
      
-- First administrator
-- Username: admin    Password: Admin@123   (change this after your first login)
INSERT INTO administrators (username, password_hash) VALUES
('jerex', '$2y$10$P3Whw7YeFA/JcxjA.TbdMuQoGLR1fosMkveMGID6gb8WR8fN583GC'); 

-- Sample services (edit names and prices to match your shop)
INSERT INTO services (service_name, description, base_price) VALUES
('Screen Replacement',      'Replace cracked or unresponsive display and touch screen.', 1000.00),
('Battery Replacement',     'Replace a battery that drains fast or swells.',             1000.00),
('Charging Port Repair',    'Fix loose, dirty or broken charging port.',                  800.00),
('Water Damage Repair',     'Clean and restore a device that got wet.',                  1200.00),
('Software Reset & Update', 'Fix freezing, boot loops and failed updates.',               500.00),
('Camera Repair',           'Replace a broken front or rear camera.',                    1100.00);

DELETE FROM administrators;
DELETE FROM repair_requests;
DELETE FROM technicians;

SELECT * FROM administrators;
SELECT * FROM repair_requests;
SELECT * FROM services;
SELECT * FROM technicians;