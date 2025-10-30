<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types;

use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Exceptions\TypeException;

/**
 * Registry for type handlers.
 *
 * This class maintains a collection of type handlers and provides
 * methods to register new handlers and find the appropriate handler
 * for a given value.
 */
class TypeRegistry
{
    /** @var array<TypeHandlerInterface> */
    private array $handlers = [];

    private bool $sorted = false;

    /**
     * Register a type handler.
     *
     * @param  TypeHandlerInterface  $handler  The handler to register
     */
    public function register(TypeHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
        $this->sorted = false;
    }

    /**
     * Find a handler that can serialize the given value.
     *
     * @param  mixed  $value  The value to find a handler for
     *
     * @throws TypeException If no handler found
     */
    public function findHandler(mixed $value): TypeHandlerInterface
    {
        $this->ensureSorted();

        foreach ($this->handlers as $handler) {
            if ($handler->supports($value)) {
                return $handler;
            }
        }

        $type = get_debug_type($value);
        throw new TypeException("No handler found for type: {$type}");
    }

    /**
     * Ensure handlers are sorted by priority.
     */
    private function ensureSorted(): void
    {
        if ($this->sorted) {
            return;
        }

        usort($this->handlers, function (TypeHandlerInterface $a, TypeHandlerInterface $b): int {
            // Sort in descending order (higher priority first)
            return $b->getPriority() <=> $a->getPriority();
        });

        $this->sorted = true;
    }
}
