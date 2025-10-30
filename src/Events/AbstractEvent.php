<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

/**
 * Abstract base event with common functionality.
 */
abstract class AbstractEvent implements Event
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
