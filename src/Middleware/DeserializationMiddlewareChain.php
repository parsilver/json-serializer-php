<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Middleware;

use Farzai\JsonSerializer\Engine\DeserializationContext;

/**
 * Manages and executes a chain of deserialization middleware.
 *
 * Middleware are executed in the order they were added, with each
 * middleware having the ability to modify JSON and results before and after
 * passing to the next middleware in the chain.
 */
final class DeserializationMiddlewareChain
{
    /**
     * @var array<DeserializationMiddleware>
     */
    private array $middleware = [];

    /**
     * Create a new middleware chain.
     *
     * @param  array<DeserializationMiddleware>  $middleware  Initial middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    /**
     * Add middleware to the chain.
     */
    public function add(DeserializationMiddleware $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Execute the middleware chain.
     *
     * @param  string  $json  The JSON to deserialize
     * @param  string  $className  The target class name
     * @param  DeserializationContext  $context  The deserialization context
     * @param  callable(string, string, DeserializationContext): object  $core  The core deserialization logic
     * @return object The deserialized object
     */
    public function execute(string $json, string $className, DeserializationContext $context, callable $core): object
    {
        // Build the chain from the inside out
        $next = $core;

        // Wrap each middleware around the previous $next
        // Process in reverse order so first middleware wraps everything
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = fn (string $j, string $c, DeserializationContext $ctx): object => $middleware->handle($j, $c, $ctx, $next);
        }

        // Execute the chain
        return $next($json, $className, $context);
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
