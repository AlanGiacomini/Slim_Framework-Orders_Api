<?php

namespace Alang\DesafioIpag\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Predis\Client as RedisClient;
use PDO;

class HealthController
{
    public function __construct(
        private PDO $pdo,
        private RedisClient $redis,
        private AMQPStreamConnection $rabbit
    ) {}

    /**
     * @OA\Get(
     *     path="/health",
     *     tags={"Health"},
     *     summary="Verifica se a API está online",
     *     description="Endpoint de health check.",
     *     @OA\Response(
     *         response=200,
     *         description="API online",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="ok")
     *         )
     *     )
     * )
     */
    public function check(Request $_request, Response $response): Response
    {
        $status = ['api' => 'ok', 'database' => 'unknown', 'redis' => 'unknown', 'rabbitmq' => 'unknown'];

        try {
            $this->pdo->query('SELECT 1');
            $status['database'] = 'ok';
        } catch (\Exception) {
            $status['database'] = 'error';
        }

        try {
            $this->redis->ping();
            $status['redis'] = 'ok';
        } catch (\Exception) {
            $status['redis'] = 'error';
        }

        try {
            $this->rabbit->isConnected();
            $status['rabbitmq'] = 'ok';
        } catch (\Exception) {
            $status['rabbitmq'] = 'error';
        }

        $response->getBody()->write(json_encode($status, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
