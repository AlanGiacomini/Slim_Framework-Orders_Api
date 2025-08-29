<?php

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL & ~E_DEPRECATED);

date_default_timezone_set('America/Sao_Paulo');

// Carrega variáveis de ambiente do .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Cria o container de dependências incluindo o arquivo de configuração
$container = require_once __DIR__ . '/../config/conteiner.php';
AppFactory::setContainer($container);

// Cria a aplicação Slim
$app = AppFactory::create();

// Middleware para tratamento de erros
$app->addErrorMiddleware(true, true, true);

// Middleware para parsear JSON do corpo da requisição
$app->addBodyParsingMiddleware();

// Middleware de CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Resposta para requisições OPTIONS (pré-flight)
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Define as rotas da aplicação
require __DIR__ . '/../routes/web.php';

// Executa a aplicação
$app->run();