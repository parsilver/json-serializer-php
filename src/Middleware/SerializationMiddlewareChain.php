<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Middleware;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Manages and executes a chain of serialization middleware.
 *
 * Middleware are executed in the order they were added, with each
 * middleware having the ability to modify values before and after
 * passing to the next middleware in the chain.
 */
final class SerializationMiddlewareChain
{
    /**
     * @var array<SerializationMiddleware>
     */
    private array $middleware = [];

    /**
     * Create a new middleware chain.
     *
     * @param  array<SerializationMiddleware>  $middleware  Initial middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    /**
     * Add middleware to the chain.
     */
    public function add(SerializationMiddleware $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Execute the middleware chain.
     *
     * @param  mixed  $value  The value to serialize
     * @param  SerializationContext  $context  The serialization context
     * @param  callable(mixed, SerializationContext): string  $core  The core serialization logic
     * @return string The JSON result
     */
    public function execute(mixed $value, SerializationContext $context, callable $core): string
    {
        // Build the chain from the inside out
        $next = $core;

        // Wrap each middleware around the previous $next
        // Process in reverse order so first middleware wraps everything
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = fn (mixed $v, SerializationContext $ctx): string => $middleware->handle($v, $ctx, $next);
        }

        // Execute the chain
        return $next($value, $context);
    }

    /**
     * Check if the chain has any middleware.
     */
    public function isEmpty(): bool
    {
        return empty($this->middleware);
    }

    /**
     * Get the number of middleware in the chain.
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Clear all middleware from the chain.
     */
    public function clear(): void
    {
        $this->middleware = [];
    }
}
