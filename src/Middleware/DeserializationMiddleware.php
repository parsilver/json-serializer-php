<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Middleware;

use Farzai\JsonSerializer\Engine\DeserializationContext;

/**
 * Middleware for intercepting and modifying deserialization.
 *
 * Middleware can inspect or transform JSON and results before and after deserialization,
 * add logging, validation, or implement cross-cutting concerns.
 */
interface DeserializationMiddleware
{
    /**
     * Handle deserialization with ability to pass to next middleware.
     *
     * The middleware can:
     * - Inspect/modify the JSON before deserialization
     * - Execute code before calling $next
     * - Call $next to continue the chain
     * - Execute code after $next returns
     * - Inspect/modify the result
     *
     * @param  string  $json  The JSON being deserialized
     * @param  string  $className  The target class name
     * @param  DeserializationContext  $context  The deserialization context
     * @param  callable(string, string, DeserializationContext): object  $next  Next middleware in chain
     * @return object The deserialized object
     */
    public function handle(string $json, string $className, DeserializationContext $context, callable $next): object;
}
