<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;

header('Content-Type: application/json');

$status = [
    'worker' => 'ok',
    'database' => 'unknown',
    'rabbitmq' => 'unknown',
    'queue' => 'unknown'
];

// Verifica conexão com banco de dados
try {
    $pdo = require_once __DIR__ . '/../config/database.php';
    $pdo->query('SELECT 1');
    $status['database'] = 'ok';
} catch (\Exception $e) {
    $status['database'] = 'error';
    $status['database_error'] = $e->getMessage();
}

// Verifica conexão com RabbitMQ e existência da fila
try {
    $connection = new AMQPStreamConnection(
        $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
        $_ENV['RABBITMQ_PORT'] ?? 5672,
        $_ENV['RABBITMQ_USER'] ?? 'guest',
        $_ENV['RABBITMQ_PASS'] ?? 'guest'
    );
    $channel = $connection->channel();

    // Verifica se a fila existe (passivo = true)
    list($queueName, $messageCount, $consumerCount) = $channel->queue_declare(
        'order_status_updates',
        true, // passive
        true, // durable
        false,
        false
    );

    $status['rabbitmq'] = 'ok';
    $status['queue'] = 'ok';
    $status['messages_pending'] = $messageCount;
    $status['consumers'] = $consumerCount;

    $channel->close();
    $connection->close();
} catch (AMQPIOException $e) {
    $status['rabbitmq'] = 'error';
    $status['rabbitmq_error'] = 'Connection failed';
} catch (\Exception $e) {
    $status['queue'] = 'error';
    $status['queue_error'] = $e->getMessage();
}

echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
