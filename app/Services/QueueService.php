<?php

namespace Alang\DesafioIpag\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueService
{
    private $connection;
    private $channel;

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
        $this->channel = $this->connection->channel();
    }

    public function publish(string $queueName, array $message): void
    {
        $this->channel->queue_declare($queueName, false, true, false, false);

        $payload = json_encode($message);
        $msg = new AMQPMessage($payload, ['delivery_mode' => 2]);

        $this->channel->basic_publish($msg, '', $queueName);
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}