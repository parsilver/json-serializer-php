<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

/**
 * Event dispatched when a serialization error occurs.
 *
 * Listeners can log errors, provide fallback values, or handle errors gracefully.
 */
final class SerializationErrorEvent extends AbstractEvent
{
    private bool $handled = false;

    private mixed $fallbackValue = null;

    /**
     * Create a new serialization error event.
     *
     * @param  \Throwable  $error  The exception that occurred
     * @param  mixed  $value  The value that failed to serialize
     * @param  string  $propertyPath  The property path where the error occurred (empty for top-level)
     */
    public function __construct(
        private readonly \Throwable $error,
        private readonly mixed $value,
        private readonly string $propertyPath = ''
    ) {}

    /**
     * Get the error that occurred.
     */
    public function getError(): \Throwable
    {
        return $this->error;
    }

    /**
     * Get the value that failed to serialize.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the property path where the error occurred.
     */
    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    /**
     * Mark the error as handled.
     *
     * When marked as handled, the error won't be thrown and
     * the fallback value will be used instead.
     */
    public function markAsHandled(mixed $fallbackValue = null): void
    {
        $this->handled = true;
        $this->fallbackValue = $fallbackValue;
    }

    /**
     * Check if the error has been handled.
     */
    public function isHandled(): bool
    {
        return $this->handled;
    }

    /**
     * Get the fallback value (if error was handled).
     */
    public function getFallbackValue(): mixed
    {
        return $this->fallbackValue;
    }
}
