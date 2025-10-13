<?php

declare(strict_types=1);

namespace LangLearn\App\Http\Middleware;

use LangLearn\App\Http\Contract\RequestContext;

Interface Middleware
{
    public function handle(?callable $next);
}