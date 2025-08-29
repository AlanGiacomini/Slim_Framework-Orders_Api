<?php

namespace Alang\DesafioIpag\Services;

use Alang\DesafioIpag\Models\Order;
use Alang\DesafioIpag\Models\OrderItem;
use Alang\DesafioIpag\Models\Customer;
use Alang\DesafioIpag\Services\QueueService;
use PDO;

/**
 * Service que lida com a lógica de negócios relacionada a pedidos (orders).
 */
class OrderService
{
    private Order $orderModel;
    private Customer $customerModel;
    private OrderItem $orderItemModel;
    private QueueService $queueService;

    /**
     * @param Order $orderModel
     * @param Customer $customerModel
     * @param OrderItem $orderItemModel
     * @param QueueService $queueService
     */
    public function __construct(
        Order $orderModel,
        Customer $customerModel,
        OrderItem $orderItemModel,
        QueueService $queueService
    ) {
        $this->orderModel = $orderModel;
        $this->customerModel = $customerModel;
        $this->orderItemModel = $orderItemModel;
        $this->queueService = $queueService;
    }

    /**
     * Método que consulta ou cadastra um cliente
     * 
     * @param array $data
     * 
     * @return array
     */
    private function resolveCustomer(array $data): array
    {
        if (!empty($data['id'])) {
            $customer = $this->customerModel->findById($data['id']);
            if ($customer) return $customer;
        }

        $existing = $this->customerModel->findByDocument($data['document']);
        if ($existing) return $existing;

        $id = $this->customerModel->create($data);
        return array_merge(['id' => $id], $data);
    }


    /**
     * Método que verifica se a transição de status é válida
     * Transições permitidas: 
     * PENDING → WAITING_PAYMENT → PAID → PROCESSING → SHIPPED → DELIVERED
     *    ↓              ↓          ↓         ↓
     * CANCELED    CANCELED    CANCELED   CANCELED
     * 
     * @param string $from
     * @param string $to
     * 
     * @return bool
     */
    private function isValidTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'PENDING'         => ['WAITING_PAYMENT', 'CANCELED'],
            'WAITING_PAYMENT' => ['PAID', 'CANCELED'],
            'PAID'            => ['PROCESSING', 'CANCELED'],
            'PROCESSING'      => ['SHIPPED', 'CANCELED'],
            'SHIPPED'         => ['DELIVERED', 'CANCELED'],
            'DELIVERED'       => [], // não pode mudar
            'CANCELED'        => []  // não pode mudar
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Método para criar um novo pedido e cliente se necessário.
     * 
     * @param array $data
     * 
     * @return array
     */
    public function createOrder(array $data): array
    {
        $customerData = $data['customer'];
        $orderData = $data['order'];

        // 1. Verifica ou cria cliente
        $customer = $this->resolveCustomer($customerData);

        // 2. Gera número único
        $orderNumber = 'ORD-' . uniqid();

        // 3. Calcula total
        $totalValue = array_reduce($orderData['items'], function ($sum, $item) {
            return $sum + $item['quantity'] * $item['unit_value'];
        }, 0);

        // 4. Cria pedido
        $orderId = $this->orderModel->create([
            'customer_id' => $customer['id'],
            'order_number' => $orderNumber,
            'total_value' => $totalValue,
            'status' => 'PENDING'
        ]);

        // 5. Cria itens
        $this->orderItemModel->create($orderId, $orderData['items']);

        // 6. Monta resposta
        $items = array_map(function ($item) {
            return [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_value' => $item['unit_value'],
                'total_value' => $item['quantity'] * $item['unit_value']
            ];
        }, $orderData['items']);

        return [
            'order_id' => $orderNumber,
            'order_number' => $orderNumber,
            'status' => 'PENDING',
            'total_value' => $totalValue,
            'customer' => $customer,
            'items' => $items,
            'created_at' => date('c')
        ];
    }

    /**
     * Método que retorna a lista de pedidos a partir de filtros opcionais
     * 
     * @param array $filters
     * 
     * @return array
     */
    public function listOrders(array $filters = []): array
    {
        $orders = $this->orderModel->all($filters);
        $result = [];

        foreach ($orders as $order) {
            $customer = $this->customerModel->findById($order['customer_id']);
            $items = $this->orderItemModel->findByOrderId($order['id']);

            // Calcula total por item
            foreach ($items as &$item) {
                $item['total_value'] = $item['quantity'] * $item['unit_value'];
            }

            $result[] = [
                'order_id'    => $order['order_number'],
                'order_number' => $order['order_number'],
                'status'      => $order['status'],
                'total_value' => $order['total_value'],
                'customer'    => $customer,
                'items'       => $items,
                'created_at'  => $order['created_at'],
            ];
        }

        return $result;
    }

    /**
     * Método que retorna os detalhes de um pedido pelo seu order_number
     * 
     * @param string $orderId
     * 
     * @return array|null
     */
    public function getOrderDetailsByNumber(string $orderId): ?array
    {
        $order = $this->orderModel->findByOrderNumber($orderId);
        if (!$order) {
            return null;
        }

        $customer = $this->customerModel->findById($order['customer_id']);
        $items = $this->orderItemModel->findByOrderId($order['id']);

        // Calcula total por item
        foreach ($items as &$item) {
            $item['total_value'] = $item['quantity'] * $item['unit_value'];
        }

        return [
            'order_id'    => $order['order_number'],
            'order_number' => $order['order_number'],
            'status'      => $order['status'],
            'total_value' => $order['total_value'],
            'customer'    => $customer,
            'items'       => $items,
            'created_at'  => $order['created_at'],
        ];
    }

    /**
     * Método responsável por trazer o resumo dos pedidos
     * 
     * @return array
     */
    public function getSummary(): array
    {
        return $this->orderModel->summary();
    }

    public function updateOrderStatus(string $orderNumber, string $newStatus, string $notes): array
    {
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        if (!$order) {
            throw new \Exception('Pedido não encontrado.');
        }

        $oldStatus = $order['status'];

        if (!$this->isValidTransition($oldStatus, $newStatus)) {
            throw new \Exception("Transição inválida de $oldStatus para $newStatus.");
        }

        $message = [
            'order_id'    => $orderNumber,
            'old_status'  => $oldStatus,
            'new_status'  => $newStatus,
            'timestamp'   => date('c'),
            'user_id'     => 'system',
            'notes'       => $notes
        ];

        $this->queueService->publish('order_status_updates', $message);

        return [
            'message'     => 'Status enviado para processamento.',
            'order_id'    => $orderNumber,
            'old_status'  => $oldStatus,
            'new_status'  => $newStatus
        ];
    }
}
