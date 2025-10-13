<?php
// app/Http/Request/SimpleRequest.php
namespace LangLearn\App\Http\Contract;

use LangLearn\App\Http\Interface\Request as RequestInterface;

// final class Request implements RequestInterface
// {
//     private array $headers;
//     private array $cookies;
//     private array $query;
//     private string $method;
//     private string $path;
//     private ?string $url;

//     /** @param array<string,mixed> $attributes */
//     public function __construct(
//         private array $server,
//         private ?array $json = null,
//         private ?string $routeName = null,
//         private array $attributes = []
//     ) {
//         $this->method  = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
//         $this->path    = self::detectPath($server);
//         $this->url     = self::detectUrl($server);
//         $this->headers = self::collectHeaders($server);
//         $this->cookies = $_COOKIE ?? [];
//         $this->query   = $_GET ?? [];
//     }

//     // -------- factory from PHP globals --------
//     public static function fromGlobals(): self
//     {
//         $body = file_get_contents('php://input') ?: '';
//         return new self($_SERVER, $body);
//     }

//     // -------- interface methods --------
//     public function getMethod(): string { return $this->method; }
//     public function getPath(): string { return $this->path; }
//     public function getUrl(): ?string { return $this->url; }

//     public function getQuery(string $key, mixed $default = null): mixed {
//         return $this->query[$key] ?? $default;
//     }
//     public function getAllQuery(): array { return $this->query; }

//     public function getHeader(string $name): ?string {
//         $key = strtolower($name);
//         $vals = $this->headers[$key] ?? null;
//         return $vals ? $vals[0] : null;
//     }
//     public function getAllHeaders(): array { return $this->headers; }

//     public function getCookie(string $name, mixed $default = null): mixed {
//         return $this->cookies[$name] ?? $default;
//     }
//     public function getAllCookies(): array { return $this->cookies; }

//     public function getBody(): string { return $this->body; }
//     public function getJson(): ?array { return $this->json; }

//     public function getRouteName(): ?string { return $this->routeName; }

//     public function getAttribute(string $key, mixed $default = null): mixed {
//         return $this->attributes[$key] ?? $default;
//     }

//     public function withRouteName(string $name): self {
//         $clone = clone $this;
//         $clone->routeName = $name;
//         return $clone;
//     }

//     public function withJson(array $json): self {
//         $clone = clone $this;
//         $clone->json = $json;
//         return $clone;
//     }

//     public function withAttribute(string $key, mixed $value): self {
//         $clone = clone $this;
//         $clone->attributes[$key] = $value;
//         return $clone;
//     }

//     // -------- helpers --------
//     private static function detectPath(array $server): string {
//         $uri = $server['REQUEST_URI'] ?? '/';
//         $qpos = strpos($uri, '?');
//         return ($qpos === false) ? $uri : substr($uri, 0, $qpos);
//     }

//     private static function detectUrl(array $server): ?string {
//         $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
//         $host   = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? null;
//         if (!$host) return null;
//         return $scheme.'://'.$host.($server['REQUEST_URI'] ?? '/');
//     }

//     /** normalize to [lower-name => string[]] */
//     private static function collectHeaders(array $server): array {
//         $headers = [];
//         foreach ($server as $k => $v) {
//             if (str_starts_with($k, 'HTTP_')) {
//                 $name = strtolower(str_replace('_', '-', substr($k, 5)));
//                 $headers[$name][] = is_array($v) ? implode(',', $v) : (string)$v;
//             }
//             // CONTENT_TYPE and CONTENT_LENGTH come without HTTP_ prefix
//             if ($k === 'CONTENT_TYPE')  { $headers['content-type'][] = (string)$v; }
//             if ($k === 'CONTENT_LENGTH'){ $headers['content-length'][] = (string)$v; }
//         }
//         return $headers;
//     }
// }