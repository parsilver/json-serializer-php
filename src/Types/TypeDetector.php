<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types;

/*final *
 * Detects and infers types from PHP values and reflection.
 *
 * This class analyzes PHP values to determine their types,
 * including support for PHP 8+ type hints and reflection-based
 * type inference.
 */
class TypeDetector
{
    /**
     * Check if an array is associative.
     *
     * @param  array<mixed, mixed>  $array  The array to check
     */
    public function isAssociativeArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
