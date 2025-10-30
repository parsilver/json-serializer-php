<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\NamingStrategy;

use Farzai\JsonSerializer\Contracts\NamingStrategyInterface;

/**
 * Converts property names to camelCase format.
 *
 * Examples:
 * - first_name -> firstName
 * - FirstName -> firstName
 * - first-name -> firstName
 */
class CamelCaseStrategy implements NamingStrategyInterface
{
    #[\Override]
    public function transform(string $propertyName): string
    {
        // Already in camelCase
        if ($this->isCamelCase($propertyName)) {
            return $propertyName;
        }

        // Split by underscore, hyphen, or capital letters
        $parts = preg_split('/[_\-\s]+|(?=[A-Z])/', $propertyName, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false || $parts === []) {
            return $propertyName;
        }

        // First part lowercase, rest capitalized
        $result = strtolower($parts[0]);
        for ($i = 1; $i < count($parts); $i++) {
            $result .= ucfirst(strtolower($parts[$i]));
        }

        return $result;
    }

    /**
     * Get the strategy name.
     *
     * @api
     */
    public function getName(): string
    {
        return 'camelCase';
    }

    private function isCamelCase(string $string): bool
    {
        // camelCase starts with lowercase, no underscores or hyphens
        return ctype_lower($string[0]) &&
            ! str_contains($string, '_') &&
            ! str_contains($string, '-');
    }
}
