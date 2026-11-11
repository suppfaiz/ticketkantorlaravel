<?php

// Patch Laravel framework base database config for PHP 8.5 compatibility
$target = __DIR__ . '/../vendor/laravel/framework/config/database.php';

if (file_exists($target)) {
    $content = file_get_contents($target);
    if (str_contains($content, 'PDO::MYSQL_ATTR_SSL_CA')) {
        $replaced = str_replace(
            'PDO::MYSQL_ATTR_SSL_CA',
            "(defined('Pdo\\Mysql::ATTR_SSL_CA') ? constant('Pdo\\Mysql::ATTR_SSL_CA') : PDO::MYSQL_ATTR_SSL_CA)",
            $content
        );
        file_put_contents($target, $replaced);
    }
}
