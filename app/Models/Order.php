<?php

namespace Alang\DesafioIpag\Models;

use PDO;

/**
 * Model que lida com a tabela de pedidos (orders).
 */
class Order
{
    private PDO $pdo;

    // Variável para armazenar a última query executada (para debug)
    private string $lastQuery = '';

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Método para obter a última query executada (para debug).
     * 
     * @return string
     */
    public function getLastQuery(): string
    {
        return $this->lastQuery;
    }


    /**
     * @param int $id
     * 
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param string $orderNumber
     * 
     * @return array|null
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param array $data
     * 
     * @return string
     */
    public function create(array $data): string
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (customer_id, order_number, total_value, status)
            VALUES (:customer_id, :order_number, :total_value, :status)
        ");

        $stmt->execute([
            ':customer_id'  => $data['customer_id'],
            ':order_number' => $data['order_number'],
            ':total_value'  => $data['total_value'],
            ':status'       => $data['status'] ?? 'PENDING',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param string $orderNumber
     * @param string $newStatus
     * 
     * @return bool
     */
    public function updateStatus(string $orderNumber, string $newStatus): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE orders SET status = :status WHERE order_number = :order_number
        ");

        return $stmt->execute([
            ':status'       => $newStatus,
            ':order_number' => $orderNumber,
        ]);
    }

    /**
     * @param array $filters
     * 
     * @return array
     */
    public function all(array $filters = []): array
    {
        //Uso de 1=1 para facilitar a concatenação de condições
        $sql = "SELECT * FROM orders WHERE 1=1";
        $params = [];

        // Filtros possíveis: id, customer_id, order_number, status, date_from, date_to, min_value, max_value

        if (!empty($filters['id'])) {
            $sql .= " AND id = :id";
            $params[':id'] = $filters['id'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND customer_id = :customer_id";
            $params[':customer_id'] = $filters['customer_id'];
        }

        if (!empty($filters['order_number'])) {
            $sql .= " AND order_number = :order_number";
            $params[':order_number'] = $filters['order_number'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['min_value'])) {
            $sql .= " AND total_value >= :min_value";
            $params[':min_value'] = $filters['min_value'];
        }

        if (!empty($filters['max_value'])) {
            $sql .= " AND total_value <= :max_value";
            $params[':max_value'] = $filters['max_value'];
        }

        $sql .= " ORDER BY created_at DESC";

        //$this->lastQuery = $sql;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     */
    public function summary(): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_orders,
                SUM(total_value) AS total_value,
                AVG(total_value) AS average_order_value
            FROM orders
        ";
        $stmt = $this->pdo->query($sql);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Quebra por status
        $statusSql = "
            SELECT status, COUNT(*) AS count
            FROM orders
            GROUP BY status
        ";
        $statusStmt = $this->pdo->query($statusSql);
        $statusBreakdown = [];
        foreach ($statusStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $statusBreakdown[$row['status']] = (int)$row['count'];
        }

        return array_merge($summary, ['status_breakdown' => $statusBreakdown]);
    }
}
