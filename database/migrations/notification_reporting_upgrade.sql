-- Notification System and Enterprise Reporting upgrade
-- Run once on an existing HEMS database before using notification preferences,
-- realtime Socket.IO updates, scheduled reports, and PDF exports.

ALTER TABLE notifications
    ADD COLUMN channel VARCHAR(30) DEFAULT 'in_app' AFTER link,
    ADD COLUMN severity ENUM('info','success','warning','danger') DEFAULT 'info' AFTER channel,
    ADD COLUMN data_json JSON NULL AFTER severity,
    ADD COLUMN read_at TIMESTAMP NULL AFTER is_read,
    ADD COLUMN delivered_at TIMESTAMP NULL AFTER read_at,
    ADD COLUMN email_sent_at TIMESTAMP NULL AFTER delivered_at,
    ADD COLUMN sms_queued_at TIMESTAMP NULL AFTER email_sent_at,
    ADD COLUMN push_queued_at TIMESTAMP NULL AFTER sms_queued_at,
    ADD COLUMN expires_at TIMESTAMP NULL AFTER push_queued_at,
    ADD COLUMN deleted_at TIMESTAMP NULL AFTER created_at,
    ADD INDEX idx_notif_type (type, created_at),
    ADD INDEX idx_notif_created (created_at);

CREATE TABLE IF NOT EXISTS notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    in_app TINYINT(1) DEFAULT 1,
    email TINYINT(1) DEFAULT 0,
    sms TINYINT(1) DEFAULT 0,
    push TINYINT(1) DEFAULT 0,
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_notification_preference (user_id, type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_delivery_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('in_app','email','sms','push') NOT NULL,
    status ENUM('queued','sent','failed','skipped') DEFAULT 'queued',
    provider VARCHAR(80),
    recipient VARCHAR(255),
    payload_json JSON NULL,
    error_message TEXT,
    queued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_delivery_user (user_id, channel, status),
    INDEX idx_delivery_notification (notification_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_push_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    endpoint VARCHAR(700) NOT NULL,
    p256dh_key VARCHAR(255),
    auth_token VARCHAR(255),
    user_agent VARCHAR(500),
    is_active TINYINT(1) DEFAULT 1,
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_push_endpoint (endpoint(255)),
    INDEX idx_push_user (user_id, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_realtime_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event_name VARCHAR(80) NOT NULL,
    payload_json JSON NOT NULL,
    status ENUM('pending','delivered','failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_error TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_realtime_pending (status, created_at),
    INDEX idx_realtime_user (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    report_type ENUM('tickets','assets','sla','maintenance','inventory','user_activity') NOT NULL,
    format ENUM('pdf','excel','csv') DEFAULT 'pdf',
    frequency ENUM('daily','weekly','monthly') DEFAULT 'weekly',
    filters_json JSON NULL,
    recipients TEXT,
    channels_json JSON NULL,
    next_run_at TIMESTAMP NULL,
    last_run_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_report_schedule_due (is_active, next_run_at),
    INDEX idx_report_schedule_user (user_id, report_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS report_exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    format VARCHAR(20) NOT NULL,
    file_path VARCHAR(500),
    row_count INT DEFAULT 0,
    status ENUM('generated','failed') DEFAULT 'generated',
    error_message TEXT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES report_schedules(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_report_export_user (user_id, generated_at),
    INDEX idx_report_export_schedule (schedule_id)
) ENGINE=InnoDB;
