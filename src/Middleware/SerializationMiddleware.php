<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Middleware;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Middleware for intercepting and modifying serialization.
 *
 * Middleware can inspect or transform values before and after serialization,
 * add logging, validation, or implement cross-cutting concerns.
 */
interface SerializationMiddleware
{
    /**
     * Handle serialization with ability to pass to next middleware.
     *
     * The middleware can:
     * - Inspect/modify the value before serialization
     * - Execute code before calling $next
     * - Call $next to continue the chain
     * - Execute code after $next returns
     * - Inspect/modify the JSON result
     *
     * @param  mixed  $value  The value being serialized
     * @param  SerializationContext  $context  The serialization context
     * @param  callable(mixed, SerializationContext): string  $next  Next middleware in chain
     * @return string The JSON result
     */
    public function handle(mixed $value, SerializationContext $context, callable $next): string;
}
