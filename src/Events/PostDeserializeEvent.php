<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

use Farzai\JsonSerializer\Engine\DeserializationContext;

/**
 * Event dispatched after deserialization completes.
 *
 * Listeners can inspect and modify the deserialized result.
 */
final class PostDeserializeEvent extends AbstractEvent
{
    /**
     * Create a new post-deserialize event.
     *
     * @param  string  $originalJson  The original JSON string
     * @param  mixed  $result  The deserialized result
     * @param  DeserializationContext  $context  The deserialization context
     */
    public function __construct(
        private readonly string $originalJson,
        private mixed $result,
        private readonly DeserializationContext $context
    ) {}

    /**
     * Get the original JSON string.
     */
    public function getOriginalJson(): string
    {
        return $this->originalJson;
    }

    /**
     * Get the deserialized result.
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Set a new result.
     *
     * This allows listeners to transform or replace the deserialized object.
     */
    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    /**
     * Get the deserialization context.
     */
    public function getContext(): DeserializationContext
    {
        return $this->context;
    }
}
