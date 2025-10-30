<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Transformers;

use Farzai\JsonSerializer\Contracts\TransformerInterface;

/**
 * Registry for managing transformer instances.
 *
 * Provides a central place to register and retrieve transformers
 * by class name, with lazy instantiation.
 */
class TransformerRegistry
{
    /**
     * @var array<class-string, TransformerInterface>
     */
    private array $instances = [];

    /**
     * @var array<class-string, class-string<TransformerInterface>>
     */
    private array $registered = [];

    public function __construct()
    {
        // Register built-in transformers
        $this->register(DateTimeTransformer::class);
        $this->register(EnumTransformer::class);
    }

    /**
     * Register a transformer class.
     *
     * @param  class-string<TransformerInterface>  $transformerClass
     */
    public function register(string $transformerClass): void
    {
        $this->registered[$transformerClass] = $transformerClass;
    }

    /**
     * Get a transformer instance by class name.
     *
     * @param  class-string<TransformerInterface>  $transformerClass
     */
    public function get(string $transformerClass): TransformerInterface
    {
        // Return cached instance
        if (isset($this->instances[$transformerClass])) {
            return $this->instances[$transformerClass];
        }

        // Create new instance
        if (isset($this->registered[$transformerClass])) {
            $instance = new $transformerClass;
            $this->instances[$transformerClass] = $instance;

            return $instance;
        }

        // Try to instantiate directly if not registered
        if (class_exists($transformerClass)) {
            $instance = new $transformerClass;
            $this->instances[$transformerClass] = $instance;
            $this->registered[$transformerClass] = $transformerClass;

            return $instance;
        }

        throw new \InvalidArgumentException("Transformer class not found: {$transformerClass}");
    }
}
