<?php
/**
 * Application Configuration
 * HEMS - Healthcare Enterprise Management System
 */

return [
    // Application
    'name'        => 'HEMS Core',
    'full_name'   => 'Healthcare Enterprise Management System',
    'version'     => '1.0.0',
    'url'         => 'http://localhost/hms',
    'timezone'    => 'UTC',
    'locale'      => 'en',
    'debug'       => true,           // Set to false in production
    'environment' => 'development',  // development, staging, production

    // Session
    'session' => [
        'name'     => 'HEMS_SESSION',
        'lifetime' => 7200, // 2 hours
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // Set to true with HTTPS
        'httponly'  => true,
        'samesite'  => 'Strict',
    ],

    // File uploads
    'uploads' => [
        'max_size'        => 10485760, // 10MB
        'allowed_types'   => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip'],
        'path'            => __DIR__ . '/../public/uploads',
        'url'             => '/uploads',
    ],

    // Pagination
    'per_page' => 25,

    // Login throttling
    'login_throttle' => [
        'max_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
    ],

    // SLA defaults (in minutes)
    'sla_defaults' => [
        'critical' => 60,
        'high'     => 240,
        'medium'   => 480,
        'low'      => 1440,
    ],

    // Notification polling interval (ms)
    'notification_poll_interval' => 30000,

    // Socket.IO realtime bridge
    'realtime' => [
        'enabled' => true,
        'socket_url' => 'http://localhost:3001',
    ],

    // Paths
    'paths' => [
        'storage' => __DIR__ . '/../storage',
        'logs'    => __DIR__ . '/../storage/logs',
        'views'   => __DIR__ . '/../app/Views',
    ],
];
