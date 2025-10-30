<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types;

use Farzai\JsonSerializer\Exceptions\DeserializationException;

/**
 * Type coercion utility for converting values between types.
 *
 * Handles type conversions based on the configured coercion mode (STRICT, SAFE, LENIENT).
 */
class TypeCoercer
{
    /**
     * Coerce a value to the specified type.
     *
     * @param  mixed  $value  The value to coerce
     * @param  string  $targetType  The target type (string, int, float, bool, array)
     * @param  TypeCoercionMode  $mode  The coercion mode
     * @param  string  $propertyPath  The property path for error reporting
     * @return mixed The coerced value
     *
     * @throws DeserializationException If coercion fails
     */
    public function coerce(mixed $value, string $targetType, TypeCoercionMode $mode, string $propertyPath = ''): mixed
    {
        return match ($targetType) {
            'string' => $this->coerceToString($value, $mode, $propertyPath),
            'int' => $this->coerceToInt($value, $mode, $propertyPath),
            'float' => $this->coerceToFloat($value, $mode, $propertyPath),
            'bool' => $this->coerceToBool($value, $mode, $propertyPath),
            'array' => $this->coerceToArray($value, $mode, $propertyPath),
            default => $value, // Unknown type, return as-is
        };
    }

    /**
     * Coerce a value to string.
     */
    private function coerceToString(mixed $value, TypeCoercionMode $mode, string $propertyPath): string
    {
        if (is_string($value)) {
            return $value;
        }

        return match ($mode) {
            TypeCoercionMode::STRICT => throw DeserializationException::typeMismatch(
                $propertyPath,
                'string',
                $this->getTypeName($value)
            ),
            TypeCoercionMode::SAFE => $this->safeCoerceToString($value, $propertyPath),
            TypeCoercionMode::LENIENT => $this->lenientCoerceToString($value),
        };
    }

    /**
     * Coerce a value to int.
     */
    private function coerceToInt(mixed $value, TypeCoercionMode $mode, string $propertyPath): int
    {
        if (is_int($value)) {
            return $value;
        }

        return match ($mode) {
            TypeCoercionMode::STRICT => throw DeserializationException::typeMismatch(
                $propertyPath,
                'int',
                $this->getTypeName($value)
            ),
            TypeCoercionMode::SAFE => $this->safeCoerceToInt($value, $propertyPath),
            TypeCoercionMode::LENIENT => $this->lenientCoerceToInt($value, $propertyPath),
        };
    }

    /**
     * Coerce a value to float.
     */
    private function coerceToFloat(mixed $value, TypeCoercionMode $mode, string $propertyPath): float
    {
        if (is_float($value)) {
            return $value;
        }

        // Allow int to float in all modes (widening conversion)
        if (is_int($value)) {
            return (float) $value;
        }

        return match ($mode) {
            TypeCoercionMode::STRICT => throw DeserializationException::typeMismatch(
                $propertyPath,
                'float',
                $this->getTypeName($value)
            ),
            TypeCoercionMode::SAFE => $this->safeCoerceToFloat($value, $propertyPath),
            TypeCoercionMode::LENIENT => $this->lenientCoerceToFloat($value, $propertyPath),
        };
    }

