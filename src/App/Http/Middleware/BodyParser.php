<?php

declare(strict_types=1);

namespace LangLearn\App\Http\Middleware;

use LangLearn\App\Http\Contract\RequestContext;

final class BodyParser implements Middleware
{
    public int $jsonLimitBytes = 1_000_000; // 1MB
    public function __construct(private RequestContext $ctx) {}

    public function handle(callable|null $next)
    {
        $ctype = $_SERVER['CONTENT_TYPE'] ?? '';
        $len   = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if (str_contains($ctype, 'application/json')) {
            if ($len > $this->jsonLimitBytes) return $this->tooLarge();

            $raw = file_get_contents('php://input', false, null, 0, $this->jsonLimitBytes + 1);

            if ($raw === false || strlen($raw) > $this->jsonLimitBytes) return $this->tooLarge();

            try {
                $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($json)) return $this->badRequest('JSON body must be an object');

                $this->ctx->setBody($json);
            } catch (\Throwable $e) {
                return $this->badRequest('Malformed JSON: ' . $e->getMessage());
            }
        } else if (str_contains($ctype, 'application/x-www-form-urlencoded')) {
            // php auto populates $_POST for this content type
            $this->ctx->setBody($_POST ?? []);   
        } else if (str_contains($ctype, 'multipart/form-data')) {
            // php auto populates $_POST and $_FILES for this content type
            $this->ctx->setBody($_POST ?? []);
            $this->ctx->setFiles($_FILES ?? []);
        } else {
            // other content types are not supported yet
            $this->ctx->setBody([]);
        }

        return $next();
    }

    public function tooLarge()
    {
        http_response_code(413);
        echo json_encode([
            'status' => "error",
            'message' => 'Payload Too Large'
        ]);
        exit;
    }

    public function badRequest(string $msg)
    {
        // http_response_code(400);
        echo json_encode([
            'status' => "error",
            'message' => $msg
        ]);
        exit;
    }
}
