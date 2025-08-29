<?php

namespace Alang\DesafioIpag\Controllers;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    /**
     * @OA\Post(
     *     path="/auth/token",
     *     tags={"Auth"},
     *     summary="Gera um token JWT para autenticação",
     *     description="Retorna um token JWT para uso nas rotas protegidas.",
     *     @OA\Parameter(
     *         name="x-api-key",
     *         in="header",
     *         required=true,
     *         description="Chave de API para autenticação",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token JWT gerado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid API Key",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized: Invalid API Key")
     *         )
     *     )
     * )
     */
    public function generateToken(Request $request, Response $response, array $args): Response
    {
        $apiKey = $request->getHeaderLine('x-api-key');
        $validApiKey = $_ENV['API_KEY'];

        if ($apiKey !== $validApiKey) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized: Invalid API Key'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $payload = [
            'user' => 'system',
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + (int) $_ENV['JWT_EXPIRATION']
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $jwt
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
