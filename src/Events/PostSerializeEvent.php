<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Event dispatched after serialization completes.
 *
 * Listeners can inspect and modify the JSON result.
 */
final class PostSerializeEvent extends AbstractEvent
{
    /**
     * Create a new post-serialize event.
     *
     * @param  mixed  $originalValue  The original value that was serialized
     * @param  string  $json  The resulting JSON string
     * @param  SerializationContext  $context  The serialization context
     */
    public function __construct(
        private readonly mixed $originalValue,
        private string $json,
        private readonly SerializationContext $context
    ) {}

    /**
     * Get the original value that was serialized.
     */
    public function getOriginalValue(): mixed
    {
        return $this->originalValue;
    }

    /**
     * Get the JSON result.
     */
    public function getJson(): string
    {
        return $this->json;
    }

    /**
     * Set a new JSON result.
     *
     * This allows listeners to transform the JSON output.
     */
    public function setJson(string $json): void
    {
        $this->json = $json;
    }

    /**
     * Get the serialization context.
     */
    public function getContext(): SerializationContext
    {
        return $this->context;
    }
}
