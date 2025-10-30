<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Exceptions\SerializationException;

/**
 * Context object containing serialization configuration and state.
 *
 * This object ct is passed through the serialization process and maintains
 * configuration options, depth tracking, and visited object tracking.
 */
class SerializationContext
{
    /** @var array<int, int> */
    private array $visitedObjects = [];

    private int $currentDepth = 0;

    /**
     * Create a new serialization context.
     *
     * @param  int  $maxDepth  Maximum nesting depth (default: 512)
     * @param  bool  $detectCircularReferences  Enable circular reference detection (default: true)
     * @param  bool  $prettyPrint  Enable pretty printing with indentation (default: false)
     * @param  bool  $strictTypes  Enable strict type checking (default: true)
     * @param  string|null  $version  Version for versioned serialization (default: null)
     */
    public function __construct(
        private readonly int $maxDepth = 512,
        private readonly bool $detectCircularReferences = true,
        private readonly bool $prettyPrint = false,
        private readonly bool $strictTypes = true,
        private readonly ?string $version = null,
    ) {}

    /**
     * Get the current depth.
     */
    public function getCurrentDepth(): int
    {
        return $this->currentDepth;
    }

    /**
     * Check if pretty printing is enabled.
     */
    public function shouldPrettyPrint(): bool
    {
        return $this->prettyPrint;
    }

    /**
     * Increase depth counter.
     *
     * @throws SerializationException If max depth is exceeded
     */
    public function increaseDepth(): void
    {
        $this->currentDepth++;

        if ($this->currentDepth > $this->maxDepth) {
            throw new SerializationException(
                "Maximum depth of {$this->maxDepth} exceeded"
            );
        }
    }

    /**
     * Decrease depth counter.
     */
    public function decreaseDepth(): void
    {
        if ($this->currentDepth > 0) {
            $this->currentDepth--;
        }
    }

    /**
     * Mark an object as visited.
     *
     * @param  object  $object  The object to mark
     *
     * @throws SerializationException If circular reference detected
     */
    public function visitObject(object $object): void
    {
        if (! $this->detectCircularReferences) {
            return;
        }

        $objectId = spl_object_id($object);

        if (in_array($objectId, $this->visitedObjects, true)) {
            throw new SerializationException(
                'Circular reference detected for object of class '.get_class($object)
            );
        }

        $this->visitedObjects[] = $objectId;
    }

    /**
     * Mark an object as no longer being visited.
     *
     * @param  object  $object  The object to unmark
     */
    public function leaveObject(object $object): void
    {
        if (! $this->detectCircularReferences) {
            return;
        }

        $objectId = spl_object_id($object);
        $key = array_search($objectId, $this->visitedObjects, true);

        if (is_int($key)) {
            unset($this->visitedObjects[$key]);
        }
    }

    /**
     * Get the indentation string for the current depth.
     */
    public function getIndentation(): string
    {
        if (! $this->prettyPrint) {
            return '';
        }

        return str_repeat('    ', $this->currentDepth);
    }

    /**
     * Get the line break character(s).
     */
    public function getLineBreak(): string
    {
        return $this->prettyPrint ? "\n" : '';
    }

    /**
     * Get the space after colon.
     */
    public function getColonSpace(): string
    {
        return $this->prettyPrint ? ' ' : '';
    }

    /**
     * Get the version for versioned serialization.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }
}
