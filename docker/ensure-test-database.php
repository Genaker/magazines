<?php

$driver = getenv('DB_CONNECTION') ?: 'mysql';

if (! in_array($driver, ['mysql', 'mariadb'], true)) {
    exit(0);
}

$host = getenv('DB_HOST') ?: 'mariadb';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'secret';
$database = getenv('DB_DATABASE') ?: 'magazines_test';

$pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
