<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Builder;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Builder for creating serfinal ialization context instances.
 *
 * This builder provides a fluent interface for configuring
 * serialization context options.
 */
class ContextBuilder
{
    private int $maxDepth = 512;

    private bool $detectCircularReferences = true;

    private bool $prettyPrint = false;

    private bool $strictTypes = true;

    /**
     * Set the maximum nesting depth.
     *
     * @param  int  $depth  The maximum depth (must be positive)
     * @return $this
     */
    public function withMaxDepth(int $depth): self
    {
        if ($depth < 1) {
            throw new \InvalidArgumentException('Max depth must be at least 1');
        }

        $this->maxDepth = $depth;

        return $this;
    }

    /**
     * Enable or disable circular reference detection.
     *
     * @param  bool  $enabled  Whether to detect circular references
     * @return $this
     */
    public function withCircularReferenceDetection(bool $enabled = true): self
    {
        $this->detectCircularReferences = $enabled;

        return $this;
    }

    /**
     * Enable or disable pretty printing.
     *
     * @param  bool  $enabled  Whether to enable pretty printing
     * @return $this
     */
    public function withPrettyPrint(bool $enabled = true): self
    {
        $this->prettyPrint = $enabled;

        return $this;
    }

    /**
     * Enable pretty printing (alias for withPrettyPrint(true)).
     *
     * @return $this
     */
    public function prettyPrint(): self
    {
        return $this->withPrettyPrint(true);
    }

    /**
     * Enable or disable strict type checking.
     *
     * @param  bool  $enabled  Whether to enable strict types
     * @return $this
     */
    public function withStrictTypes(bool $enabled = true): self
    {
        $this->strictTypes = $enabled;

        return $this;
    }

    /**
     * Enable strict type checking (alias for withStrictTypes(true)).
     *
     * @return $this
     */
    public function strict(): self
    {
        return $this->withStrictTypes(true);
    }

    /**
     * Build the serialization context.
     */
    public function build(): SerializationContext
    {
        return new SerializationContext(
            maxDepth: $this->maxDepth,
            detectCircularReferences: $this->detectCircularReferences,
            prettyPrint: $this->prettyPrint,
            strictTypes: $this->strictTypes,
        );
    }
}
