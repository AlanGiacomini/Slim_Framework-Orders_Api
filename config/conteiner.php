<?php

use DI\Container;
use Psr\Container\ContainerInterface;
use Predis\Client as RedisClient;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Alang\DesafioIpag\Models\Order;
use Alang\DesafioIpag\Models\Customer;
use Alang\DesafioIpag\Models\OrderItem;
use Alang\DesafioIpag\Models\NotificationLog;
use Alang\DesafioIpag\Services\OrderService;
use Alang\DesafioIpag\Services\QueueService;
use Alang\DesafioIpag\Controllers\OrderController;
use Alang\DesafioIpag\Controllers\HealthController;
use Alang\DesafioIpag\Utils\Validator;

$container = new Container();

// Configura a dependência do PDO.
$container->set(PDO::class, function () {
    return require_once __DIR__ . '/../config/database.php';
});

// Os Models precisam do PDO para se conectar ao banco de dados.
$container->set(Order::class, function (ContainerInterface $c) {
    return new Order($c->get(PDO::class));
});
$container->set(Customer::class, function (ContainerInterface $c) {
    return new Customer($c->get(PDO::class));
});
$container->set(OrderItem::class, function (ContainerInterface $c) {
    return new OrderItem($c->get(PDO::class));
});
$container->set(NotificationLog::class, function (ContainerInterface $c) {
    return new NotificationLog($c->get(PDO::class));
});

// Utilitário de validação de dados
$container->set(Validator::class, function (){
    return new Validator();
});

// O OrderService precisa dos Models e QueueService para executar a lógica de negócio.
$container->set(OrderService::class, function (ContainerInterface $c) {
    return new OrderService(
        $c->get(Order::class),
        $c->get(Customer::class),
        $c->get(OrderItem::class),
        $c->get(QueueService::class)
    );
});

// O OrderController precisa do OrderService para executar as ações de negócio.
$container->set(OrderController::class, function (ContainerInterface $c) {
    return new OrderController(
        $c->get(OrderService::class)
    );
});

// Definindo o Redisclient
$container->set(RedisClient::class, function () {
    return new RedisClient([
        'scheme' => 'tcp',
        'host'   => $_ENV['REDIS_HOST'] ?? 'redis',
        'port'   => $_ENV['REDIS_PORT'] ?? 6379,
    ]);
});

// Definindo o RabbitMQ
$container->set(AMQPStreamConnection::class, function () {
    return new AMQPStreamConnection(
        $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
        $_ENV['RABBITMQ_PORT'] ?? 5672,
        $_ENV['RABBITMQ_USER'] ?? 'guest',
        $_ENV['RABBITMQ_PASS'] ?? 'guest'
    );
});

// O QueueService precisa AMQPStreamConnection.
$container->set(QueueService::class, function (ContainerInterface $c) {
    return new QueueService(
        $c->get(AMQPStreamConnection::class)
    );
});

// Definindo o HealthController que usa o PDO, o Redis e o RabbitMQ
$container->set(HealthController::class, function (ContainerInterface $c) {
    return new HealthController(
        $c->get(PDO::class),
        $c->get(RedisClient::class),
        $c->get(AMQPStreamConnection::class)
    );
});


return $container; // Retorna o contêiner configurado.