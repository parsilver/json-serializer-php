<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\NamingStrategy;

use Farzai\JsonSerializer\Contracts\NamingStrategyInterface;

/**
 * Converts property names to snake_case format.
 *
 * Examples:
 * - firstName -> first_name
 * - FirstName -> first_name
 * - first-name -> first_name
 */
class SnakeCaseStrategy implements NamingStrategyInterface
{
    #[\Override]
    public function transform(string $propertyName): string
    {
        // Already in snake_case
        if ($this->isSnakeCase($propertyName)) {
            return $propertyName;
        }

        // Insert underscore before uppercase letters
        $result = preg_replace('/(?<!^)[A-Z]/', '_$0', $propertyName);

        if ($result === null) {
            return strtolower($propertyName);
        }

        // Replace hyphens and spaces with underscores
        $result = str_replace(['-', ' '], '_', $result);

        // Convert to lowercase
        return strtolower($result);
    }

    /**
     * Get the strategy name.
     *
     * @api
     */
    public function getName(): string
    {
        return 'snake_case';
    }

    private function isSnakeCase(string $string): bool
    {
        // snake_case is all lowercase with underscores
        return $string === strtolower($string) &&
            ! str_contains($string, '-') &&
            ! str_contains($string, ' ');
    }
}
