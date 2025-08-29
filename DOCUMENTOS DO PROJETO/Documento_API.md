# Documentação da API

## Autenticação

### Gerar Token de Acesso

- **Endpoint:** `POST /auth/token`
- **Headers:** `x-api-key: SUA_API_KEY`
- **Descrição:** Gera um token JWT para autenticação nas rotas protegidas.

#### Exemplo de Requisição

```http
POST /auth/token
x-api-key: SUA_API_KEY
```

#### Exemplo de Resposta

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

---

## Pedidos (`/orders`)  
**Todas as rotas abaixo exigem o header:**  
`Authorization: Bearer {token}`

---

### Listar Pedidos

- **Endpoint:** `GET /orders`
- **Descrição:** Lista pedidos com filtros opcionais.

#### Parâmetros de Consulta (query string)

- `id`: int
- `customer_id`: int
- `order_number`: string
- `status`: PENDING, WAITING_PAYMENT, PAID, PROCESSING, SHIPPED, DELIVERED, CANCELED
- `date_from`: datetime (YYYY-MM-DD)
- `date_to`: datetime (YYYY-MM-DD)
- `min_value`: float
- `max_value`: float

#### Exemplo

```http
GET /orders?status=PAID&min_value=100
Authorization: Bearer {token}
```

#### Exemplo de Resposta

```json
[
  {
    "order_id": "ORD-123456",
    "order_number": "ORD-123456",
    "status": "PAID",
    "total_value": 150.00,
    "customer": {
      "id": 1,
      "name": "João",
      "document": "12345678900",
      "email": "joao@email.com",
      "phone": "11999999999",
      "created_at": "2025-08-28 20:22:01"
    },
    "items": [
      {
        "id": 1,
        "order_id": 5,
        "product_name": "Produto A",
        "quantity": 2,
        "unit_value": 50,
        "total_value": 100
      }
    ],
    "created_at": "2024-06-01T12:00:00Z"
  }
]
```

---

### Criar Pedido

- **Endpoint:** `POST /orders`
- **Descrição:** Cria um novo pedido.

#### Corpo da Requisição (JSON)

```json
{
  "customer": {
    "id": 2,
    "name": "João",
    "document": "12345678900",
    "email": "joao@email.com",
    "phone": "11999999999"
  },
  "order": {
    "total_value": 150.00,
    "items": [
      {
        "product_name": "Produto A",
        "quantity": 2,
        "unit_value": 50
      }
    ]
  }
}
```

#### Exemplo de Resposta

```json
{
  "order_id": "ORD-123456",
  "order_number": "ORD-123456",
  "status": "PENDING",
  "total_value": 100,
  "customer": {
    "id": 1,
    "name": "João",
    "document": "12345678900",
    "email": "joao@email.com",
    "phone": "11999999999",
    "created_at": "2025-08-28 20:22:01"
  },
  "items": [
    {
      "product_name": "Produto A",
      "quantity": 2,
      "unit_value": 50,
      "total_value": 100
    }
  ],
  "created_at": "2024-06-01T12:00:00Z"
}
```

---

### Detalhes de um Pedido

- **Endpoint:** `GET /orders/{order_id}`
- **Descrição:** Retorna os detalhes de um pedido pelo número.

#### Exemplo

```http
GET /orders/ORD-123456
Authorization: Bearer {token}
```

#### Exemplo de Resposta

```json
{
  "order_id": "ORD-123456",
  "order_number": "ORD-123456",
  "status": "PAID",
  "total_value": 150.00,
  "customer": {
    "id": 1,
    "name": "João",
    "document": "12345678900",
    "email": "joao@email.com",
    "phone": "11999999999"
  },
  "items": [
    {
      "id": 9,
      "order_id": 9,
      "product_name": "Produto A",
      "quantity": 2,
      "unit_value": 50,
      "total_value": 100
    }
  ],
  "created_at": "2024-06-01T12:00:00Z"
}
```

---

### Resumo dos Pedidos

- **Endpoint:** `GET /orders/summary`
- **Descrição:** Retorna estatísticas dos pedidos.

#### Exemplo

```http
GET /orders/summary
Authorization: Bearer {token}
```

#### Exemplo de Resposta

```json
{
  "total_orders": 10,
  "total_value": 1500.00,
  "average_order_value": 150.00,
  "status_breakdown": {
    "PENDING": 2,
    "PAID": 5,
    "DELIVERED": 3
  }
}
```

---

### Atualizar Status do Pedido

- **Endpoint:** `PUT /orders/{order_id}/status`
- **Descrição:** Atualiza o status do pedido (processamento assíncrono).

#### Corpo da Requisição (JSON)

```json
{
  "status": "PAID",
  "notes": "Pagamento confirmado"
}
```

#### Exemplo

```http
PUT /orders/ORD-123456/status
Authorization: Bearer {token}
Content-Type: application/json
```

#### Exemplo de Resposta

```json
{
  "message": "Status enviado para processamento.",
  "order_id": "ORD-123456",
  "old_status": "WAITING_PAYMENT",
  "new_status": "PAID"
}
```
---

## Observações

- Todos os endpoints retornam JSON.
- Para rotas protegidas, envie o header `Authorization: Bearer {token}`.
- Para gerar token, utilize o endpoint `/auth/token` com o header `x-api-key`.
