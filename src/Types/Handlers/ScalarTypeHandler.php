<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types\Handlers;

use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Handler for scalar types (string, int, float, bool, null).
 *
 * This handler serializes PHP scalar values to their JSON
 * representation according to the JSON specification.
 */
class ScalarTypeHandler implements TypeHandlerInterface
{
    #[\Override]
    public function supports(mixed $value): bool
    {
        return is_scalar($value) || is_null($value);
    }

    #[\Override]
    public function serialize(mixed $value, SerializationContext $context): string
    {
        return match (true) {
            is_null($value) => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => $this->serializeNumber($value),
            is_string($value) => $this->serializeString($value),
            default => 'null', // Fallback, should not happen
        };
    }

    #[\Override]
    public function getPriority(): int
    {
        return 100; // High priority for scalar types
    }

    /**
     * Serialize a number (int or float).
     *
     * @param  int|float  $value  The number to serialize
     * @return string The JSON representation
     */
    private function serializeNumber(int|float $value): string
    {
        // Handle special float values
        if (is_float($value)) {
            if (is_infinite($value)) {
                return $value > 0 ? '"Infinity"' : '"-Infinity"';
            }

            if (is_nan($value)) {
                return '"NaN"';
            }
        }

        return (string) $value;
    }

    /**
     * Serialize a string with proper escaping.
     *
     * @param  string  $value  The string to serialize
     * @return string The JSON representation
     */
    private function serializeString(string $value): string
    {
        // Use json_encode for proper string escaping
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // json_encode should never fail for strings, but handle it gracefully
        if ($encoded === false) {
            // Fallback: escape manually
            return '"'.$this->escapeString($value).'"';
        }

        return $encoded;
    }

    /**
     * Manually escape a string for JSON.
     *
     * @param  string  $value  The string to escape
     * @return string The escaped string
     */
    private function escapeString(string $value): string
    {
        $replacements = [
            '\\' => '\\\\',
            '"' => '\\"',
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
            "\b" => '\\b',
            "\f" => '\\f',
        ];

        return strtr($value, $replacements);
    }
}
