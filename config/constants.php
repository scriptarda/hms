<?php
/**
 * System Constants
 * HEMS - Healthcare Enterprise Management System
 */

// Ticket Statuses
define('TICKET_STATUS_NEW', 'new');
define('TICKET_STATUS_ASSIGNED', 'assigned');
define('TICKET_STATUS_IN_PROGRESS', 'in_progress');
define('TICKET_STATUS_WAITING_USER', 'waiting_user');
define('TICKET_STATUS_WAITING_VENDOR', 'waiting_vendor');
define('TICKET_STATUS_RESOLVED', 'resolved');
define('TICKET_STATUS_CLOSED', 'closed');

// Ticket Priorities
define('PRIORITY_CRITICAL', 'critical');
define('PRIORITY_HIGH', 'high');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_LOW', 'low');

// Asset Statuses
define('ASSET_STATUS_ACTIVE', 'active');
define('ASSET_STATUS_INACTIVE', 'inactive');
define('ASSET_STATUS_MAINTENANCE', 'maintenance');
define('ASSET_STATUS_RETIRED', 'retired');
define('ASSET_STATUS_DISPOSED', 'disposed');

// Maintenance Types
define('MAINTENANCE_PREVENTIVE', 'preventive');
define('MAINTENANCE_CORRECTIVE', 'corrective');
define('MAINTENANCE_EMERGENCY', 'emergency');
define('MAINTENANCE_INSPECTION', 'inspection');

// Maintenance Status
define('MAINTENANCE_STATUS_SCHEDULED', 'scheduled');
define('MAINTENANCE_STATUS_IN_PROGRESS', 'in_progress');
define('MAINTENANCE_STATUS_COMPLETED', 'completed');
define('MAINTENANCE_STATUS_OVERDUE', 'overdue');
define('MAINTENANCE_STATUS_CANCELLED', 'cancelled');

// Inventory Transaction Types
define('INVENTORY_IN', 'in');
define('INVENTORY_OUT', 'out');
define('INVENTORY_TRANSFER', 'transfer');
define('INVENTORY_ADJUSTMENT', 'adjustment');
define('INVENTORY_RETURN', 'return');

// Service Request Statuses
define('SR_STATUS_DRAFT', 'draft');
define('SR_STATUS_PENDING', 'pending_approval');
define('SR_STATUS_APPROVED', 'approved');
define('SR_STATUS_REJECTED', 'rejected');
define('SR_STATUS_FULFILLING', 'fulfilling');
define('SR_STATUS_COMPLETED', 'completed');
define('SR_STATUS_CANCELLED', 'cancelled');

// User Statuses
define('USER_ACTIVE', 'active');
define('USER_INACTIVE', 'inactive');
define('USER_LOCKED', 'locked');

// Roles
define('ROLE_STAFF', 'staff');
define('ROLE_NURSE', 'nurse');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_TECHNICIAN', 'technician');
define('ROLE_BIOMEDICAL', 'biomedical_engineer');
define('ROLE_MANAGER', 'manager');
define('ROLE_ADMIN', 'administrator');
define('ROLE_SUPER_ADMIN', 'super_administrator');

// Notification Types
define('NOTIFY_TICKET_ASSIGNED', 'ticket_assigned');
define('NOTIFY_TICKET_UPDATED', 'ticket_updated');
define('NOTIFY_TICKET_RESOLVED', 'ticket_resolved');
define('NOTIFY_TICKET_ESCALATED', 'ticket_escalated');
define('NOTIFY_SLA_WARNING', 'sla_warning');
define('NOTIFY_SLA_BREACHED', 'sla_breached');
define('NOTIFY_APPROVAL_REQUIRED', 'approval_required');
define('NOTIFY_MAINTENANCE_DUE', 'maintenance_due');
define('NOTIFY_LOW_STOCK', 'low_stock');
define('NOTIFY_REPORT_READY', 'report_ready');
define('NOTIFY_REPORT_FAILED', 'report_failed');
define('NOTIFY_SYSTEM', 'system');

// Audit Actions
define('AUDIT_LOGIN', 'login');
define('AUDIT_LOGOUT', 'logout');
define('AUDIT_LOGIN_FAILED', 'login_failed');
define('AUDIT_CREATE', 'create');
define('AUDIT_UPDATE', 'update');
define('AUDIT_DELETE', 'delete');
define('AUDIT_ASSIGN', 'assign');
define('AUDIT_ESCALATE', 'escalate');
define('AUDIT_APPROVE', 'approve');
define('AUDIT_REJECT', 'reject');

// SLA Status
define('SLA_ON_TRACK', 'on_track');
define('SLA_WARNING', 'warning');
define('SLA_BREACHED', 'breached');

// Priority badge colors (for CSS classes)
define('PRIORITY_COLORS', [
    'critical' => 'danger',
    'high'     => 'warning',
    'medium'   => 'primary',
    'low'      => 'secondary',
]);

// Status badge colors
define('STATUS_COLORS', [
    'new'            => 'info',
    'assigned'       => 'primary',
    'in_progress'    => 'warning',
    'waiting_user'   => 'secondary',
    'waiting_vendor' => 'secondary',
    'resolved'       => 'success',
    'closed'         => 'dark',
    'scheduled'      => 'primary',
    'completed'      => 'success',
    'overdue'        => 'danger',
    'cancelled'      => 'dark',
    'active'         => 'success',
    'inactive'       => 'secondary',
    'maintenance'    => 'warning',
    'draft'          => 'secondary',
    'submitted'      => 'primary',
    'approved'       => 'success',
    'ordered'        => 'info',
    'received'       => 'success',
    'rejected'       => 'danger',
    'on_track'       => 'success',
    'warning'        => 'warning',
    'breached'       => 'danger',
    'info'           => 'info',
    'success'        => 'success',
    'danger'         => 'danger',
]);
