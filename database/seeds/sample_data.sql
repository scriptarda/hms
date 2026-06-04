-- HEMS Sample Data
-- Database handled by installer


-- Roles
INSERT INTO roles (name, slug, description, is_system) VALUES
('Staff', 'staff', 'General hospital staff', 1),
('Nurse', 'nurse', 'Nursing staff', 1),
('Doctor', 'doctor', 'Medical doctors', 1),
('Technician', 'technician', 'IT and maintenance technicians', 1),
('Biomedical Engineer', 'biomedical_engineer', 'Biomedical engineering staff', 1),
('Manager', 'manager', 'Department managers', 1),
('Administrator', 'administrator', 'System administrators', 1),
('Super Administrator', 'super_administrator', 'Full system access', 1);

-- Permissions
INSERT INTO permissions (name, slug, module) VALUES
('View Dashboard', 'dashboard.view', 'dashboard'),
('View Tickets', 'tickets.view', 'tickets'),
('Create Tickets', 'tickets.create', 'tickets'),
('Edit Tickets', 'tickets.edit', 'tickets'),
('Delete Tickets', 'tickets.delete', 'tickets'),
('Assign Tickets', 'tickets.assign', 'tickets'),
('View Assets', 'assets.view', 'assets'),
('Create Assets', 'assets.create', 'assets'),
('Edit Assets', 'assets.edit', 'assets'),
('Delete Assets', 'assets.delete', 'assets'),
('View Maintenance', 'maintenance.view', 'maintenance'),
('Create Maintenance', 'maintenance.create', 'maintenance'),
('Edit Maintenance', 'maintenance.edit', 'maintenance'),
('View Inventory', 'inventory.view', 'inventory'),
('Manage Inventory', 'inventory.manage', 'inventory'),
('View Knowledge Base', 'knowledge.view', 'knowledge'),
('Manage Knowledge Base', 'knowledge.manage', 'knowledge'),
('View Reports', 'reports.view', 'reports'),
('Export Reports', 'reports.export', 'reports'),
('Manage Users', 'users.manage', 'admin'),
('Manage Roles', 'roles.manage', 'admin'),
('Manage Departments', 'departments.manage', 'admin'),
('System Settings', 'settings.manage', 'admin'),
('View Audit Logs', 'audit.view', 'admin'),
('Manage Service Requests', 'service_requests.manage', 'service_requests'),
('Approve Requests', 'service_requests.approve', 'service_requests');

-- Role Permissions (Super Admin gets all)
INSERT INTO role_permissions (role_id, permission_id) SELECT 8, id FROM permissions;

-- Admin gets almost all
INSERT INTO role_permissions (role_id, permission_id) SELECT 7, id FROM permissions WHERE slug NOT IN ('settings.manage');

-- Manager
INSERT INTO role_permissions (role_id, permission_id) SELECT 6, id FROM permissions WHERE module IN ('dashboard','tickets','assets','maintenance','inventory','knowledge','reports','service_requests');

-- Technician
INSERT INTO role_permissions (role_id, permission_id) SELECT 4, id FROM permissions WHERE slug IN ('dashboard.view','tickets.view','tickets.create','tickets.edit','tickets.assign','assets.view','assets.edit','maintenance.view','maintenance.create','maintenance.edit','inventory.view','knowledge.view');

-- Staff
INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions WHERE slug IN ('dashboard.view','tickets.view','tickets.create','knowledge.view','inventory.view');

-- Departments
INSERT INTO departments (name, code, description) VALUES
('Radiology', 'RAD', 'Radiology and Imaging Department'),
('Cardiology', 'CARD', 'Cardiology Department'),
('Emergency', 'ER', 'Emergency Department'),
('ICU', 'ICU', 'Intensive Care Unit'),
('General Surgery', 'SURG', 'General Surgery Department'),
('Laboratory', 'LAB', 'Clinical Laboratory'),
('Pharmacy', 'PHAR', 'Pharmacy Department'),
('IT Services', 'IT', 'Information Technology'),
('Biomedical Engineering', 'BME', 'Biomedical Engineering Department'),
('Administration', 'ADMIN', 'Hospital Administration');

-- Buildings
INSERT INTO buildings (name, code, address) VALUES
('Main Hospital', 'MH', '100 Medical Center Drive'),
('East Wing', 'EW', '100 Medical Center Drive - East'),
('West Wing', 'WW', '100 Medical Center Drive - West'),
('Admin Building', 'AB', '110 Medical Center Drive');

