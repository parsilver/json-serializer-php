<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types\Handlers;

use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Generator;

/**
 * Handler for PHP Generators.
 *
 * This handler serializes PHP generators to JSON arrays incrementally,
 * allowing memory-efficient serialization of large datasets.
 * Generators are consumed during serialization.
 */
final class GeneratorTypeHandler implements TypeHandlerInterface
{
    // No dependencies needed

    #[\Override]
    public function supports(mixed $value): bool
    {
        return $value instanceof Generator;
    }

    #[\Override]
    public function serialize(mixed $value, SerializationContext $context): string
    {
        if (! $value instanceof Generator) {
            return 'null';
        }

        $context->increaseDepth();

        try {
            // Check if generator yields key-value pairs (associative) or just values (sequential)
            // We need to peek at first item to determine this
            $firstKey = null;
            $firstValue = null;
            $hasItems = false;

            // Get the first item to determine array type
            if ($value->valid()) {
                $firstKey = $value->key();
                $firstValue = $value->current();
                $hasItems = true;
            }

            // If no items, return empty array
            if (! $hasItems) {
                return '[]';
            }

            // Determine if this is associative based on first key
            $isAssociative = ! is_int($firstKey) || $firstKey !== 0;

            // Collect all items to determine final structure
            // Note: This consumes the generator, but allows us to detect if keys are sequential
            /** @var array<string|int, mixed> $items */
            $items = [];
            $currentIndex = 0;

            // Add the first item we already fetched
            // Ensure array key is string or int (for PHPStan)
            if (is_string($firstKey) || is_int($firstKey)) {
                $items[$firstKey] = $firstValue;
            } else {
                // Convert to string for non-string/int keys (should not happen in practice)
                $items[0] = $firstValue;
            }
            $value->next();
            $currentIndex++;

            // Collect remaining items
            while ($value->valid()) {
                $key = $value->key();
                $val = $value->current();

                // Only process valid array keys (string or int)
                if (! is_string($key) && ! is_int($key)) {
                    $value->next();

                    continue;
                }

                // Check if keys are still sequential
                if (! $isAssociative && is_int($key) && $key === $currentIndex) {
                    // Still sequential
                    $items[$key] = $val;
                    $currentIndex++;
                } else {
                    // Not sequential, treat as associative
                    $isAssociative = true;
                    $items[$key] = $val;
                }

                $value->next();
            }

            // Serialize based on detected type
            if ($isAssociative) {
                return $this->serializeAssociativeArray($items, $context);
            }

            return $this->serializeSequentialArray(array_values($items), $context);
        } finally {
            $context->decreaseDepth();
        }
    }

    #[\Override]
    public function getPriority(): int
    {
        return 60; // Higher than ArrayTypeHandler (50) to check generators first
    }

    /**
     * Serialize a sequential (indexed) array from generator values.
     *
     * @param  array<int, mixed>  $array  The array to serialize
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON representation
     */
    private function serializeSequentialArray(array $array, SerializationContext $context): string
    {
        $elements = [];

        foreach ($array as $value) {
            $elements[] = $this->serializeValue($value, $context);
        }

        if ($context->shouldPrettyPrint()) {
            $context->getLineBreak();
            $indent = $context->getIndentation();
            $innerIndent = str_repeat('    ', $context->getCurrentDepth() + 1);

            return "[\n{$innerIndent}".implode(",\n{$innerIndent}", $elements)."\n{$indent}]";
        }

        return '['.implode(',', $elements).']';
    }

    /**
     * Serialize an associative array from generator key-value pairs.
     *
     * @param  array<string|int, mixed>  $array  The array to serialize
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON representation
     */
    private function serializeAssociativeArray(array $array, SerializationContext $context): string
    {
        $pairs = [];

        foreach ($array as $key => $value) {
            $jsonKey = json_encode((string) $key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $jsonValue = $this->serializeValue($value, $context);
            $colon = ':'.$context->getColonSpace();
            $pairs[] = $jsonKey.$colon.$jsonValue;
        }

        if ($context->shouldPrettyPrint()) {
            $context->getLineBreak();
            $indent = $context->getIndentation();
            $innerIndent = str_repeat('    ', $context->getCurrentDepth() + 1);

            return "{\n{$innerIndent}".implode(",\n{$innerIndent}", $pairs)."\n{$indent}}";
        }

        return '{'.implode(',', $pairs).'}';
    }

    /**
     * Serialize a value (handles basic types, delegates complex types to other handlers).
     *
     * @param  mixed  $value  The value to serialize
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON representation
     */
    private function serializeValue(mixed $value, SerializationContext $context): string
    {
        // Handle null
        if (is_null($value)) {
            return 'null';
        }

        // Handle booleans
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // Handle numbers
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        // Handle strings
        if (is_string($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded !== false ? $encoded : '""';
        }

        // Handle arrays - delegate to ArrayTypeHandler
        if (is_array($value)) {
            $arrayHandler = new ArrayTypeHandler;

            return $arrayHandler->serialize($value, $context);
        }

        // Handle nested generators before generic objects
        if ($value instanceof Generator) {
            return $this->serialize($value, $context);
        }

        // Handle objects - delegate to ObjectTypeHandler
        if (is_object($value)) {
            $objectHandler = new ObjectTypeHandler;

            return $objectHandler->serialize($value, $context);
        }

        return 'null';
    }
}
