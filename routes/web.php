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
$router->get('/qr/asset/{id}', 'AssetController@qrView');

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
    $router->get('/assets/{id}', 'AssetController@show');
    $router->get('/assets/{id}/edit', 'AssetController@edit');
    $router->post('/assets/{id}/update', 'AssetController@update');
    $router->get('/assets/{id}/qr', 'AssetController@generateQR');
    $router->post('/assets/{id}/assign', 'AssetController@assignAsset');
    $router->get('/assets/data/list', 'AssetController@dataList');

    // Maintenance
    $router->get('/maintenance', 'MaintenanceController@index');
    $router->get('/maintenance/calendar', 'MaintenanceController@calendar');
    $router->get('/maintenance/create', 'MaintenanceController@create');
    $router->post('/maintenance/store', 'MaintenanceController@store');
    $router->get('/maintenance/{id}', 'MaintenanceController@show');
    $router->get('/maintenance/{id}/edit', 'MaintenanceController@edit');
    $router->post('/maintenance/{id}/update', 'MaintenanceController@update');
    $router->post('/maintenance/{id}/complete', 'MaintenanceController@complete');
    $router->get('/maintenance/data/events', 'MaintenanceController@events');

    // Inventory
    $router->get('/inventory', 'InventoryController@index');
    $router->get('/inventory/create', 'InventoryController@create');
    $router->post('/inventory/store', 'InventoryController@store');
    $router->get('/inventory/{id}', 'InventoryController@show');
    $router->get('/inventory/{id}/edit', 'InventoryController@edit');
    $router->post('/inventory/{id}/update', 'InventoryController@update');
    $router->post('/inventory/{id}/transaction', 'InventoryController@addTransaction');
    $router->get('/inventory/data/list', 'InventoryController@dataList');

    // Service Requests
    $router->get('/service-requests', 'ServiceRequestController@index');
    $router->get('/service-requests/catalog', 'ServiceRequestController@catalog');
    $router->get('/service-requests/create/{type}', 'ServiceRequestController@create');
    $router->post('/service-requests/store', 'ServiceRequestController@store');
    $router->get('/service-requests/{id}', 'ServiceRequestController@show');
    $router->post('/service-requests/{id}/approve', 'ServiceRequestController@approve');
    $router->post('/service-requests/{id}/reject', 'ServiceRequestController@reject');

    // Knowledge Base
    $router->get('/knowledge', 'KnowledgeController@index');
    $router->get('/knowledge/create', 'KnowledgeController@create');
    $router->post('/knowledge/store', 'KnowledgeController@store');
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
    $router->get('/reports/export/{type}', 'ReportController@export');

    // Notifications
    $router->get('/notifications', 'NotificationController@index');
    $router->post('/notifications/{id}/read', 'NotificationController@markRead');
    $router->post('/notifications/read-all', 'NotificationController@markAllRead');
    $router->get('/notifications/unread', 'NotificationController@getUnread');

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
