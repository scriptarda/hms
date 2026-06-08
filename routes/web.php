<?php
/**
 * Route Definitions
 * HEMS - Healthcare Enterprise Management System
 */

use App\Helpers\Router;

/** @var Router $router */

// ==================== PUBLIC ROUTES ====================
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@doLogin');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotPassword');
$router->post('/forgot-password', 'AuthController@doForgotPassword');
$router->get('/reset-password/{token}', 'AuthController@resetPassword');
$router->post('/reset-password', 'AuthController@doResetPassword');

// Installation
$router->get('/install', 'InstallController@index');
$router->post('/install/step1', 'InstallController@step1');
$router->post('/install/step2', 'InstallController@step2');
$router->post('/install/step3', 'InstallController@step3');
$router->get('/install/finish', 'InstallController@finish');

// QR Code public access
$router->get('/qr/scan', 'AssetController@scanner');
$router->get('/qr/asset/{id}', 'AssetController@qrView');
$router->get('/qr/asset-tag/{tag}', 'AssetController@qrViewByTag');
$router->get('/qr/asset/{id}/report', 'AssetController@reportIssue');
$router->post('/qr/asset/{id}/report', 'AssetController@submitIssue');

// ==================== AUTHENTICATED ROUTES ====================
$router->group(['middleware' => ['AuthMiddleware', 'CsrfMiddleware']], function (Router $router) {

    // Dashboard
    $router->get('/', 'DashboardController@index');
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/dashboard/data', 'DashboardController@getData');

    // Profile
    $router->get('/profile', 'AuthController@profile');
    $router->post('/profile', 'AuthController@updateProfile');
    $router->post('/change-password', 'AuthController@changePassword');

    // Tickets / Incidents
    $router->get('/tickets', 'TicketController@index');
    $router->get('/tickets/create', 'TicketController@create');
    $router->post('/tickets/store', 'TicketController@store');
    $router->get('/tickets/{id}', 'TicketController@show');
    $router->get('/tickets/{id}/edit', 'TicketController@edit');
    $router->post('/tickets/{id}/update', 'TicketController@update');
    $router->post('/tickets/{id}/assign', 'TicketController@assign');
    $router->post('/tickets/{id}/escalate', 'TicketController@escalate');
    $router->post('/tickets/{id}/resolve', 'TicketController@resolve');
    $router->post('/tickets/{id}/close', 'TicketController@close');
    $router->post('/tickets/{id}/reopen', 'TicketController@reopen');
    $router->post('/tickets/{id}/comment', 'TicketController@addComment');
    $router->get('/tickets/data/list', 'TicketController@dataList');

    // Assets
    $router->get('/assets', 'AssetController@index');
    $router->get('/assets/create', 'AssetController@create');
    $router->post('/assets/store', 'AssetController@store');
    $router->get('/assets/qr/labels', 'AssetController@qrLabels');
    $router->get('/assets/data/list', 'AssetController@dataList');
    $router->get('/assets/api/list', 'AssetController@dataList');
    $router->get('/assets/api/warranty', 'AssetController@apiWarranty');
    $router->post('/assets/api/store', 'AssetController@apiStore');
    $router->get('/assets/api/{id}', 'AssetController@apiShow');
    $router->get('/assets/api/{id}/history', 'AssetController@apiHistory');
    $router->get('/assets/api/{id}/qr', 'AssetController@apiQR');
    $router->post('/assets/api/{id}/update', 'AssetController@apiUpdate');
    $router->post('/assets/api/{id}/delete', 'AssetController@apiDelete');
    $router->post('/assets/api/{id}/assign', 'AssetController@apiAssign');
    $router->post('/assets/api/{id}/return', 'AssetController@apiReturn');
    $router->get('/assets/{id}', 'AssetController@show');
    $router->get('/assets/{id}/edit', 'AssetController@edit');
    $router->post('/assets/{id}/update', 'AssetController@update');
    $router->get('/assets/{id}/qr', 'AssetController@generateQR');
    $router->post('/assets/{id}/assign', 'AssetController@assignAsset');
    $router->post('/assets/{id}/return', 'AssetController@returnAsset');
    $router->post('/assets/{id}/delete', 'AssetController@delete');

    // Maintenance
    $router->get('/maintenance', 'MaintenanceController@index');
    $router->get('/maintenance/work-orders', 'MaintenanceController@workOrders');
    $router->get('/maintenance/history', 'MaintenanceController@history');
    $router->get('/maintenance/queue', 'MaintenanceController@queue');
    $router->get('/maintenance/calendar', 'MaintenanceController@calendar');
    $router->get('/maintenance/create', 'MaintenanceController@create');
    $router->post('/maintenance/store', 'MaintenanceController@store');
    $router->get('/maintenance/api/dashboard', 'MaintenanceController@apiDashboard');
    $router->get('/maintenance/api/work-orders', 'MaintenanceController@apiWorkOrders');
    $router->post('/maintenance/api/work-orders', 'MaintenanceController@apiStore');
    $router->get('/maintenance/api/calendar/events', 'MaintenanceController@apiCalendarEvents');
    $router->get('/maintenance/api/history', 'MaintenanceController@apiHistory');
    $router->get('/maintenance/api/queue', 'MaintenanceController@apiQueue');
    $router->get('/maintenance/api/schedules', 'MaintenanceController@apiSchedules');
    $router->post('/maintenance/api/schedules', 'MaintenanceController@apiStoreSchedule');
    $router->post('/maintenance/api/schedules/{id}/generate', 'MaintenanceController@apiGenerateSchedule');
    $router->get('/maintenance/api/work-orders/{id}', 'MaintenanceController@apiShow');
    $router->post('/maintenance/api/work-orders/{id}/update', 'MaintenanceController@apiUpdate');
    $router->post('/maintenance/api/work-orders/{id}/start', 'MaintenanceController@apiStart');
    $router->post('/maintenance/api/work-orders/{id}/complete', 'MaintenanceController@apiComplete');
    $router->post('/maintenance/api/work-orders/{id}/cancel', 'MaintenanceController@apiCancel');
    $router->get('/maintenance/data/events', 'MaintenanceController@events');
    $router->get('/maintenance/{id}', 'MaintenanceController@show');
    $router->get('/maintenance/{id}/edit', 'MaintenanceController@edit');
    $router->post('/maintenance/{id}/update', 'MaintenanceController@update');
    $router->post('/maintenance/{id}/start', 'MaintenanceController@start');
    $router->post('/maintenance/{id}/complete', 'MaintenanceController@complete');
    $router->post('/maintenance/{id}/cancel', 'MaintenanceController@cancel');

    // Inventory
    $router->get('/inventory', 'InventoryController@index');
    $router->get('/inventory/items', 'InventoryController@items');
    $router->get('/inventory/transactions', 'InventoryController@transactions');
    $router->get('/inventory/reorder-alerts', 'InventoryController@reorderAlerts');
    $router->get('/inventory/suppliers', 'InventoryController@suppliers');
    $router->post('/inventory/suppliers/store', 'InventoryController@storeSupplier');
    $router->get('/inventory/purchase-requests', 'InventoryController@purchaseRequests');
    $router->post('/inventory/purchase-requests/store', 'InventoryController@storePurchaseRequest');
    $router->post('/inventory/purchase-requests/{id}/status', 'InventoryController@updatePurchaseRequestStatus');
    $router->get('/inventory/api/dashboard', 'InventoryController@apiDashboard');
    $router->get('/inventory/api/items', 'InventoryController@apiItems');
    $router->get('/inventory/api/transactions', 'InventoryController@apiTransactions');
    $router->get('/inventory/api/reorder-alerts', 'InventoryController@apiReorderAlerts');
    $router->get('/inventory/api/suppliers', 'InventoryController@apiSuppliers');
    $router->get('/inventory/api/purchase-requests', 'InventoryController@apiPurchaseRequests');
    $router->get('/inventory/api/items/{id}', 'InventoryController@apiShow');
    $router->get('/inventory/data/list', 'InventoryController@dataList');
    $router->get('/inventory/create', 'InventoryController@create');
    $router->post('/inventory/store', 'InventoryController@store');
    $router->get('/inventory/{id}', 'InventoryController@show');
    $router->get('/inventory/{id}/edit', 'InventoryController@edit');
    $router->post('/inventory/{id}/update', 'InventoryController@update');
    $router->post('/inventory/{id}/transaction', 'InventoryController@addTransaction');

    // Service Requests
    $router->get('/service-requests', 'ServiceRequestController@index');
    $router->get('/service-requests/catalog', 'ServiceRequestController@catalog');
    $router->get('/service-requests/api/catalog', 'ServiceRequestController@catalogData');
    $router->get('/service-requests/api/forms/{type}', 'ServiceRequestController@formSchema');
    $router->get('/service-requests/api/list', 'ServiceRequestController@dataList');
    $router->get('/service-requests/api/{id}/tracking', 'ServiceRequestController@tracking');
    $router->get('/service-requests/create/{type}', 'ServiceRequestController@create');
    $router->post('/service-requests/store', 'ServiceRequestController@store');
    $router->get('/service-requests/{id}', 'ServiceRequestController@show');
    $router->post('/service-requests/{id}/approve', 'ServiceRequestController@approve');
    $router->post('/service-requests/{id}/reject', 'ServiceRequestController@reject');
    $router->post('/service-requests/{id}/fulfillment/start', 'ServiceRequestController@startFulfillment');
    $router->post('/service-requests/{id}/fulfillment/complete', 'ServiceRequestController@completeFulfillment');
    $router->post('/service-requests/{id}/cancel', 'ServiceRequestController@cancel');

    // SLA Management
    $router->get('/sla', 'SlaController@index');
    $router->post('/sla/rules/store', 'SlaController@storeRule');
    $router->post('/sla/rules/{id}/update', 'SlaController@updateRule');
    $router->post('/sla/monitor/run', 'SlaController@runMonitor');
    $router->get('/sla/api/metrics', 'SlaController@apiMetrics');
    $router->get('/sla/api/rules', 'SlaController@apiRules');
    $router->post('/sla/api/monitor/run', 'SlaController@apiRunMonitor');

    // Knowledge Base
    $router->get('/knowledge', 'KnowledgeController@index');
    $router->get('/knowledge/create', 'KnowledgeController@create');
    $router->post('/knowledge/store', 'KnowledgeController@store');
    $router->get('/knowledge/categories', 'KnowledgeController@categories');
    $router->post('/knowledge/categories/store', 'KnowledgeController@storeCategory');
    $router->post('/knowledge/categories/{id}/update', 'KnowledgeController@updateCategory');
    $router->get('/knowledge/faq', 'KnowledgeController@faq');
    $router->get('/knowledge/api/search', 'KnowledgeController@apiSearch');
    $router->get('/knowledge/api/categories', 'KnowledgeController@apiCategories');
    $router->get('/knowledge/search', 'KnowledgeController@search');
    $router->get('/knowledge/{slug}', 'KnowledgeController@article');
    $router->get('/knowledge/{slug}/edit', 'KnowledgeController@edit');
    $router->post('/knowledge/{slug}/update', 'KnowledgeController@update');

    // Reports
    $router->get('/reports', 'ReportController@index');
    $router->get('/reports/tickets', 'ReportController@tickets');
    $router->get('/reports/assets', 'ReportController@assets');
    $router->get('/reports/maintenance', 'ReportController@maintenance');
    $router->get('/reports/inventory', 'ReportController@inventory');
    $router->get('/reports/sla', 'ReportController@sla');
    $router->get('/reports/user-activity', 'ReportController@userActivity');
    $router->get('/reports/api/{type}', 'ReportController@api');
    $router->post('/reports/schedules/store', 'ReportController@storeSchedule');
    $router->post('/reports/schedules/{id}/toggle', 'ReportController@toggleSchedule');
    $router->get('/reports/export/{type}', 'ReportController@export');

    // Notifications
    $router->get('/notifications', 'NotificationController@index');
    $router->get('/notifications/preferences', 'NotificationController@preferences');
    $router->post('/notifications/preferences', 'NotificationController@updatePreferences');
    $router->post('/notifications/read-all', 'NotificationController@markAllRead');
    $router->post('/notifications/push/subscribe', 'NotificationController@subscribePush');
    $router->get('/notifications/unread', 'NotificationController@getUnread');
    $router->post('/notifications/{id}/read', 'NotificationController@markRead');
    $router->post('/notifications/{id}/unread', 'NotificationController@markUnread');

    // Admin
    $router->get('/admin/users', 'AdminController@users');
    $router->get('/admin/users/create', 'AdminController@createUser');
    $router->post('/admin/users/store', 'AdminController@storeUser');
    $router->get('/admin/users/{id}/edit', 'AdminController@editUser');
    $router->post('/admin/users/{id}/update', 'AdminController@updateUser');
    $router->get('/admin/roles', 'AdminController@roles');
    $router->get('/admin/roles/{id}/edit', 'AdminController@editRole');
    $router->post('/admin/roles/{id}/update', 'AdminController@updateRole');
    $router->get('/admin/departments', 'AdminController@departments');
    $router->get('/admin/departments/create', 'AdminController@createDepartment');
    $router->post('/admin/departments/store', 'AdminController@storeDepartment');
    $router->get('/admin/settings', 'AdminController@settings');
    $router->post('/admin/settings', 'AdminController@updateSettings');
    $router->get('/admin/audit-logs', 'AdminController@auditLogs');
});
