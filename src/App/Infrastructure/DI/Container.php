<?php

declare(strict_types=1);

namespace LangLearn\App\Infrastructure\DI;

class Container
{
    private array $entries;

    private function has(string $id)
    {
        return isset($this->entries[$id]);
    }

    public function bind(string $id, callable $class)
    {
        $this->entries[$id] = $class;
    }

    public function get(string $id, array $args = [])
    {
        if ($this->has($id)) {
            if (is_callable($this->entries[$id])) {
                return call_user_func($this->entries[$id], $args);
            }
            return $this->entries[$id];
        }
    }

    private function resolve() {}
}
