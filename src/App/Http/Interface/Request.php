<?php
// app/Http/Contracts/Request.php
namespace LangLearn\App\Http\Interface;

interface Request
{
    /** HTTP method in uppercase (GET/POST/PUT/PATCH/DELETE/OPTIONS) */
    public function getMethod(): string;

    /** Raw request path (e.g., /api/projects/123) without scheme/host/query */
    public function getPath(): string;

    /** Full URL if available (optional to implement) */
    public function getUrl(): ?string;

    /** Query param by key; returns default if missing */
    public function getQuery(string $key, mixed $default = null): mixed;

    /** All query params */
    public function getAllQuery(): array;

    /** Header value by case-insensitive name (first value) */
    public function getHeader(string $name): ?string;

    /** All headers as [lowercased-name => string[]] */
    public function getAllHeaders(): array;

    /** Cookie by name */
    public function getCookie(string $name, mixed $default = null): mixed;

    /** All cookies */
    public function getAllCookies(): array;

    /** Raw body (string). Keep lazily loaded if you like. */
    public function getBody(): string;

    /** Parsed JSON payload previously stored by BodyParser (array) or null */
    public function getJson(): ?array;

    /** Route name set by your router (used by rate limiter buckets) */
    public function getRouteName(): ?string;

    /** Arbitrary attribute by key (clientIp, scheme, host, user_id, etc.) */
    public function getAttribute(string $key, mixed $default = null): mixed;

    /** Return a new request with route name set (immutably) */
    public function withRouteName(string $name): self;

    /** Return a new request with JSON payload set (immutably) */
    public function withJson(array $json): self;

    /** Return a new request with an attribute set (immutably) */
    public function withAttribute(string $key, mixed $value): self;
}