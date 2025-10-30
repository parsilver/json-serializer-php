<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types;

use Farzai\JsonSerializer\Types\Handlers\ArrayTypeHandler;
use Farzai\JsonSerializer\Types\Handlers\EnumTypeHandler;
use Farzai\JsonSerializer\Types\Handlers\ObjectTypeHandler;
use Farzai\JsonSerializer\Types\Handlers\ScalarTypeHandler;

/**
 * Factory for creating and registering default type handlers.
 *
 * This factory creates the standard set of type handlers and
 * registers them with a type registry in the correct priority order.
 */
class TypeHandlerFactory
{
    /**
     * Create a type registry with all default handlers registered.
     */
    public function createDefaultRegistry(): TypeRegistry
    {
        $registry = new TypeRegistry;

        // Register handlers in priority order (most specific first)
        $this->registerDefaultHandlers($registry);

        return $registry;
    }

    /**
     * Register all default type handlers.
     *
     * @param  TypeRegistry  $registry  The registry to register handlers with
     */
    public function registerDefaultHandlers(TypeRegistry $registry): void
    {
        // Register in order of specificity/priority
        $registry->register(new ScalarTypeHandler);
        $registry->register(new EnumTypeHandler);
        $registry->register(new ArrayTypeHandler);
        $registry->register(new ObjectTypeHandler);
    }
}
