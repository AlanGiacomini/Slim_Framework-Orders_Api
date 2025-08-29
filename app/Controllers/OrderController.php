<?php

namespace Alang\DesafioIpag\Controllers;

use Alang\DesafioIpag\Services\OrderService;
use Alang\DesafioIpag\Utils\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller que lida com as requisições relacionadas a pedidos (orders).
 */
class OrderController
{
    private OrderService $orderService;

    /**
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * @OA\Get(
     *     path="/orders",
     *     tags={"Orders"},
     *     summary="Lista pedidos",
     *     description="Lista todos os pedidos com filtros opcionais.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="customer_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="order_number", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"PENDING","WAITING_PAYMENT","PAID","PROCESSING","SHIPPED","DELIVERED","CANCELED"})),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="min_value", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_value", in="query", @OA\Schema(type="number")),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de pedidos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados Inválidos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno"
     *     )
     * )
     */
    public function list(Request $request, Response $response): Response
    {
        $invalidate = [];
        $filters = $request->getQueryParams();

        try {
            //Validação de valores
            $rules = [
                'id' => 'integer|min:1',
                'customer_id' => 'integer|min:1',
                'order_number' => 'order_number',
                'status' => 'in:PENDING,WAITING_PAYMENT,PAID,PROCESSING,SHIPPED,DELIVERED,CANCELED',
                'date_from' => 'datetime',
                'date_to' => 'datetime',
                'min_value' => 'numeric|min:0',
                'max_value' => 'numeric|min:0'
            ];

            //Validação de campos vindos no get
            $invalidate['invalid_keys'] = Validator::validateAllowedKeys($filters, [
                'id',
                'customer_id',
                'order_number',
                'status',
                'date_from',
                'date_to',
                'min_value',
                'max_value'
            ]);

            $invalidate['invalid_values'] = Validator::validateAllFields($filters, $rules);
            $invalidate = array_filter($invalidate);

            if (!empty($invalidate)) {
                throw new \Exception('Erro de validação nos filtros.');
            }
            $orders = $this->orderService->listOrders($filters);

            $response->getBody()->write(json_encode($orders));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {

            $statusCode = !empty($invalidate) ? 422 : 500;
            $error = ['error' => 'Erro ao listar pedidos: ' . $e->getMessage()];
            $error = $error + $invalidate;
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }
    }

    /**
     * @OA\Post(
     *     path="/orders",
     *     tags={"Orders"},
     *     summary="Cria um novo pedido",
     *     description="Cria um pedido e retorna seus dados.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateOrderRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pedido criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados Inválidos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno"
     *     )
     * )
     */
    public function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $invalidate = [];

        try {
            //Validação de campos vindos no get
            $invalidate['invalid_keys'] = Validator::validateAllowedKeys($body, [
                'customer',
                'order'
            ]);
            if (isset($body['customer'])) {
                $invalidate['invalid_customer'] = Validator::validateAllowedKeys($body['customer'], [
                    'id',
                    'name',
                    'document',
                    'email',
                    'phone'
                ]);
            }
            if (isset($body['order'])) {
                $invalidate['invalid_order'] = Validator::validateAllowedKeys($body['order'], [
                    'total_value',
                    'items'
                ]);
            }

            if (isset($body['order']['items'])) {
                $invalidate['invalid_item'] = [];
                foreach ($body['order']['items'] ?? [] as $index => $item) {
                    $invalid = Validator::validateAllowedKeys($item, [
                        'product_name',
                        'quantity',
                        'unit_value'
                    ]);
                    if (!empty($invalid)) {
                        $invalidate['invalid_item'][$index] = $invalid;
                    }
                }
            }

            $rules = [
                // Dados do cliente
                'customer.id' => 'required|integer|min:1',
                'customer.name' => 'required|string',
                'customer.document' => 'required|string|cpf',
                'customer.email' => 'required|email',
                'customer.phone' => 'required|phone',

                // Dados do pedido
                'order.total_value' => 'required|numeric|min:0',

                // Itens do pedido (validação será feita item a item)
                'order.items.*.product_name' => 'required|string',
                'order.items.*.quantity' => 'required|integer|min:1',
                'order.items.*.unit_value' => 'required|numeric|min:0'
            ];

            $invalidate['invalid_values'] = Validator::validateAllFields($body, $rules);

            $invalidate = array_filter($invalidate);

            if (!empty($invalidate)) {
                throw new \Exception('Erro de validação dos parâmetros');
            }

            $order = $this->orderService->createOrder($body);

            $response->getBody()->write(json_encode($order));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $statusCode = !empty($invalidate) ? 422 : 500;
            $error = ['error' => 'Erro ao listar pedidos: ' . $e->getMessage()];
            $error = $error + $invalidate;
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }
    }