    /**
     * Coerce a value to bool.
     */
    private function coerceToBool(mixed $value, TypeCoercionMode $mode, string $propertyPath): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return match ($mode) {
            TypeCoercionMode::STRICT => throw DeserializationException::typeMismatch(
                $propertyPath,
                'bool',
                $this->getTypeName($value)
            ),
            TypeCoercionMode::SAFE => $this->safeCoerceToBool($value, $propertyPath),
            TypeCoercionMode::LENIENT => $this->lenientCoerceToBool($value),
        };
    }

    /**
     * Coerce a value to array.
     *
     * @return array<mixed>
     */
    private function coerceToArray(mixed $value, TypeCoercionMode $mode, string $propertyPath): array
    {
        if (is_array($value)) {
            return $value;
        }

        return match ($mode) {
            TypeCoercionMode::STRICT => throw DeserializationException::typeMismatch(
                $propertyPath,
                'array',
                $this->getTypeName($value)
            ),
            TypeCoercionMode::SAFE, TypeCoercionMode::LENIENT => throw DeserializationException::typeMismatch(
                $propertyPath,
                'array',
                $this->getTypeName($value)
            ),
        };
    }

    /**
     * SAFE mode: Coerce to string.
     */
    private function safeCoerceToString(mixed $value, string $propertyPath): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        throw DeserializationException::typeMismatch(
            $propertyPath,
            'string',
            $this->getTypeName($value)
        );
    }

    /**
     * SAFE mode: Coerce to int.
     */
    private function safeCoerceToInt(mixed $value, string $propertyPath): int
    {
        // Allow numeric strings
        if (is_string($value) && is_numeric($value)) {
            $result = filter_var($value, FILTER_VALIDATE_INT);
            if ($result !== false) {
                return $result;
            }

            // Try float conversion then to int (for strings like "123.0")
            $floatValue = (float) $value;
            $intValue = (int) $floatValue;
            if ($floatValue == $intValue) {
                return $intValue;
            }
        }

        // Allow float if it's a whole number
        if (is_float($value) && $value == (int) $value) {
            return (int) $value;
        }

        throw DeserializationException::typeMismatch(
            $propertyPath,
            'int',
            $this->getTypeName($value)
        );
    }

    /**
     * SAFE mode: Coerce to float.
     */
    private function safeCoerceToFloat(mixed $value, string $propertyPath): float
    {
        // Allow numeric strings
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw DeserializationException::typeMismatch(
            $propertyPath,
            'float',
            $this->getTypeName($value)
        );
    }

    /**
     * SAFE mode: Coerce to bool.
     */
    private function safeCoerceToBool(mixed $value, string $propertyPath): bool
    {
        // Allow 1/0
        if ($value === 1 || $value === 0) {
            return (bool) $value;
        }

        // Allow "true"/"false" strings
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true') {
                return true;
            }
            if ($lower === 'false') {
                return false;
            }

            // Allow "1"/"0" strings
            if ($value === '1') {
                return true;
            }
            if ($value === '0') {
                return false;
            }
        }

        throw DeserializationException::typeMismatch(
            $propertyPath,
            'bool',
            $this->getTypeName($value)
        );
    }

    /**
     * LENIENT mode: Coerce to string.
     */
    private function lenientCoerceToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            $encoded = json_encode($value);

            return $encoded !== false ? $encoded : '[]';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            $encoded = json_encode($value);

            return $encoded !== false ? $encoded : '{}';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * LENIENT mode: Coerce to int.
     */
    private function lenientCoerceToInt(mixed $value, string $propertyPath): int
    {
        if (is_string($value)) {
            // Try to extract number from string
            if (is_numeric($value)) {
                return (int) $value;
            }

            // Extract first number from string
            if (preg_match('/-?\d+/', $value, $matches)) {
                return (int) $matches[0];
            }
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if ($value === null) {
            return 0;
        }

        throw DeserializationException::typeMismatch(
            $propertyPath,
            'int',
            $this->getTypeName($value)
        );
    }

    /**
     * LENIENT mode: Coerce to float.
     */
    private function lenientCoerceToFloat(mixed $value, string $propertyPath): float
    {
        if (is_string($value)) {
            if (is_numeric($value)) {
                return (float) $value;
            }

            // Extract first number from string
            if (preg_match('/-?\d+\.?\d*/', $value, $matches)) {
                return (float) $matches[0];
            }
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if ($value === null) {
            return 0.0;
        }

        throw DeserializationException::typeMismatch(
            $propertyPath,
            'float',
            $this->getTypeName($value)
        );
    }

    /**
     * LENIENT mode: Coerce to bool.
     */
    private function lenientCoerceToBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return $value != 0;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));

            // Explicit false values
            if (in_array($lower, ['false', 'no', 'n', 'off', '0', ''], true)) {
                return false;
            }

            // Explicit true values
            if (in_array($lower, ['true', 'yes', 'y', 'on', '1'], true)) {
                return true;
            }

            // Any non-empty string is truthy
            return $value !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        // Default to PHP's truthiness
        return (bool) $value;
    }

    /**
     * Get a human-readable type name.
     */
    private function getTypeName(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_object($value)) {
            return get_class($value);
        }

        return gettype($value);
    }
}
