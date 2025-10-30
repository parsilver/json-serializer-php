<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Contracts;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Interface for type-specific serialization handlers.
 *
 * Type handlers are responsible for converting specific PHP types
 * to their JSON representation. Each handler supports one or more
 * PHP types and provides the logic to serialize values of those types.
 */
interface TypeHandlerInterface
{
    /**
     * Check if this handler can serialize the given value.
     *
     * @param  mixed  $value  The value to check
     * @return bool True if this handler can handle the value, false otherwise
     */
    public function supports(mixed $value): bool;

    /**
     * Serialize the given value to JSON format.
     *
     * @param  mixed  $value  The value to serialize
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON representation of the value
     *
     * @throws \Farzai\JsonSerializer\Exceptions\SerializationException If serialization fails
     */
    public function serialize(mixed $value, SerializationContext $context): string;

    /**
     * Get the priority of this handler.
     *
     * Handlers with higher priority are checked first. This allows
     * more specific handlers to override generic ones.
     *
     * @return int The priority (higher = checked first)
     */
    public function getPriority(): int;
}
