<?php

declare(strict_types=1);

namespace LangLearn\App\Http\Middleware;

// use LangLearn\App\Http\Middleware\Middleware as IMiddleware;

// Basically the set of browser origins allowed to access your app, like whitelisted browser origins
// Also it handles some of the response header and handles all responses to preflight (OPTIONS) requests
final class Cors implements Middleware
{
    public function __construct(private array $allowedOrigins)
    {
    }

    public function handle(?callable $next)
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
        $isAllowed = $origin === '' || in_array($origin, $this->allowedOrigins, true);

        // Preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if ($isAllowed) {
                header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
                header('Vary: Origin');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, If-None-Match');
                header('Access-Control-Max-Age: 600');
                http_response_code(204);
                exit;
            }
            http_response_code(403);
            exit;
        }

        if ($isAllowed) {
            header("Access-Control-Allow-Origin: " . ($origin ?: '*'));
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
            header('Content-Type: application/json');
            http_response_code(200);

            return isset($next) ? $next() : '';
        }

        http_response_code(403);
        return "CORS blocked for origin: $origin";
    }
}
