<?php

namespace Alang\DesafioIpag\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
use Predis\Client;

class RateLimitMiddleware
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => $_ENV['REDIS_HOST'] ?? 'redis',
            'port'   => $_ENV['REDIS_PORT'] ?? 6379,
        ]);
    }

    public function __invoke(Request $request, Handler $handler): ResponseInterface
    {
        $jwtPayload = $request->getAttribute('jwt_payload');
        $userKey = $jwtPayload['user'] ?? null;
        $rateKey = $userKey ? "rate:user:$userKey" : "rate:ip:" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        $limit = (int) ($_ENV['RATE_LIMIT_MAX'] ?? 50);
        $window = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 60);
        $now = time();

        // Remove requisições fora da janela
        $this->redis->zremrangebyscore($rateKey, '-inf', $now - $window);

        // Conta requisições recentes
        $count = $this->redis->zcard($rateKey);

        if ($count >= $limit) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Rate limit exceeded. Try again later.'
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(429);
        }

        // Registra a requisição atual
        $this->redis->zadd($rateKey, [$now => $now]);
        $this->redis->expire($rateKey, $window);

        return $handler->handle($request);
    }
}
