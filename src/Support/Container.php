<?php

namespace Spatie\FlareClient\Support;

use Closure;
use Psr\Container\ContainerInterface;
use Spatie\FlareClient\Support\Exceptions\ContainerEntryNotFoundException;

class Container implements ContainerInterface
{
    private static self $instance;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @template T
     *
     * @param array<class-string<T>, Closure(): T> $definitions
     * @param array<class-string<T>, Closure(): T> $singletons
     * @param array<class-string<T>, T> $initializedSingletons
     */
    protected function __construct(
        protected array $definitions = [],
        protected array $singletons = [],
        protected array $initializedSingletons = [],
    ) {
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     * @param null|Closure():T|class-string<T> $builder
     */
    public function singleton(string $class, null|string|Closure $builder = null): void
    {
        $this->singletons[$class] = match (true) {
            $builder === null => fn () => new $class(),
            is_string($builder) => fn () => new $builder(),
            default => $builder,
        };
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     * @param null|Closure():T $builder
     */
    public function bind(string $class, ?Closure $builder = null): void
    {
        $this->definitions[$class] = $builder ?? fn () => new $class();
    }

    /**
     * @template T
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    public function get(string $id)
    {
        if (array_key_exists($id, $this->initializedSingletons)) {
            return $this->initializedSingletons[$id];
        }

        if (array_key_exists($id, $this->singletons)) {
            return $this->initializedSingletons[$id] = $this->singletons[$id]();
        }

        if (array_key_exists($id, $this->definitions)) {
            return $this->definitions[$id]();
        }

        throw ContainerEntryNotFoundException::make($id);
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->definitions) || array_key_exists($id, $this->singletons);
    }

    public function reset(): void
    {
        $this->definitions = [];
        $this->singletons = [];
        $this->initializedSingletons = [];
    }
}
