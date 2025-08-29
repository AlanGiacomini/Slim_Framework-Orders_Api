<?php

$host = getenv('DB_HOST') ?: 'ipag-db';
$db   = getenv('DB_NAME') ?: 'ipag';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Define o timezone da sessão MySQL para São Paulo
    $pdo->exec("SET time_zone = '-03:00'");
    return $pdo;
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}