```
│   .env
│   .gitignore
│   composer.json
│   composer.lock
│   docker-compose.yml
│   Dockerfile
│   migrate.php
│   README.md
│
├───0 - DOCUMENTOS DO PROJETO
│       Documento_API.md
│       Enunciado_Desafio.md
│       Estrutura de Arquivos.md
│       Plano de Trabalho - Desafio iPag - Alan Giacomini.pdf
│       Plano Desafio.md
│
├───app
│   │   SwaggerBase.php
│   │
│   ├───Controllers
│   │       AuthController.php
│   │       HealthController.php
│   │       OrderController.php
│   │
│   ├───Middleware
│   │       ApiKeyMiddleware.php
│   │       JwtMiddleware.php
│   │       RateLimitMiddleware.php
│   │
│   ├───Models
│   │       Customer.php
│   │       NotificationLog.php
│   │       Order.php
│   │       OrderItem.php
│   │
│   ├───Services
│   │       OrderService.php
│   │       QueueService.php
│   │
│   └───Utils
│           Validator.php
│
├───config
│       conteiner.php
│       database.php
│
├───logs
├───migrations
│       202508241600_create_customers_table.php
│       202508241605_create_orders_table.php
│       202508241610_create_notification_logs_table.php
│       202508241615_create_order_items_table.php
│
├───public
│   │   .htaccess
│   │   index.php
│   │
│   └───docs
│           openapi.yaml
│
├───routes
│       web.php
│
├───vendor
│
└───worker
        healthChecker.php
        statusOrderWorker.php
```