<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Alang\DesafioIpag\Models\Order;
use Alang\DesafioIpag\Models\NotificationLog;
use Alang\DesafioIpag\Models\Customer;


require_once __DIR__ . '/../vendor/autoload.php';

$pdo = require_once __DIR__ . '/../config/database.php';

$orderModel = new Order($pdo);
$logModel = new NotificationLog($pdo);
$customerModel = new Customer($pdo);

$connection = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
    $_ENV['RABBITMQ_PORT'] ?? 5672,
    $_ENV['RABBITMQ_USER'] ?? 'guest',
    $_ENV['RABBITMQ_PASS'] ?? 'guest'
);
$channel = $connection->channel();



$channel->queue_declare('order_status_updates', false, true, false, false);

//Função que processa os dados da fila
$callback = function (AMQPMessage $msg) use ($orderModel, $logModel, $customerModel) {
    $data = json_decode($msg->body, true);

    if (!isset($data['order_id'], $data['old_status'], $data['new_status'], $data['timestamp'], $data['user_id'])) {
        echo "[ERROR] Mensagem inválida\n";
        return;
    }

    $order = $orderModel->findByOrderNumber($data['order_id']);
    if (!$order) {
        echo "[ERROR] Pedido não encontrado: {$data['order_id']}\n";
        return;
    }

    if ($order['status'] === 'DELIVERED') {
        echo "[ERROR] Pedido já entregue. Não pode ser alterado.\n";
        return;
    }

    $updated = $orderModel->updateStatus($data['order_id'], $data['new_status']);
    if (!$updated) {
        echo "[ERROR] Falha ao atualizar status\n";
        return;
    }

    $customer = $customerModel->findById($order['customer_id']);

    if (!$customer) {
        echo "[ERROR] Cliente não encontrado para o pedido {$data['order_id']}\n";
        return;
    }

    echo "Adicionando log de atualização de pedido:" . $order['id'];

    //Registrar log de atualização de status
    $logModel->create([
        'order_id'    => $order['id'],
        'old_status'  => $data['old_status'],
        'new_status'  => $data['new_status'],
        'message'     => "Pedido {$data['order_id']} alterado de {$data['old_status']} para {$data['new_status']}. Nota: " . ($data['notes'] ?? 'Status atualizado pelo Worker'),
        'level'       => 'INFO',
        'context'     => [
            'order_id'    => $order['id'],
            'user_id' => $data['user_id'],
            'notes'   => $data['notes'] ?? null,
            'old_status'  => $data['old_status'],
            'new_status'  => $data['new_status'],
            'created_at'  => $data['timestamp']
        ],
        'created_at'  => $data['timestamp']
    ]);

    //Simular envio de notificação com log estruturado
    echo "Simulação de envio de notificação." . "\n";
    echo json_encode([
        'timestamp' => $data['timestamp'],
        'event'     => 'notification_sent',
        'order_id'  => $data['order_id'],
        'email'     => $customer['email'],
        'status'    => $data['new_status']
    ]) . "\n";

    //Registrar log de notificação enviada
    $logModel->create([
        'order_id'    => $order['id'],
        'old_status'  => $data['old_status'],
        'new_status'  => $data['new_status'],
        'message'     => "Notificação enviada para {$customer['email']}",
        'level'       => 'INFO',
        'context'     => [
            'order_id'    => $order['id'],
            'user_id' => $data['user_id'],
            'email'   => $customer['email'],
            'status'  => $data['new_status']
        ],
        'created_at'  => $data['timestamp']
    ]);
};

$channel->basic_consume('order_status_updates', '', false, true, false, false, $callback);

//Manter o worker rodando
while (true) {
    try {
        $channel->wait();
    } catch (\Exception $e) {
        echo "[ERROR] Worker falhou: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
