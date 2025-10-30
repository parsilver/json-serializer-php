<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types\Handlers;

use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\Types\TypeDetector;

/**
 * Handler for PHP arrays.
 *
 * This handler serializes PHP arrays to JSON arrays or objects,
 * depending on whether the array is associative or sequential.
 */
class ArrayTypeHandler implements TypeHandlerInterface
{
    private TypeDetector $detector;

    public function __construct()
    {
        $this->detector = new TypeDetector;
    }

    #[\Override]
    public function supports(mixed $value): bool
    {
        return is_array($value);
    }

    #[\Override]
    public function serialize(mixed $value, SerializationContext $context): string
    {
        if (! is_array($value)) {
            return 'null';
        }

        // Empty arrays are always serialized as []
        if ($value === []) {
            return '[]';
        }

        $context->increaseDepth();

        try {
            if ($this->detector->isAssociativeArray($value)) {
                return $this->serializeAssociativeArray($value, $context);
            }

            // Cast to sequential array type for PHPStan
            /** @var array<int, mixed> $sequentialArray */
            $sequentialArray = array_values($value);

            return $this->serializeSequentialArray($sequentialArray, $context);
        } finally {
            $context->decreaseDepth();
        }
    }

    #[\Override]
    public function getPriority(): int
    {
        return 50; // Medium priority
    }

    /**
     * Serialize a sequential (indexed) array.
     *
     * @param  array<int, mixed>  $array  The array to serialize
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON representation
     */
    private function serializeSequentialArray(array $array, SerializationContext $context): string
    {
        // For sequential arrays, we need a way to serialize values
        // For now, we'll use a simple approach - this will be improved when we have the full engine
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
     * Serialize an associative array.
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
     * Serialize a value (temporary implementation - will use registry later).
     *
     * @param  mixed  $value  The value to serialize
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON representation
     */
    private function serializeValue(mixed $value, SerializationContext $context): string
    {
        // Temporary simple serialization - this will be replaced when we integrate with the engine
        // For now, handle the basic cases
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded !== false ? $encoded : '""';
        }

        if (is_array($value)) {
            return $this->serialize($value, $context);
        }

        // For objects and other types, return null for now
        // This will be properly handled when integrated with the full type system
        return 'null';
    }
}
