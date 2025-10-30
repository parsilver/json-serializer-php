<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Event dispatched before serialization begins.
 *
 * Listeners can inspect and modify the value before it's serialized.
 */
final class PreSerializeEvent extends AbstractEvent
{
    /**
     * Create a new pre-serialize event.
     *
     * @param  mixed  $value  The value to be serialized
     * @param  SerializationContext  $context  The serialization context
     */
    public function __construct(
        private mixed $value,
        private readonly SerializationContext $context
    ) {}

    /**
     * Get the value to be serialized.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set a new value to be serialized.
     *
     * This allows listeners to transform the value before serialization.
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Get the serialization context.
     */
    public function getContext(): SerializationContext
    {
        return $this->context;
    }
}
