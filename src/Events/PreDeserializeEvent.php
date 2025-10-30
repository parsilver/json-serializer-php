<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

use Farzai\JsonSerializer\Engine\DeserializationContext;

/**
 * Event dispatched before deserialization begins.
 *
 * Listeners can inspect and modify the JSON input before it's deserialized.
 */
final class PreDeserializeEvent extends AbstractEvent
{
    /**
     * Create a new pre-deserialize event.
     *
     * @param  string  $json  The JSON string to be deserialized
     * @param  class-string|null  $className  The target class (null for raw deserialization)
     * @param  DeserializationContext  $context  The deserialization context
     */
    public function __construct(
        private string $json,
        private readonly ?string $className,
        private readonly DeserializationContext $context
    ) {}

    /**
     * Get the JSON string to be deserialized.
     */
    public function getJson(): string
    {
        return $this->json;
    }

    /**
     * Set a new JSON string to be deserialized.
     *
     * This allows listeners to transform the input before deserialization.
     */
    public function setJson(string $json): void
    {
        $this->json = $json;
    }

    /**
     * Get the target class name (if any).
     *
     * @return class-string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Get the deserialization context.
     */
    public function getContext(): DeserializationContext
    {
        return $this->context;
    }
}
