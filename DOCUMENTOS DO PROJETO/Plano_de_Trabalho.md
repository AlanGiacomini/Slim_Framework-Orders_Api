# Plano de Trabalho

### Execução dos itens principais

#### Lista de Tarefas

- **Configuração do ambiente com Docker Compose (MySQL, RabbitMQ, aplicação):** 2h
- **Estruturação do projeto com Slim Framework e padrão MVC:** 2h
- **Implementação da autenticação via API Key e geração de token JWT:** 3h
- **Middleware para validação do token JWT em todos os endpoints:** 2h
- **Modelagem do banco de dados e criação das migrations:** 2h
- **Implementação dos endpoints REST:**
  - POST /orders: 2h
  - GET /orders/{order_id}: 1h
  - PUT /orders/{order_id}/status: 2h
  - GET /orders (com filtros): 2h
  - GET /orders/summary: 2h
- **Integração com RabbitMQ (publisher no backend):** 2h
- **Implementação do Worker (consumer + log + simulação de notificação):** 3h
- **Testes manuais e ajustes finais:** 3h

**Total:** 28 horas

### Execução dos diferenciais opcionais

#### Nível I

- **Validação robusta de dados de entrada:** 2h
- **Logs estruturados em formato JSON:** 1h
- **README detalhado com exemplos de uso:** 1h

#### Nível II

- **API documentation (Swagger/OpenAPI):** 2h
- **Health checks para API e Worker:** 1h
- **Rate limiting básico nos endpoints:** 1h

**Total:** 8 horas

---

### Previsão de estrutura de pastas e arquivos ao final do projeto

```
project-root/
├── app/
│   ├── Controllers/ orderController.php
│   ├── Models/
│   └── Services/ orderService.php
├── config/
│   ├── database.php
│   └── rabbitmq.php
│   └── rateLimiter.php         		🟡 (para controle de requisições)
├── public/
│   └── swagger/ index.php       	🟡 (Swagger UI)
│   └── docs/                   		🟡 (documentação Swagger/OpenAPI)
├── routes/
│   └── web.php
├── worker/
│   └── statusOrderWorker.php
│   └── healthCheck.php         	🟡 (verificação de saúde do worker)
├── docker-compose.yml
├── Dockerfile
├── migrations/
│   ├── 20250824_create_customers_table.php
│   ├── 20250824_create_orders_table.php
│   ├── 20250824_create_order_items_table.php
│   └── 20250824_create_notification_logs_table.php
├── logs/
│   └── api.log                 		🟡 (logs estruturados em JSON)
│   └── worker.log              		🟡(logs estruturados em JSON)
├── README.md
└── .env
```
**OBS:** Os itens marcados com o ícone 🟡 fazem referência aos diferenciais que poderão ser implementados na primeira versão ou no futuro.