-- Floors
INSERT INTO floors (building_id, name, floor_number) VALUES
(1, 'Ground Floor', 0), (1, 'First Floor', 1), (1, 'Second Floor', 2), (1, 'Third Floor', 3),
(2, 'Ground Floor', 0), (2, 'First Floor', 1),
(3, 'Ground Floor', 0), (3, 'First Floor', 1);

-- Rooms
INSERT INTO rooms (floor_id, name, room_number, room_type) VALUES
(1, 'Server Room A', '001', 'server_room'),
(1, 'MRI Suite', '010', 'imaging'),
(2, 'ICU Bay 1', '101', 'patient_care'),
(2, 'ICU Bay 2', '102', 'patient_care'),
(3, 'OR Suite 1', '201', 'operating_room'),
(4, 'Ward 3A', '301', 'ward');

-- Users (password: Password123!)
INSERT INTO users (first_name, last_name, email, password, department_id, job_title, status) VALUES
('System', 'Admin', 'admin@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10, 'Super Administrator', 'active'),
('Sarah', 'Chen', 'sarah.chen@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10, 'Sr. Administrator', 'active'),
('Marcus', 'Thorne', 'marcus.thorne@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, 'Sr. Clinical Engineer', 'active'),
('John', 'Doe', 'john.doe@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 9, 'Chief Technologist', 'active'),
('Alice', 'Smith', 'alice.smith@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Radiology Technician', 'active'),
('Bob', 'Wilson', 'bob.wilson@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'ER Nurse', 'active'),
('Emily', 'Davis', 'emily.davis@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'Cardiologist', 'active'),
('James', 'Miller', 'james.miller@healthcentral.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 8, 'IT Manager', 'active');

-- User Roles
INSERT INTO user_roles (user_id, role_id) VALUES
(1, 8), (2, 7), (3, 4), (4, 6), (5, 4), (6, 2), (7, 3), (8, 6);

-- Ticket Categories
INSERT INTO ticket_categories (name, icon, description) VALUES
('Hardware', 'bi-pc-display', 'Hardware issues and requests'),
('Software', 'bi-code-square', 'Software issues and installations'),
('Network', 'bi-wifi', 'Network connectivity issues'),
('Medical Equipment', 'bi-heart-pulse', 'Medical device issues'),
('Facilities', 'bi-building', 'Building and facilities issues'),
('Access & Security', 'bi-shield-lock', 'Access control and security');

-- Ticket Subcategories
INSERT INTO ticket_subcategories (category_id, name) VALUES
(1, 'Computer Issue'), (1, 'Printer Issue'), (1, 'Monitor Issue'),
(2, 'Software Install'), (2, 'Software Bug'), (2, 'EMR Issue'),
(3, 'WiFi Issue'), (3, 'VPN Issue'), (3, 'Network Port'),
(4, 'MRI Scanner'), (4, 'Ventilator'), (4, 'Patient Monitor'), (4, 'Infusion Pump'),
(5, 'HVAC'), (5, 'Electrical'), (5, 'Plumbing'),
(6, 'Badge Access'), (6, 'System Access'), (6, 'Password Reset');

-- Asset Categories
INSERT INTO asset_categories (name, code, description) VALUES
('Biomedical', 'BIO', 'Biomedical equipment'),
('Imaging', 'IMG', 'Imaging and radiology equipment'),
('Laboratory', 'LAB', 'Laboratory equipment'),
('Surgical', 'SURG', 'Surgical equipment'),
('IT Equipment', 'IT', 'Computers, printers, network'),
('Other', 'OTH', 'Other equipment');

-- Assets
INSERT INTO assets (asset_tag, name, serial_number, category_id, manufacturer, model, department_id, status, purchase_date, warranty_expiry) VALUES
('MRI-SCAN-01', 'Siemens Magnetom Lumina', 'SN-MRI-2023-001', 2, 'Siemens', 'Magnetom Lumina 3T', 1, 'active', '2023-01-15', '2028-01-15'),
('VNT-ICU-42', 'Hamilton G5 Ventilator', 'SN-VNT-2023-042', 1, 'Hamilton Medical', 'G5', 4, 'active', '2023-03-20', '2026-03-20'),
('MON-GW-15', 'Philips Monitor G4', 'SN-MON-2023-015', 1, 'Philips', 'IntelliVue MX800', 4, 'active', '2023-02-10', '2026-02-10'),
('PMP-ER-08', 'Medtronic Pump P-22', 'SN-PMP-2023-008', 1, 'Medtronic', 'P-22 Infusion', 3, 'active', '2023-04-05', '2026-04-05'),
('CT-RAD-02', 'Siemens Somatom CT', 'SN-CT-2022-002', 2, 'Siemens', 'Somatom Force', 1, 'active', '2022-06-15', '2027-06-15'),
('US-CARD-01', 'GE Healthcare Ultrasound', 'SN-US-2023-001', 2, 'GE Healthcare', 'Vivid E95', 2, 'active', '2023-05-20', '2026-05-20');

-- Sample Tickets
INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, requester_id, assigned_to, department_id, asset_id, sla_due_at) VALUES
('HEMS-9402', 'Siemens Somatom MRI - Calibration Error', 'MRI scanner showing calibration errors during startup. Affecting patient scans in radiology.', 4, 'critical', 'in_progress', 5, 3, 1, 1, DATE_ADD(NOW(), INTERVAL 1 HOUR)),
('HEMS-9398', 'Dräger Atlan Ventilator Alert', 'Ventilator in ICU Bed 04 showing intermittent pressure sensor alerts.', 4, 'high', 'assigned', 6, 3, 4, 2, DATE_ADD(NOW(), INTERVAL 4 HOUR)),
('HEMS-9391', 'Medtronic Pump P-22 Flow Rate Issue', 'Infusion pump flow rate inconsistent. Emergency ward unit.', 4, 'medium', 'new', 6, NULL, 3, 4, DATE_ADD(NOW(), INTERVAL 8 HOUR)),
('HEMS-9385', 'Philips Monitor G4 Display Flickering', 'Patient monitor display flickering intermittently in General Ward B.', 4, 'low', 'new', 6, NULL, 4, 3, DATE_ADD(NOW(), INTERVAL 24 HOUR)),
('HEMS-9345', 'Software Update - Patient Portal', 'Need to update patient portal software to latest version.', 2, 'low', 'assigned', 8, 3, 8, NULL, DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- SLA Rules
INSERT INTO sla_rules (name, priority, response_time, resolution_time, escalation_time, is_active) VALUES
('Critical SLA', 'critical', 15, 60, 30, 1),
('High SLA', 'high', 30, 240, 120, 1),
('Medium SLA', 'medium', 60, 480, 240, 1),
('Low SLA', 'low', 120, 1440, 720, 1);

-- Inventory Categories
INSERT INTO inventory_categories (name, code, description) VALUES
('Spare Parts', 'SP', 'Replacement parts for equipment'),
('Consumables', 'CON', 'Consumable supplies'),
('Tools', 'TL', 'Maintenance tools'),
('Cables & Connectors', 'CC', 'Cables and connectors');

-- Inventory Items
INSERT INTO inventory_items (name, sku, category_id, unit, quantity, min_quantity, reorder_level, unit_cost, location, supplier) VALUES
('MRI Cooling Fluid (1L)', 'SP-MRI-CF-1L', 1, 'liters', 25, 5, 10, 150.00, 'Warehouse A-1', 'Siemens Parts Direct'),
('Ventilator O2 Sensor', 'SP-VNT-O2S', 1, 'pcs', 12, 3, 5, 89.50, 'Warehouse A-2', 'Hamilton Medical Parts'),
('Patient Monitor Cable Set', 'SP-MON-CBS', 1, 'sets', 8, 2, 4, 45.00, 'Warehouse B-1', 'Philips Supply Chain'),
('IV Tubing Set', 'CON-IV-TS', 2, 'pcs', 500, 100, 200, 3.50, 'Warehouse C-1', 'Medical Supplies Inc'),
('Network Cable Cat6 (3m)', 'CC-NET-C6-3M', 4, 'pcs', 50, 10, 20, 5.00, 'IT Storage', 'Cable Wholesale');

-- Knowledge Categories
INSERT INTO knowledge_categories (name, slug, description, icon, color) VALUES
('Equipment Guides', 'equipment-guides', 'Calibration, maintenance, and operation manuals for all clinical hardware.', 'bi-tools', '#1a56db'),
('Software Troubleshooting', 'software-troubleshooting', 'Step-by-step resolution for EMR, imaging systems, and lab management software.', 'bi-code-square', '#0d9488'),
('Facility Safety', 'facility-safety', 'Biological hazard protocols, emergency exit maps, and safety compliance guides.', 'bi-shield-exclamation', '#dc2626'),
('System FAQs', 'system-faqs', 'General operational questions, scheduling, and hospital-wide standard procedures.', 'bi-question-circle', '#7c3aed');

-- Knowledge Articles
INSERT INTO knowledge_articles (category_id, title, slug, content, excerpt, author_id, status, is_featured, views, tags) VALUES
(1, 'MRI Suite Cooling System Emergency Protocol', 'mri-cooling-emergency-protocol', '<h2>Emergency Cooling Protocol</h2><p>When the MRI cooling system triggers an alert, follow these steps immediately...</p>', 'Emergency procedure for MRI cooling system failures.', 1, 'published', 1, 342, 'CRITICAL,RADIOLOGY'),
(2, 'New Multi-Factor Authentication Requirements', 'mfa-requirements-hems', '<h2>MFA Setup Guide</h2><p>All HEMS users must enable multi-factor authentication by end of quarter...</p>', 'Guide to setting up MFA for HEMS access.', 2, 'published', 0, 189, 'SECURITY,MANDATORY'),
(3, 'Standard Operating Procedure: Patient Record Transfer', 'sop-patient-record-transfer', '<h2>Patient Record Transfer SOP</h2><p>Follow this procedure when transferring patient records between departments...</p>', 'SOP for inter-department patient record transfers.', 2, 'published', 0, 95, 'WORKFLOW');

-- Service Catalog
INSERT INTO service_catalog_items (type, name, short_description, description, icon, color, category, default_priority, approval_mode, fulfillment_category_id, sla_hours, form_schema, sort_order) VALUES
('new_computer', 'New Computer Request', 'Provision a workstation, laptop, clinical terminal, or monitor bundle.', 'Request a new endpoint device with operating profile, accessories, and delivery location.', 'bi-pc-display', '#1a56db', 'Hardware', 'medium', 'department_head', 1, 72, '[{"key":"device_type","label":"Device Type","type":"select","required":true,"options":[{"value":"desktop","label":"Desktop Workstation"},{"value":"laptop","label":"Laptop"},{"value":"tablet","label":"Clinical Tablet"},{"value":"monitor_bundle","label":"Monitor Bundle"}]},{"key":"primary_user","label":"Primary User or Station","type":"text","required":true},{"key":"performance_profile","label":"Performance Profile","type":"select","required":true,"options":[{"value":"standard","label":"Standard clinical"},{"value":"performance","label":"Performance imaging/admin"},{"value":"mobile_rounding","label":"Mobile rounding"}]},{"key":"delivery_location","label":"Delivery Location","type":"text","required":true},{"key":"needed_by","label":"Needed By","type":"date","required":false}]', 10),
('software_install', 'Software Installation', 'Install licensed clinical, admin, or utility software on an approved device.', 'Request software installation with licensing and target asset details.', 'bi-cloud-download', '#059669', 'Software', 'medium', 'department_head', 2, 48, '[{"key":"software_name","label":"Software Name","type":"text","required":true},{"key":"license_source","label":"License Source","type":"select","required":true,"options":[{"value":"existing_license","label":"Existing hospital license"},{"value":"new_purchase","label":"New purchase required"},{"value":"freeware","label":"Approved freeware"}]},{"key":"target_asset_tag","label":"Target Asset Tag","type":"text","required":true},{"key":"operating_system","label":"Operating System","type":"select","required":false,"options":[{"value":"windows","label":"Windows"},{"value":"macos","label":"macOS"},{"value":"ios","label":"iOS"},{"value":"android","label":"Android"}]}]', 20),
('email_creation', 'Email Creation', 'Create a mailbox, shared mailbox, alias, or distribution group.', 'Request email account creation with naming, manager, and onboarding details.', 'bi-envelope-at', '#d97706', 'Identity', 'medium', 'department_head', 6, 24, '[{"key":"employee_first_name","label":"First Name","type":"text","required":true},{"key":"employee_last_name","label":"Last Name","type":"text","required":true},{"key":"employee_id","label":"Employee ID","type":"text","required":false},{"key":"mailbox_type","label":"Mailbox Type","type":"select","required":true,"options":[{"value":"user_mailbox","label":"User mailbox"},{"value":"shared_mailbox","label":"Shared mailbox"},{"value":"distribution_list","label":"Distribution list"}]},{"key":"desired_address","label":"Desired Address","type":"email","required":false},{"key":"start_date","label":"Start Date","type":"date","required":false}]', 30),
('access_request', 'Access Request', 'Request application permissions, badge access, VPN, or privileged roles.', 'Request access with a system, level, expiration, and business justification.', 'bi-shield-lock', '#7c3aed', 'Security', 'medium', 'administrator', 6, 24, '[{"key":"target_system","label":"Target System","type":"select","required":true,"options":[{"value":"emr","label":"Electronic Medical Records"},{"value":"pacs","label":"PACS Imaging"},{"value":"pharmacy","label":"Pharmacy System"},{"value":"billing","label":"Billing and Finance"},{"value":"badge_access","label":"Badge Access"}]},{"key":"access_level","label":"Access Level","type":"select","required":true,"options":[{"value":"read_only","label":"Read only"},{"value":"standard","label":"Standard user"},{"value":"supervisor","label":"Supervisor"},{"value":"admin","label":"Administrator"}]},{"key":"expiration_date","label":"Expiration Date","type":"date","required":false},{"key":"justification","label":"Access Justification","type":"textarea","required":true}]', 40),
('network_access', 'Network Access', 'Request network ports, Wi-Fi, VPN, firewall, or device onboarding.', 'Request connectivity changes with device identity and access scope.', 'bi-wifi', '#0891b2', 'Network', 'medium', 'administrator', 3, 48, '[{"key":"access_type","label":"Access Type","type":"select","required":true,"options":[{"value":"wired_port","label":"Wired port activation"},{"value":"wifi","label":"Wi-Fi access"},{"value":"vpn","label":"VPN access"},{"value":"firewall_rule","label":"Firewall rule"}]},{"key":"device_name","label":"Device Name","type":"text","required":true},{"key":"mac_address","label":"MAC Address","type":"text","required":false},{"key":"network_location","label":"Location or VLAN","type":"text","required":true},{"key":"business_owner","label":"Business Owner","type":"text","required":true}]', 50),
('equipment_request', 'Equipment Request', 'Request clinical equipment, loaner devices, accessories, or spare hardware.', 'Request hospital equipment with quantity, urgency, and delivery details.', 'bi-hdd-stack', '#dc2626', 'Equipment', 'medium', 'department_head', 4, 72, '[{"key":"equipment_type","label":"Equipment Type","type":"text","required":true},{"key":"quantity","label":"Quantity","type":"number","required":true},{"key":"request_reason","label":"Reason","type":"select","required":true,"options":[{"value":"new_service","label":"New service or station"},{"value":"replacement","label":"Replacement"},{"value":"temporary_loan","label":"Temporary loan"},{"value":"surge_capacity","label":"Surge capacity"}]},{"key":"delivery_location","label":"Delivery Location","type":"text","required":true},{"key":"needed_by","label":"Needed By","type":"date","required":false}]', 60);

-- Maintenance Tasks
INSERT INTO maintenance_tasks (title, description, asset_id, type, priority, status, assigned_to, scheduled_date, due_date) VALUES
('ICU Ventilator Series 500 - Annual PM', 'Annual Preventive Maintenance & Sensor Calibration', 2, 'preventive', 'high', 'scheduled', 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
('Philips Patient Monitor G40 - Firmware Update', 'Firmware Update & Battery Load Testing', 3, 'preventive', 'medium', 'in_progress', 5, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
('GE Healthcare Ultrasound - Routine Inspection', 'Routine Visual Inspection & Cleaning', 6, 'inspection', 'low', 'scheduled', NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY));

-- Notifications
INSERT INTO notifications (user_id, type, title, message, link) VALUES
(3, 'ticket_assigned', 'New Ticket Assigned', 'You have been assigned ticket HEMS-9402: Siemens Somatom MRI', '/tickets/1'),
(3, 'sla_warning', 'SLA Warning', 'Ticket HEMS-9402 is approaching SLA deadline.', '/tickets/1'),
(1, 'system', 'System Update', 'HEMS Core v1.0 has been deployed successfully.', '/dashboard');
