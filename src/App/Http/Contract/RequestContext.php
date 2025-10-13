<?php

declare(strict_types=1);

namespace LangLearn\App\Http\Contract;

final class RequestContext 
{
    /** @var array<string,mixed> */
    private array $files;
    private array $params;
    private array $query;
    private array $body;
    private array $headers;

    public function __construct() {
        $this->files = $_FILES ?? [];
        $this->params = [];
        $this->query = $_GET ?? [];
        $this->body = $_POST ?? [];
        $this->headers = getallheaders() ?: [];
    }

    public function setFiles(array $files): void {
        $this->files = $files;
    }

    public function getFiles(): array {
        return $this->files;
    }

    public function setParams(array $params): void {
        $this->params = $params;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function setQuery(array $query): void {
        $this->query = $query;
    }   

    public function getQuery(): array {
        return $this->query;
    }

    public function setBody(array $body): void {
        $this->body = $body;
    }

    public function getBody(): array {
        return $this->body;
    }

    public function setHeaders(array $headers): void {
        $this->headers = $headers;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function addHeader(string $key, string $value): void {
        $this->headers[$key] = $value;
    } 

    public function getHeader(string $key): ?string {
        return $this->headers[$key] ?? null;
    }

    public function clear(): void {
        $this->files = [];
        $this->params = [];
        $this->query = [];
        $this->body = [];
        $this->headers = [];
    }
}