-- SLA Management upgrade
-- Run once on an existing HEMS database before using the SLA monitor.

ALTER TABLE tickets
    ADD COLUMN sla_rule_id BIGINT UNSIGNED NULL AFTER asset_id,
    ADD COLUMN response_due_at TIMESTAMP NULL AFTER sla_rule_id,
    ADD COLUMN responded_at TIMESTAMP NULL AFTER response_due_at,
    ADD COLUMN response_sla_status ENUM('on_track','warning','breached') DEFAULT 'on_track' AFTER responded_at,
    ADD COLUMN resolution_due_at TIMESTAMP NULL AFTER response_sla_status,
    ADD COLUMN resolution_sla_status ENUM('on_track','warning','breached') DEFAULT 'on_track' AFTER resolution_due_at,
    ADD COLUMN last_sla_checked_at TIMESTAMP NULL AFTER sla_status,
    ADD COLUMN escalation_level INT DEFAULT 0 AFTER last_sla_checked_at,
    ADD COLUMN last_escalated_at TIMESTAMP NULL AFTER escalation_level,
    ADD INDEX idx_ticket_response_sla (response_sla_status, response_due_at),
    ADD INDEX idx_ticket_resolution_sla (resolution_sla_status, resolution_due_at),
    ADD INDEX idx_ticket_sla_rule (sla_rule_id);

ALTER TABLE sla_rules
    ADD COLUMN warning_threshold INT DEFAULT 80 COMMENT 'percentage of target elapsed before warning' AFTER escalation_time,
    ADD COLUMN escalation_role VARCHAR(50) DEFAULT 'manager' AFTER warning_threshold,
    ADD COLUMN notify_roles VARCHAR(255) DEFAULT 'technician,manager,administrator' AFTER escalation_role,
    ADD COLUMN business_hours_only TINYINT(1) DEFAULT 0 AFTER notify_roles,
    ADD COLUMN sort_order INT DEFAULT 0 AFTER business_hours_only,
    ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at,
    ADD INDEX idx_sla_priority_active (priority, is_active, deleted_at);

UPDATE sla_rules
SET resolution_time = 120,
    warning_threshold = COALESCE(warning_threshold, 80),
    escalation_role = COALESCE(escalation_role, 'manager'),
    notify_roles = COALESCE(notify_roles, 'technician,manager,administrator')
WHERE priority = 'critical';

UPDATE sla_rules
SET warning_threshold = COALESCE(warning_threshold, 80),
    escalation_role = COALESCE(escalation_role, 'manager'),
    notify_roles = COALESCE(notify_roles, 'technician,manager,administrator')
WHERE priority <> 'critical';

CREATE TABLE IF NOT EXISTS sla_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    rule_id BIGINT UNSIGNED NULL,
    event_type ENUM('response_warning','response_breached','resolution_warning','resolution_breached','escalated','recalculated') NOT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30),
    escalation_level INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (rule_id) REFERENCES sla_rules(id) ON DELETE SET NULL,
    INDEX idx_sla_event_ticket (ticket_id, created_at),
    INDEX idx_sla_event_type (event_type, created_at)
) ENGINE=InnoDB;

ALTER TABLE tickets
    ADD CONSTRAINT fk_ticket_sla_rule FOREIGN KEY (sla_rule_id) REFERENCES sla_rules(id) ON DELETE SET NULL;

UPDATE tickets t
JOIN sla_rules s ON s.priority = t.priority AND s.is_active = 1 AND s.deleted_at IS NULL
SET t.sla_rule_id = COALESCE(t.sla_rule_id, s.id),
    t.response_due_at = COALESCE(t.response_due_at, DATE_ADD(t.created_at, INTERVAL s.response_time MINUTE)),
    t.resolution_due_at = COALESCE(t.resolution_due_at, t.sla_due_at, DATE_ADD(t.created_at, INTERVAL s.resolution_time MINUTE)),
    t.sla_due_at = COALESCE(t.sla_due_at, DATE_ADD(t.created_at, INTERVAL s.resolution_time MINUTE))
WHERE t.deleted_at IS NULL;
