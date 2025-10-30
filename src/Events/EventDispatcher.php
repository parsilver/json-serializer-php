<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Events;

/**
 * Event dispatcher for managing and dispatching serialization events.
 *
 * This dispatcher allows registering listeners for various events
 * and dispatching those events during the serialization process.
 */
final class EventDispatcher
{
    /**
     * @var array<class-string<Event>, array<array{callback: callable(Event): void, priority: int}>>
     */
    private array $listeners = [];

    /**
     * Add a listener for a specific event type.
     *
     * @param  class-string<Event>  $eventClass  The event class to listen for
     * @param  callable(Event): void  $listener  The listener callback
     * @param  int  $priority  Higher priority listeners are called first (default: 0)
     */
    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        if (! isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = [
            'callback' => $listener,
            'priority' => $priority,
        ];

        // Sort by priority (higher first)
        usort($this->listeners[$eventClass], fn ($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @template T of Event
     *
     * @param  T  $event  The event to dispatch
     * @return T The event after all listeners have been called
     */
    public function dispatch(Event $event): Event
    {
        $eventClass = get_class($event);

        if (! isset($this->listeners[$eventClass])) {
            return $event;
        }

        foreach ($this->listeners[$eventClass] as $listenerData) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $listener = $listenerData['callback'];
            $listener($event);
        }

        return $event;
    }

    /**
     * Check if there are any listeners for an event type.
     *
     * @param  class-string<Event>  $eventClass
     */
    public function hasListeners(string $eventClass): bool
    {
        return isset($this->listeners[$eventClass]) && count($this->listeners[$eventClass]) > 0;
    }

    /**
     * Remove all listeners for a specific event type.
     *
     * @param  class-string<Event>  $eventClass
     */
    public function removeListeners(string $eventClass): void
    {
        unset($this->listeners[$eventClass]);
    }

    /**
     * Remove all listeners.
     */
    public function clearListeners(): void
    {
        $this->listeners = [];
    }

    /**
     * Get the number of listeners for a specific event type.
     *
     * @param  class-string<Event>  $eventClass
     */
    public function getListenerCount(string $eventClass): int
    {
        return isset($this->listeners[$eventClass]) ? count($this->listeners[$eventClass]) : 0;
    }
}
