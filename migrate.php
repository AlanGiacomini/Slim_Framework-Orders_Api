<?php

$pdo = require __DIR__ . '/config/database.php';

$dir = __DIR__ . '/migrations';
$files = scandir($dir);

// Filtra arquivos PHP e ordena
$migrations = array_filter($files, fn($f) => pathinfo($f, PATHINFO_EXTENSION) === 'php');
sort($migrations);

echo "Executando migrations...\n";

foreach ($migrations as $migration) {
    echo "→ Executando: $migration\n";
    require $dir . '/' . $migration;
}

echo "✅ Todas as migrations foram executadas.\n";