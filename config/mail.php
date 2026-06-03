<?php
/**
 * Mail Configuration
 * HEMS - Healthcare Enterprise Management System
 */

return [
    'driver'     => 'log', // 'smtp' or 'log' (log writes to storage/logs)
    'host'       => 'smtp.example.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => '',
    'password'   => '',
    'from' => [
        'address' => 'noreply@healthcentral.org',
        'name'    => 'HEMS Core',
    ],
];
