<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'isabella_salon',
        'user' => 'USUARIO_MYSQL',
        'password' => 'CONTRASENA_MYSQL',
        'charset' => 'utf8mb4',
    ],
    'debug' => false,
    'timezone' => 'America/Lima',
    'whatsapp' => '51999999999',
    'minimum_booking_notice_hours' => 4,
    'admin_idle_minutes' => 120,
    'max_video_mb' => 50,
    'upload_dir' => __DIR__ . '/app/storage/uploads',
];