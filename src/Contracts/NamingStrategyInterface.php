<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Contracts;

/**
 * Contract for naming strategy implementations.
 *
 * Naming strategies convert property names from one format to another
 * (e.g., camelCase to snake_case).
 */
interface NamingStrategyInterface
{
    /**
     * Transform a property name according to the strategy.
     *
     * @param  string  $propertyName  The original property name
     * @return string The transformed property name
     */
    public function transform(string $propertyName): string;
}
