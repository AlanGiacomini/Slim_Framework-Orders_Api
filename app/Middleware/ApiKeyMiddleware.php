<?php

namespace Alang\DesafioIpag\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ApiKeyMiddleware
{
    public function __invoke(ServerRequestInterface $request, $handler): ResponseInterface
    {
        $apiKeyHeader = $request->getHeaderLine('x-api-key');
        $validApiKey = $_ENV['API_KEY'];

        if ($apiKeyHeader !== $validApiKey) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized: Invalid API Key'
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        return $handler->handle($request);
    }
}