USE samfix;

-- First administrator
-- Username: admin    Password: Admin@123   (change this after your first login)
INSERT INTO administrators (username, password_hash) VALUES
('admin', '$2y$12$6MpKz/yVQjqBnfhy9YTqeueAJEFKoaJSjZ4ggaWn.jNLUbBodNE3u');

-- Sample services (edit names and prices to match your shop)
INSERT INTO services (service_name, description, base_price) VALUES
('Screen Replacement',      'Replace cracked or unresponsive display and touch screen.', 2500.00),
('Battery Replacement',     'Replace a battery that drains fast or swells.',             1200.00),
('Charging Port Repair',    'Fix loose, dirty or broken charging port.',                  800.00),
('Water Damage Repair',     'Clean and restore a device that got wet.',                  1500.00),
('Software Reset & Update', 'Fix freezing, boot loops and failed updates.',               500.00),
('Camera Repair',           'Replace a broken front or rear camera.',                    1000.00);