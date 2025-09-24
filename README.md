# 📘 README - API de Gerenciamento de Pedidos com Slim Framework

## 📑 Sumário

- [📦 Sobre o Projeto](#-sobre-o-projeto)
- [🚀 Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [🛠️ Estrutura do Projeto](#️-estrutura-do-projeto)
- [📝 Entregáveis](#-entregáveis)
- [📖 Instruções de Execução do Projeto](#-instruções-de-execução-do-projeto)
- [📄 Documentação da API](#-documentação-da-api)
- [🧠 Decisões Técnicas](#-decisões-técnicas)
- [🔒 Diferenciais Implementados](#-diferenciais-implementados)

## 📦 Sobre o Projeto

O objetivo é construir uma API REST para gerenciamento de pedidos de venda, com processamento assíncrono de atualizações de status e notificações via RabbitMQ.

## 🚀 Tecnologias Utilizadas

- PHP com Slim Framework
- MySQL
- RabbitMQ
- Swagger / OpenAPI
- Docker Compose

## 🛠️ Estrutura do Projeto
> A fim de manter a separação visual entre código fonte e documentos do projeto, foi criada a pasta `0 - DOCUMENTOS DO PROJETO`. Nela estão contidos os documentos gerados ao longo do desenvolvimento e o `Plano de Trabalho` proposto.

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
├───DOCUMENTOS DO PROJETO
│       Colletion-Desafio-iPag-Alan.postman_collection.json
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
## 📝 Entregáveis

- Código fonte completo no repositório GitHub:
`https://github.com/AlanGiacomini/desafio-ipag`

- README.md detalhado com:
`este documento`
  - Instruções de setup e execução
  - Documentação dos endpoints
  - Estrutura do projeto
  - Decisões técnicas tomadas

- Plano de trabalho (tasks e estimativas)
`documento PDF na pasta DOCUMENTOS DO PROJETO neste repositório`
- Docker Compose funcional
`docker-compose.yml`

- Migrations do banco de dados
`arquivos localizados em /migrations`

  > OBS: docker-compose já executa migrate.php (e as migrations) ao subir o conteiner.

- Collection do Postman/Insomnia para testes
`collection localizada na pasta DOCUMENTOS DO PROJETO`

## 📖 Instruções de Execução do Projeto

### Requisitos

- Docker e Docker Compose instalados

- Git instalado

### Passo a passo

1 - Clone o repositório:

```
git clone https://github.com/AlanGiacomini/desafio-ipag
cd desafio-ipag
```
2 - Construa e inicie os serviços:

```
docker-compose up -d --build
```
3 - Verifique se os serviços estão rodando:

```
docker ps
```
4 - Você deve ver os contêineres:

    ipag-api (API REST)

    ipag-db (MySQL)

    ipag-rabbitmq (RabbitMQ)

    ipag-worker (Worker de notificação)

    migrator (executa as migrations)

5 - Acesse o RabbitMQ Management:

    URL: http://localhost:15672
    Usuário: guest
    Senha: guest

6 - Teste os endpoints da API

Você pode usar Postman ou Insomnia. Uma collection de exemplo está disponível no repositório nas pasta DOCUMENTOS DO PROJETO.

### Encerramento dos serviços:

```
docker-compose down
```


## 📄 Documentação da API

### Endpoints REST disponíveis

- **POST /auth/token:** Cria token jwt a partir do envio da API-Key
- **POST /orders:** Cria um novo pedido
- **GET /orders/{order_id}:** Consulta pedido específico
- **PUT /orders/{order_id}/status:** Atualiza status do pedido
- **GET /orders:** Lista pedidos (com filtros opcionais)
- **GET /orders/summary:** Resumo estatístico dos pedidos
- **GET /health:** Realiza verificações de conectividade com os serviços que a API consome diretamente

---

### 🔒  Endpoint de Autenticação

#### ➡️ Gerar Token de Acesso

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

### 🛠️ Endpoints de Pedidos (`/orders`)  
**Todas as rotas abaixo exigem o header:**  
`Authorization: Bearer {token}`

### ➡️ Listar Pedidos

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

### ➡️ Criar Pedido

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

### ➡️ Detalhes de um Pedido

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

### ➡️ Resumo dos Pedidos

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

### ➡️ Atualizar Status do Pedido

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
### 🩺 Health check para API
>Esta rota não precisa de Authorization: Bearer {token}

- **Endpoint:** `GET /health`
- **Descrição:** Realiza verificações de conectividade com os serviços que a API consome diretamente.
  - Banco de dados (MySQL)
  - Redis (utilizado para controle de requisições)
  - RabbitMQ (fila de mensageria)

- Esse endpoint retorna um JSON com o status de cada serviço, permitindo que orquestradores, load balancers ou ferramentas de monitoramento identifiquem falhas rapidamente.

#### Exemplo

```http
GET /health
```
#### Exemplo de Resposta

```json
{
  "api": "ok",
  "database": "ok",
  "redis": "ok",
  "rabbitmq": "ok"
}
```

## Observações

- Todos os endpoints retornam JSON.
- Para gerar token, utilize o endpoint `/auth/token` com o header `x-api-key`.
- Para rotas protegidas, envie o header `Authorization: Bearer {token}`.

---
## 🧠 Decisões Técnicas

### ➖ Arquitetura do sistema

A arquitetura segue o padrão MVC adaptado para APIs REST, com separação clara entre rotas, controllers, serviços, modelos e persistência. A lógica de negócio foi isolada na camada de serviços, e o processamento assíncrono é tratado por workers desacoplados via RabbitMQ.

### ➖ Justificativas das dependências utilizadas

- **slim/slim** 
>microframework leve para criação de rotas e estruturação da API REST.
- **php-di/php-di** 
>gerenciador de dependências que facilita a injeção de serviços e modelos.
- **vlucas/phpdotenv** 
>carrega variáveis de ambiente de forma segura via arquivo .env.
- **firebase/php-jwt** 
>gera e valida tokens JWT para autenticação segura.
- **php-amqplib/php-amqplib** 
>integra a aplicação com RabbitMQ usando protocolo AMQP.
- **slim/psr7** 
>implementação PSR-7 para lidar com requisições e respostas HTTP no Slim.
- **predis/predis** 
>cliente Redis para PHP, usado em funcionalidades como rate limiting.
- **zircote/swagger-php** 
>gera documentação Swagger/OpenAPI diretamente das anotações no código.
- **doctrine/annotations** 
>interpreta as anotações utilizadas pelo Swagger-PHP.
- **bcmath** 
>extensão nativa do PHP usada por bibliotecas como firebase/php-jwt.
- **pdo** 
>ativa a interface PDO (PHP Data Objects), que permite acesso seguro e orientado a objetos a bancos de dados.
- **pdo_mysql** 
>habilita o driver específico para conexão PDO com MySQL/MariaDB, essencial para persistência dos dados da API.

### ➖ Cadastro de Cliente ao lançar novo Pedido

O endpoint  permite o envio de dados completos do cliente. Caso o cliente não exista (por id ou documento), o sistema realiza o cadastro automaticamente. Essa abordagem foi adotada considerando cenários em que o pedido é gerado diretamente por um app ou sistema que realiza o onboarding do cliente no mesmo fluxo.

### ➖ Segurança nas Rotas

As rotas foram estruturadas com dois grupos distintos a fim de mostrar a utilização de 2 medidas de segurança diferentes, mas complementares:
Verificação de API Key e verificação de Token Gerado.

```php
// Rotas protegidas por API-Key
$app->group('/auth', function ($group) {
    $group->post('/token', AuthController::class . ':generateToken');
})->add(new ApiKeyMiddleware());

// Rotas protegidas por JWT
$app->group('/orders', function ($group) {
    $group->get('', OrderController::class . ':list');
    $group->post('', OrderController::class . ':create');
    $group->get('/summary', OrderController::class . ':summary');
    $group->get('/{order_id}', OrderController::class . ':get');
    $group->put('/{order_id}/status', OrderController::class . ':updateStatus');
})->add(new JwtMiddleware());
```

Essa estrutura foi deixada pronta para permitir a identificação da aplicação acessando a API via "API Key" e do usuário logado via "JWT", caso o projeto evolua para múltiplos clientes ou autenticação por usuário final.

### ➖ Identificador de pedidos nas rotas

Em APIs é uma boa prática usar identificadores externos e legíveis nas rotas, como:
- ORD-12345 para pedidos;
Isso evita expor IDs internos das tabelas do banco de dados e facilita rastreabilidade.

### ➖ Assinatura do método "summary" em OrderController

Utilizar o prefixo _ (underscore) em um nome de variável, como em $_request, é uma convenção comum e uma boa prática no PHP. Isso sinaliza para outros desenvolvedores, e até para ferramentas de análise de código, que:

- O parâmetro é necessário na assinatura da função (nesse caso, porque o framework Slim exige).

- A variável é propositalmente ignorada dentro do corpo da função.

Essa prática ajuda a evitar warnings ou erros de linting sobre "variável não utilizada", que são comuns em IDEs e ferramentas de qualidade de código. É uma maneira clara de expressar a intenção e manter o código limpo e sem alertas desnecessários.

### ➖ Criação de QueueService.php
Ao desacoplar o publishToQueue para um QueueService:

- **Separa responsabilidades**: o OrderService cuida da lógica de pedidos, enquanto o QueueService cuida da mensageria.
- **Facilita testes**: é possível mockar o QueueService em testes unitários.
- **Ganha flexibilidade**: se futuramente quiser publicar em outra fila, outro exchange, ou até outro broker (Kafka, Redis Streams), só alterar o QueueService.
- **Cria espaço para reuso**: outros serviços (ex: cancelamento, faturamento, envio de e-mails) podem usar o mesmo publisher.

### ➖ Validação de transição de status do pedido no Service e não no Worker

A validação das transições de status foi implementada diretamente na API, antes da publicação da mensagem na fila RabbitMQ. Essa decisão foi tomada com base nos seguintes critérios:

- Feedback imediato ao cliente: ao validar a transição na API, o usuário recebe uma resposta rápida caso a operação seja inválida, evitando frustrações ou dúvidas sobre o processamento.
- Redução de carga no Worker: ao garantir que apenas mensagens válidas sejam publicadas, o Worker pode se concentrar exclusivamente na execução das ações, sem precisar lidar com validações complexas.
- Evita poluição da fila: mensagens inválidas não são enviadas, o que reduz a necessidade de tratamento de erros, Dead Letter Queues ou reprocessamentos desnecessários.
- Melhor experiência de desenvolvimento: centralizar a validação na API facilita testes manuais e automatizados, além de tornar o fluxo mais previsível.

Essa abordagem foi escolhida por priorizar a clareza, simplicidade e controle no ponto de entrada do sistema, mantendo o Worker como executor de ações previamente validadas.

### ➖ Estrutura da tabela de logs

A estrutura da tabela notification_logs foi adaptada para suportar logs semânticos e estruturados, com campos específicos para nível (level) e contexto adicional (context) em formato JSON. Essa abordagem facilita exportações, auditoria, e integração futura com ferramentas de monitoramento.

### ➖ Uso do Redis no Rate Limiting

O Redis foi adotado como mecanismo de controle de requisições (Rate Limiting) para garantir escalabilidade, consistência e performance em ambientes distribuídos. Diferente de abordagens que armazenam requisições em memória local (como arrays estáticos), o Redis permite que múltiplos containers ou processos compartilhem o mesmo estado de controle, evitando inconsistências e garantindo que o limite seja respeitado globalmente.

Além disso, o Redis oferece estruturas como Sorted Sets, que facilitam a implementação de janelas deslizantes (sliding window), removendo requisições antigas e contabilizando apenas as recentes. Essa abordagem é especialmente útil em ambientes com Docker Compose, onde múltiplas instâncias da aplicação podem estar em execução simultânea.

Com isso, o sistema se torna mais robusto contra abusos, ataques de negação de serviço (DoS) e garante uma experiência mais estável para todos os usuários.


## 🔒 Diferenciais Implementados

### 🔒 Nivel 1 - Validação robusta de dados de entrada

A classe `Validator` foi criada para centralizar e padronizar a validação de dados recebidos pela API. Em vez de espalhar validações por controllers e services, essa abordagem oferece:

- **Reutilização de lógica**: Um único ponto de validação pode ser usado em múltiplos endpoints.
- **Facilidade de testes**: Métodos isolados facilitam testes unitários.
- **Padronização de respostas**: Todos os erros seguem o mesmo formato, com `code` e `message`, facilitando o tratamento no frontend.
- **Extensibilidade**: Novas regras podem ser adicionadas sem afetar o restante da aplicação.

#### Validação de Valores

Os métodos `validateField` e `validateAllFields` foram implementados com base em regras comuns de integridade de dados, utilizando funções nativas do PHP e expressões regulares. Cada erro é retornado no formato:

```php
$errors['nome_do_campo'] = [
  'code' => 'invalid_email',
  'message' => 'E-mail inválido.'
];
```
Essa estrutura foi escolhida para:

- Separar a lógica de negócio da lógica de validação
- Permitir internacionalização (o frontend pode traduzir os códigos)
- Facilitar debug e rastreamento
- Melhorar a experiência do consumidor da API

#### Validação de Parâmetros permitidos

O método `validateAllowedKeys` foi criado para validar se os parâmetros recebidos em uma requisição (geralmente via query string ou corpo JSON) estão dentro de um conjunto permitido. Ele evita que campos inesperados ou maliciosos sejam processados silenciosamente pela API.

#### Exemplo de resposta da API

```json
{
  "error": "Erro ao listar pedidos: Erro de validação nos filtros.",
  "invalid_keys": [
    "comanda"
  ],
  "invalid_values": {
    "status": [
      {
        "code": "value_not_allowed",
        "message": "Valor não permitido."
      }
    ],
    "date_from": [
      {
        "code": "invalid_datetime",
        "message": "Formato de data inválido (ISO 8601)."
      }
    ]
  }
}
```
### Vantagens dessa abordagem

- Segurança: evita que campos não documentados sejam aceitos ou utilizados indevidamente
- Previsibilidade: garante que a API só processe dados esperados
- Facilidade de debug: retorna os nomes dos campos inválidos de forma clara
- Complementa a validação de valores: enquanto  valida o conteúdo,  valida a estrutura

### Exemplo de uso em GET /orders: Lista pedidos (com filtros opcionais)

```
$filters = $request->getQueryParams();

//Passa a lista de campos permitidos no GET
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

$invalidate['invalid_values'] = Validator::validateAllFields($filters, $rules);
```
---
### 🔒 Nivel 1 - Logs estruturados em formato JSON

A tabela `notification_logs` foi criada com uma coluna `context` do tipo `JSON`, permitindo armazenar dados estruturados diretamente no banco:
```
context:
{
    "notes": "Pagamento confirmado via PIX",
    "user_id": "system",
    "order_id": 7,
    "created_at": "2025-08-28T21:48:29-03:00",
    "new_status": "SHIPPED",
    "old_status": "PROCESSING"
}
```
Isso garante que cada log de notificação possa conter metadados ricos e organizados, sem depender de parsing de texto ou campos adicionais.

Esse formato permite:
- Auditoria precisa: cada alteração de status tem rastreabilidade completa.
- Consultas flexíveis: usando funções nativas do MySQL para JSON (JSON_EXTRACT, ->, etc.).
- Integração com ferramentas externas: como ELK, Grafana ou serviços de monitoramento.

---
### 🔒 Nivel 1 - README detalhado com exemplos de uso

Corresponde a essa documentação README.md, especificamente na sessão "📄 Documentação da API".

---
### 🔒 Nivel 2 - Rate limiting básico nos endpoints

Criado Middleware `RateLimitMiddlaware` para controle de limites de requisições:

- **Identificação do cliente**: usa `jwt_payload['user']` como chave primária. Se o token estiver ausente, usa o IP como fallback. O controle por usuário é útil para limitar por identidade (ex: admin, cliente123). Por outro lado poderia ser usado o controle por token, que é útil se você quer isolar o Rate limit de cada sessão ou dispositivo.
- **Configuração dinâmica**: os limites são definidos via `.env` com as variáveis `RATE_LIMIT_MAX` e `RATE_LIMIT_WINDOW`.
- **Controle por janela deslizante**: armazena timestamps das requisições e remove os que estão fora da janela. A cada requisição, o sistema olha para os últimos 60 segundos a partir do momento atual.
- **Resposta padrão**: retorna `429 Too Many Requests` com mensagem JSON estruturada.

#### Exemplo de `.env`

```env
RATE_LIMIT_MAX=50
RATE_LIMIT_WINDOW=60
```
#### Como aplicar nas rotas

```php
$app->group('/orders', function ($group) {
    // suas rotas protegidas
})->add(new JwtMiddleware())->add(new RateLimitMiddleware());
```

---

### 🔒 Nivel 2 - Health checks para API e Worker

A aplicação implementa verificadores de saúde independentes para a API e o Worker, permitindo monitoramento automatizado e validação da operação dos serviços.

#### Health Check da API

A API expõe o endpoint `/health`, que pode ser acessado via HTTP. Ele realiza verificações de conectividade com os serviços que a API consome diretamente:

- Banco de dados (MySQL)
- Redis (utilizado para controle de requisições)
- RabbitMQ (fila de mensageria)

Esse endpoint retorna um JSON com o status de cada serviço, permitindo que orquestradores, load balancers ou ferramentas de monitoramento identifiquem falhas rapidamente.

**Exemplo de resposta:**

```json
{
  "api": "ok",
  "database": "ok",
  "redis": "ok",
  "rabbitmq": "ok"
}
```

#### Health Check do Worker

O Worker possui um script CLI localizado em `worker/healthCheck.php`, que pode ser executado manualmente ou por sistemas de monitoramento. Ele verifica:

- Conexão com o banco de dados
- Conexão com o RabbitMQ
- Existência e status da fila `order_status_updates`

**Exemplo de execução:**

```bash
docker exec ipag-worker php worker/healthChecker.php
```

**Exemplo de saída:**

```json
{
  "worker": "ok",
  "database": "ok",
  "rabbitmq": "ok",
  "queue": "ok",
  "messages_pending": 3,
  "consumers": 1
}
```
---
### 🔒 Nivel 2 - API documentation (Swagger/OpenAPI)

A documentação foi gerada automaticamente a partir das anotações no código, facilitando a compreensão e o uso da API por desenvolvedores e integradores.

O arquivo localizado em 'public/docs/openapi.yaml' contém a documentação OpenAPI do projeto.

---
### 🔒 Nivel 3 - Environment-based configuration

A aplicação utiliza variáveis de ambiente para configurar dinamicamente aspectos como credenciais de banco de dados, chaves secretas, URLs externas e níveis de log. Isso é feito por meio da biblioteca vlucas/phpdotenv, que carrega o conteúdo do arquivo .env e disponibiliza os valores via $_ENV. Essa abordagem permite flexibilidade entre ambientes (desenvolvimento, produção, testes), evita hardcode de informações sensíveis e facilita a automação de deploys e integração com Docker e CI/CD.

---
# 👨‍💻 Autor: Alan Giacomini

📫 **Contato**: [LinkedIn](https://www.linkedin.com/in/alangiacominisp/)







