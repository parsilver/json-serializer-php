<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types;

/**
 * Type coercion modes for deserialization.
 *
 * Controls how the deserializer handles type mismatches during conversion.
 */
enum TypeCoercionMode: string
{
    /**
     * STRICT mode - No type coercion allowed.
     *
     * Throws exception on any type mismatch. Values must exactly match
     * the expected type. Only allows safe widening (int → float).
     *
     * Use when you need strict type safety and want to catch all type errors.
     */
    case STRICT = 'strict';

    /**
     * SAFE mode - Conservative type coercion (default).
     *
     * Allows safe, obvious type conversions:
     * - Numeric strings to numbers ("123" → 123, "3.14" → 3.14)
     * - Boolean strings to bool ("true" → true, "false" → false)
     * - 1/0 to boolean
     * - int to float (widening)
     * - Empty string to null for nullable types
     *
     * Use for most applications where you want reasonable flexibility
     * without sacrificing type safety.
     */
    case SAFE = 'safe';

    /**
     * LENIENT mode - Aggressive type coercion.
     *
     * Attempts to coerce almost any value to the target type:
     * - Any value to string (cast)
     * - Numeric-like values to numbers
     * - Truthy/falsy values to boolean ("yes"/"no", non-empty strings, etc.)
     * - Very permissive conversions
     *
     * Use when dealing with loosely-typed data sources (CSV, forms, etc.)
     * where you want maximum flexibility.
     */
    case LENIENT = 'lenient';
}
