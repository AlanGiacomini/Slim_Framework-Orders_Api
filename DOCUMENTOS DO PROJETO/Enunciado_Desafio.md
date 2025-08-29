# 🚀 Desafio Desenvolvedor(a) de Software - iPag

## 📌 Instruções Gerais

1. Leia esse documento com atenção antes de iniciar as atividades.
2. Você terá:
   - **2 dias** para entregar o plano de trabalho (item 1).
   - **Até 7 dias corridos** para concluir as atividades solicitadas.
   - Caso não consiga concluir todas as atividades, entregue o que tiver feito até a data.
   - **Importante:** qualidade e clareza serão mais valorizadas do que quantidade de funcionalidades.

3. Crie um repositório **público** no Github para seu projeto.
4. Ao concluir, envie um e-mail com o assunto:
   `[DESAFIO IPAG] - SEU NOME COMPLETO` para: **[vagas@ipag.com.br](mailto:vagas@ipag.com.br)**, incluindo o link do repositório.

5. Você pode utilizar tecnologias, libs ou técnicas adicionais, desde que documente em seu relatório final os motivos.

6. A aplicação deve rodar com instruções claras de execução (README).

7. **Obrigatório:** utilização de **Docker Compose** para montagem do ambiente (MySQL, RabbitMQ, aplicação).

8. **Não utilize frameworks prontos** (ex.: Laravel, Nest, Adonis, Django).
   - Permitido uso de micro frameworks para endpoints (Express, Flask, Slim, etc.).
   - O objetivo é avaliar sua capacidade de estruturar soluções do zero.

9. Documente bem o seu projeto (README explicativo, arquitetura, decisões técnicas).

10. **Boa sorte!**

---

## 🎯 Escopo

O desafio consiste em desenvolver uma **API REST** para gerenciar pedidos de venda e um **Worker** para processamento assíncrono de notificações. O sistema deve incluir funcionalidades de cadastro, consulta, atualização de status e geração de logs de notificação via RabbitMQ.

### Componentes da Solução:

1. **API REST**: Gerencia pedidos (CRUD + status)
2. **Worker/Consumer**: Processa mensagens da fila e gera logs
3. **Banco de Dados**: Armazena pedidos, clientes e itens
4. **RabbitMQ**: Mensageria para comunicação assíncrona

---

## 🏗️ Arquitetura Esperada

```
┌─────────────┐    ┌──────────────┐    ┌─────────────┐
│   API REST  │────│   RabbitMQ   │────│   Worker    │
│             │    │              │    │             │
│ - Orders    │    │ Queue:       │    │ - Consume   │
│ - Status    │    │ order_status │    │ - Log       │
│ - Summary   │    │              │    │ - Notify    │
└─────────────┘    └──────────────┘    └─────────────┘
       │                                       │
       └─────────────┐               ┌─────────┘
                     │               │
              ┌──────▼───────────────▼──────┐
              │      MySQL Database         │
              │                             │
              │ Tables:                     │
              │ • customers                 │
              │ • orders                    │
              │ • order_items               │
              │ • notification_logs         │
              └─────────────────────────────┘
```

---

## 📋 Funcionalidades Obrigatórias

### 1. **Plano de Trabalho**
- Liste as atividades como **tarefas**.
- Estime as horas de cada atividade.

### 2. **API Backend** (PHP, Node.js ou Python)
- Estrutura limpa, organizada, com boas práticas de programação.
- Utilize padrões de projeto (MVC, Repository, etc.).

### 3. **Banco de Dados (MySQL, MariaDB ou PostgreSQL)**
- Modele e implemente as tabelas necessárias.
- Crie migrations para criação das tabelas.
- **Sugestão de estrutura mínima:**
  ```sql
  customers: id, name, document, email, phone, created_at
  orders: id, customer_id, order_number, total_value, status, created_at, updated_at
  order_items: id, order_id, product_name, quantity, unit_value
  notification_logs: id, order_id, old_status, new_status, message, created_at
  ```

### 4. **RabbitMQ + Worker**
- **Fila**: `order_status_updates`
- **Publisher**: API publica mensagem quando status muda
- **Consumer**: Worker processa mensagens e gera logs
- **Formato da mensagem:**
  ```json
  {
    "order_id": "ORD-12345",
    "old_status": "PENDING",
    "new_status": "PAID",
    "timestamp": "2025-08-21T10:30:00Z",
    "user_id": "system"
  }
  ```

### 5. **Endpoints REST**

- **POST /orders**: Cria um novo pedido
- **GET /orders/{order_id}**: Consulta pedido específico
- **PUT /orders/{order_id}/status**: Atualiza status do pedido
- **GET /orders**: Lista pedidos (com filtros opcionais)
- **GET /orders/summary**: Resumo estatístico dos pedidos

---

## 📊 Estrutura de Dados

### Exemplo de JSON de entrada (POST /orders):

