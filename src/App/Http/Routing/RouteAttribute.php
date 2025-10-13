<?php

declare(strict_types=1);

namespace LangLearn\App\Http\Routing;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RouteAttribute
{
    public function __construct(
        public readonly string $path,
        public readonly string $method = 'GET'
    ) {}
}
