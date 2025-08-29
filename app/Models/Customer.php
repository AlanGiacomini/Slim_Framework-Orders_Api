<?php

namespace Alang\DesafioIpag\Models;

use PDO;

class Customer
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByDocument(string $document): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE document = ?");
        $stmt->execute([$document]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO customers (name, document, email, phone)
            VALUES (:name, :document, :email, :phone)
        ");

        $stmt->execute([
            ':name'     => $data['name'],
            ':document' => $data['document'],
            ':email'    => $data['email'] ?? null,
            ':phone'    => $data['phone'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
}