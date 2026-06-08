-- Inventory Management and Knowledge Base upgrade
-- Run once on an existing HEMS database before using the expanded modules.

CREATE TABLE IF NOT EXISTS inventory_suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,
    contact_name VARCHAR(150),
    email VARCHAR(150),
    phone VARCHAR(50),
    address TEXT,
    lead_time_days INT DEFAULT 7,
    payment_terms VARCHAR(150),
    rating DECIMAL(3,2),
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_supplier_active (is_active, deleted_at)
) ENGINE=InnoDB;

ALTER TABLE inventory_items
    ADD COLUMN reorder_quantity INT DEFAULT 0 AFTER reorder_level,
    ADD COLUMN supplier_id BIGINT UNSIGNED NULL AFTER location,
    ADD COLUMN last_restocked_at TIMESTAMP NULL AFTER supplier,
    ADD INDEX idx_inv_supplier (supplier_id);

CREATE TABLE IF NOT EXISTS inventory_purchase_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(30) NOT NULL UNIQUE,
    item_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    status ENUM('draft','submitted','approved','ordered','received','rejected','cancelled') DEFAULT 'submitted',
    quantity INT NOT NULL,
    unit_cost DECIMAL(12,2),
    total_cost DECIMAL(12,2),
    needed_by DATE,
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    ordered_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_pr_status (status),
    INDEX idx_pr_item (item_id)
) ENGINE=InnoDB;

ALTER TABLE knowledge_articles
    ADD COLUMN article_type ENUM('guide','faq','procedure','policy','troubleshooting') DEFAULT 'guide' AFTER status,
    ADD COLUMN is_faq TINYINT(1) DEFAULT 0 AFTER article_type;

ALTER TABLE knowledge_articles DROP INDEX idx_kb_search;
ALTER TABLE knowledge_articles ADD FULLTEXT INDEX idx_kb_search (title, excerpt, content, tags);

CREATE TABLE IF NOT EXISTS knowledge_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120),
    file_size INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_kb_attach_article (article_id)
) ENGINE=InnoDB;
