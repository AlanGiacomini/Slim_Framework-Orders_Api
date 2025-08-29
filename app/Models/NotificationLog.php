<?php

namespace Alang\DesafioIpag\Models;

use PDO;

class NotificationLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Usado para inserir logs na tabela.
     * Campos permitidos:
     * order_id (int), old_status (VARCHAR(20)), new_status (VARCHAR(20)),
     * message (text), level (varchar(10)), context (json)
     * 
     * @param array $data
     * 
     * @return int
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO notification_logs (
                order_id, old_status, new_status, message, level, context, created_at
            ) VALUES (
                :order_id, :old_status, :new_status, :message, :level, :context, :created_at
            )
        ");

        $now = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $createdAt = $data['created_at'] ?? $now->format('Y-m-d H:i:s');

        $stmt->execute([
            ':order_id'   => $data['order_id'],
            ':old_status' => $data['old_status'] ?? null,
            ':new_status' => $data['new_status'] ?? null,
            ':message'    => $data['message'],
            ':level'      => $data['level'] ?? 'INFO',
            ':context'    => isset($data['context']) ? json_encode($data['context']) : null,
            ':created_at' => $createdAt
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
