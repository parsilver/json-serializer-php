<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

/**
 * Base interface for all serialization events.
 *
 * Events are dispatched at various points during the serialization
 * and deserialization process, allowing users to hook into the lifecycle.
 */
interface Event
{
    /**
     * Check if event propagation has been stopped.
     */
    public function isPropagationStopped(): bool;

    /**
     * Stop event propagation.
     *
     * When stopped, no further listeners will be called for this event.
     */
    public function stopPropagation(): void;
}