    /**
     * @OA\Get(
     *     path="/orders/{order_id}",
     *     tags={"Orders"},
     *     summary="Detalhes de um pedido",
     *     description="Retorna os detalhes de um pedido pelo número.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados do pedido",
     *         @OA\JsonContent(ref="#/components/schemas/Order")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido não encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados Inválidos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno"
     *     )
     * )
     */
    public function get(Request $request, Response $response): Response
    {
        $invalidate = [];
        try {
            $orderNumber["order_number"] = $request->getAttribute('order_id'); // na rota, continua como {order_id}

            $rules = [
                'order_number' => 'required|string|order_number'
            ];

            $invalidate['invalid_order'] = Validator::validateAllFields($orderNumber, $rules);
            $invalidate = array_filter($invalidate);

            if (!empty($invalidate)) {
                throw new \Exception('Erro de validação dos parâmetros');
            }

            $order = $this->orderService->getOrderDetailsByNumber($orderNumber["order_number"]);
            if (!$order) {
                $response->getBody()->write(json_encode(['error' => 'Pedido não encontrado.']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode($order));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $statusCode = !empty($invalidate) ? 422 : 500;
            $error = ['error' => 'Erro ao consultar pedido: ' . $e->getMessage()];
            $error = $error + $invalidate;
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }
    }

    /**
     * @OA\Get(
     *     path="/orders/summary",
     *     tags={"Orders"},
     *     summary="Resumo dos pedidos",
     *     description="Retorna estatísticas dos pedidos.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Resumo estatístico",
     *         @OA\JsonContent(ref="#/components/schemas/OrderSummary")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno"
     *     )
     * )
     */
    public function summary(Request $_request, Response $response): Response
    {

        try {
            $data = $this->orderService->getSummary();
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $error = ['error' => 'Erro ao gerar resumo: ' . $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * @OA\Put(
     *     path="/orders/{order_id}/status",
     *     tags={"Orders"},
     *     summary="Atualiza status do pedido",
     *     description="Atualiza o status do pedido (processamento assíncrono).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", enum={"PENDING","WAITING_PAYMENT","PAID","PROCESSING","SHIPPED","DELIVERED","CANCELED"}),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Status enviado para processamento",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="order_id", type="string"),
     *             @OA\Property(property="old_status", type="string"),
     *             @OA\Property(property="new_status", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados Inválidos"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno"
     *     )
     * )
     */
    public function updateStatus(Request $request, Response $response): Response
    {
        $invalidate = [];
        try {
            $orderNumber["order_number"] = $request->getAttribute('order_id'); // na rota, continua como {order_id}

            $rules = [
                'order_number' => 'required|string|order_number'
            ];

            $invalidate['invalid_order'] = Validator::validateAllFields($orderNumber, $rules);

            $body = $request->getParsedBody();
            $body['status'] = strtoupper(trim($body['status'] ?? null));
            $body['notes'] = trim($body['notes'] ?? null);

            $invalidate['invalid_payload'] = Validator::validateAllowedKeys($body, ['status', 'notes']);

            $rules = [
                'status' => 'required|in:PENDING,WAITING_PAYMENT,PAID,PROCESSING,SHIPPED,DELIVERED,CANCELED',
                'notes' => 'string'
            ];
            $invalidate['invalid_values'] = Validator::validateAllFields($body, $rules);

            $invalidate = array_filter($invalidate);

            if (!empty($invalidate)) {
                throw new \Exception('Erro de validação dos parâmetros');
            }

            $newStatus = $body['status'];
            $notes = $body['notes'];

            // Chama o service para atualizar o status e publicar na fila
            $result = $this->orderService->updateOrderStatus($orderNumber["order_number"], $newStatus, $notes);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(202);
        } catch (\Exception $e) {
            $statusCode = !empty($invalidate) ? 422 : 500;
            $error = ['error' => 'Erro ao atualizar status: ' . $e->getMessage()];
            $error = $error + $invalidate;
            $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }
    }

    /**
     * @OA\Schema(
     *     schema="Order",
     *     type="object",
     *     @OA\Property(property="order_id", type="string"),
     *     @OA\Property(property="order_number", type="string"),
     *     @OA\Property(property="status", type="string"),
     *     @OA\Property(property="total_value", type="number"),
     *     @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *     @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/Item")),
     *     @OA\Property(property="created_at", type="string", format="date-time")
     * )
     */
    private function orderSchema(){}

    /**
     * @OA\Schema(
     *     schema="Customer",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="document", type="string"),
     *     @OA\Property(property="email", type="string"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="created_at", type="string", format="date-time")
     * )
     */
    private function customerSchema(){}
    

    /**
     * @OA\Schema(
     *     schema="Item",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="order_id", type="integer"),
     *     @OA\Property(property="product_name", type="string"),
     *     @OA\Property(property="quantity", type="integer"),
     *     @OA\Property(property="unit_value", type="number"),
     *     @OA\Property(property="total_value", type="number")
     * )
     */
    private function itemSchema(){}
    

    /**
     * @OA\Schema(
     *     schema="CreateOrderRequest",
     *     type="object",
     *     @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *     @OA\Property(
     *         property="order",
     *         type="object",
     *         @OA\Property(property="total_value", type="number"),
     *         @OA\Property(
     *             property="items",
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="product_name", type="string"),
     *                 @OA\Property(property="quantity", type="integer"),
     *                 @OA\Property(property="unit_value", type="number")
     *             )
     *         )
     *     )
     * )
     */
    private function createdOrderRequestSchema(){}
    

    /**
     * @OA\Schema(
     *     schema="UpdateStatusRequest",
     *     type="object",
     *     @OA\Property(property="status", type="string"),
     *     @OA\Property(property="notes", type="string")
     * )
     */

    /**
     * @OA\Schema(
     *     schema="OrderSummary",
     *     type="object",
     *     @OA\Property(property="total_orders", type="integer"),
     *     @OA\Property(property="total_value", type="number"),
     *     @OA\Property(property="average_order_value", type="number"),
     *     @OA\Property(
     *         property="status_breakdown",
     *         type="object",
     *         @OA\Property(property="PENDING", type="integer"),
     *         @OA\Property(property="PAID", type="integer"),
     *         @OA\Property(property="DELIVERED", type="integer")
     *     )
     * )
     */
    private function updateStatusRequestrderSchema(){}
    
}
