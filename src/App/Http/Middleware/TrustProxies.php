<?php

namespace LangLearn\App\Http\Middleware;

use LangLearn\App\Http\Contract\RequestContext;
// use LangLearn\App\Http\Middleware\Middleware as IMiddleware;

// cidrs are Classless Inter-Domain Routing
// Basically they are a basic form of ip grouping to indicate a set ips or a range of ips eg 192.168.0.0/24 ie all ips that starts from 192.168.0 - 192.168.255, like a particular building range

final class TrustProxies implements Middleware {
  public function __construct(private array $trustedCidrs, private RequestContext $ctx) {}
  public function handle(?callable $next = null): array {
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!$this->isTrusted($remoteIp)) return [
      "message" => "Not a trusted ip, maybe it's not behind a proxy",
      "shouldStop" => false,
    ];

    $xfFor   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    $xfProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $xfHost  = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';

    $clientIp = trim(explode(',', $xfFor)[0] ?? $remoteIp);
    $scheme   = $xfProto ?: ($_SERVER['HTTPS'] ? 'https' : 'http');
    $host     = $xfHost ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $this->ctx->addHeader('clientIp', $clientIp);
    $this->ctx->addHeader('scheme', $scheme);
    $this->ctx->addHeader('host', $host);

    return [
      "message" => "IP verified and cleaned",
      "shouldStop" => false,
    ];;
  }
  private function isTrusted(string $ip): bool {
    foreach ($this->trustedCidrs as $cidr) {
      if ($this->inCidr($ip, $cidr)) return true;
    }
    return false;
  }

  // Subnet mask and IP matching calculations
  private function inCidr(string $ip, string $cidr): bool {
    [$subnet,$mask] = explode('/', $cidr) + [1=>32];
    return (ip2long($ip) & ~((1<<(32-$mask))-1)) === (ip2long($subnet) & ~((1<<(32-$mask))-1));
  }
}