```json
{
  "customer": {
    "id": 1,
    "name": "Fulano de Tal",
    "document": "12345678900",
    "email": "fulano@email.com",
    "phone": "11999999999"
  },
  "order": {
    "total_value": 150.00,
    "items": [
      {
        "product_name": "Produto 1",
        "quantity": 2,
        "unit_value": 50.00
      },
      {
        "product_name": "Produto 2",
        "quantity": 1,
        "unit_value": 50.00
      }
    ]
  }
}
```

### Resposta da API:

```json
{
  "order_id": "ORD-12345",
  "order_number": "ORD-12345",
  "status": "PENDING",
  "total_value": 150.00,
  "customer": {
    "id": 1,
    "name": "Fulano de Tal",
    "document": "12345678900",
    "email": "fulano@email.com",
    "phone": "11999999999"
  },
  "items": [
    {
      "product_name": "Produto 1",
      "quantity": 2,
      "unit_value": 50.00,
      "total_value": 100.00
    },
    {
      "product_name": "Produto 2",
      "quantity": 1,
      "unit_value": 50.00,
      "total_value": 50.00
    }
  ],
  "created_at": "2025-08-21T10:30:00Z"
}
```

### Atualização de Status (PUT /orders/{id}/status):

```json
{
  "status": "PAID",
  "notes": "Pagamento confirmado via PIX"
}
```

---

## 📈 Fluxo de Status de Pedido

### Estados Válidos:
- **PENDING**: Pedido criado, aguardando pagamento
- **WAITING_PAYMENT**: Aguardando confirmação de pagamento
- **PAID**: Pagamento confirmado
- **PROCESSING**: Pedido em processamento
- **SHIPPED**: Pedido enviado
- **DELIVERED**: Pedido entregue ao cliente
- **CANCELED**: Pedido cancelado

### Transições Válidas:
```
PENDING → WAITING_PAYMENT → PAID → PROCESSING → SHIPPED → DELIVERED
   ↓              ↓          ↓         ↓
CANCELED    CANCELED    CANCELED   CANCELED
```

### Regras de Negócio:
- Não é possível cancelar pedidos já entregues
- Status só pode avançar sequencialmente (exceto cancelamento)
- Toda mudança de status deve gerar notificação na fila

---

## 🔧 Worker de Notificação

### Responsabilidades:
1. **Consumir** mensagens da fila `order_status_updates`
2. **Validar** dados da mensagem
3. **Registrar** log no banco de dados (tabela `notification_logs`)
4. **Simular** envio de notificação (log estruturado)

### Exemplo de Log Gerado:
```
[2025-08-21 10:30:00] INFO: Order ORD-12345 status changed from PENDING to PAID
[2025-08-21 10:30:00] INFO: Notification sent to customer fulano@email.com
```

---

## 📊 O que será avaliado

### Critérios Principais:
- **Qualidade do código** (organização, padrões, clean code)
- **Modelagem do banco** (clareza, normalização, migrations)
- **Uso correto de filas** (publisher/consumer, tratamento de erros)
- **Boas práticas de arquitetura** (separação de responsabilidades)
- **Documentação clara** (README, setup, endpoints, decisões)

### Critérios Técnicos:
- **Validação de dados** de entrada
- **Tratamento de erros** e logs estruturados
- **Transições de status** válidas
- **Código limpo** e bem comentado
- **Facilidade de execução** (Docker Compose funcional)

---

## 🔒 Diferenciais (opcionais)

### Nível I:
- **Validação robusta** de dados de entrada
- **Logs estruturados** em formato JSON
- **README detalhado** com exemplos de uso

### Nível II:
- **Testes automatizados** (unitários e integração)
- **API documentation** (Swagger/OpenAPI)
- **Health checks** para API e Worker
- **Rate limiting** básico nos endpoints

### Nivel III:
- **Dead Letter Queue** para mensagens com falha
- **Metrics/Monitoring** básico (contadores, timers)
- **Graceful shutdown** dos serviços
- **Environment-based configuration**
- **Database connection pooling**

---

## 📝 Entregáveis

1. **Código fonte** completo no repositório GitHub
2. **README.md** detalhado com:
   - Instruções de setup e execução
   - Documentação dos endpoints
   - Estrutura do projeto
   - Decisões técnicas tomadas
3. **Plano de trabalho** (tasks e estimativas)
4. **Docker Compose** funcional
5. **Migrations** do banco de dados
6. **Collection do Postman/Insomnia** para testes (opcional)

---

## ⚡ Dicas de Implementação

1. **Comece simples**: implemente primeiro a API básica, depois adicione o worker
2. **Teste incrementalmente**: cada endpoint deve funcionar antes de prosseguir
3. **Use logs**: facilita debug e demonstra profissionalismo
4. **Documente decisões**: explique por que escolheu determinada abordagem
5. **Valide entradas**: sempre valide dados recebidos pela API
6. **Pense em falhas**: como o sistema se comporta quando algo dá errado?

**Tempo estimado: 20-35 horas** (dependendo do nível e diferenciais implementados)

**Boa sorte! 🚀**
