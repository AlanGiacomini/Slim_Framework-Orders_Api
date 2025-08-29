<?php

namespace Alang\DesafioIpag\Models;

use PDO;

class OrderItem
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $orderId, array $items): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_items (order_id, product_name, quantity, unit_value)
            VALUES (:order_id, :product_name, :quantity, :unit_value)
        ");

        foreach ($items as $item) {
            $stmt->execute([
                ':order_id'     => $orderId,
                ':product_name' => $item['product_name'],
                ':quantity'     => $item['quantity'],
                ':unit_value'   => $item['unit_value'],
            ]);
        }
    }

    public function findByOrderId(int $orderId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *, quantity * unit_value AS total_value
            FROM order_items
            WHERE order_id = :order_id
        ");

        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}