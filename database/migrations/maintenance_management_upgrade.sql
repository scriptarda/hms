-- Maintenance Management upgrade
-- Run once on an existing HEMS database that was installed before the expanded maintenance module.

ALTER TABLE maintenance_tasks
    ADD COLUMN work_order_number VARCHAR(30) UNIQUE AFTER id,
    ADD COLUMN schedule_id BIGINT UNSIGNED NULL AFTER asset_id,
    ADD COLUMN source_ticket_id BIGINT UNSIGNED NULL AFTER schedule_id,
    ADD COLUMN requested_by BIGINT UNSIGNED NULL AFTER assigned_to,
    ADD COLUMN completed_by BIGINT UNSIGNED NULL AFTER requested_by,
    ADD COLUMN downtime_minutes INT DEFAULT 0 AFTER cost,
    ADD COLUMN failure_code VARCHAR(80) AFTER downtime_minutes,
    ADD COLUMN checklist_json JSON NULL AFTER failure_code,
    ADD INDEX idx_maint_schedule (schedule_id),
    ADD INDEX idx_maint_due (due_date, status),
    ADD INDEX idx_maint_assignee (assigned_to, status),
    ADD INDEX idx_maint_type (type, priority);

ALTER TABLE maintenance_schedules
    ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER asset_id,
    ADD COLUMN priority ENUM('critical','high','medium','low') DEFAULT 'medium' AFTER frequency,
    ADD COLUMN estimated_hours DECIMAL(5,2) AFTER priority,
    ADD COLUMN lead_time_days INT DEFAULT 7 AFTER estimated_hours,
    ADD COLUMN last_generated_task_id BIGINT UNSIGNED NULL AFTER assigned_to,
    ADD COLUMN checklist_json JSON NULL AFTER last_generated_task_id,
    ADD INDEX idx_schedule_next_due (next_due, is_active),
    ADD INDEX idx_schedule_asset (asset_id);

ALTER TABLE maintenance_logs
    ADD COLUMN status_from VARCHAR(50) AFTER action,
    ADD COLUMN status_to VARCHAR(50) AFTER status_from,
    ADD COLUMN labor_hours DECIMAL(5,2) AFTER parts_used,
    ADD COLUMN cost DECIMAL(12,2) AFTER labor_hours,
    ADD COLUMN metadata_json JSON NULL AFTER cost;

UPDATE maintenance_tasks
SET work_order_number = CONCAT('WO-', DATE_FORMAT(COALESCE(created_at, NOW()), '%y%m'), '-', LPAD(id + 9400, 5, '0'))
WHERE work_order_number IS NULL;
