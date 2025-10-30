<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\NamingStrategy;

use Farzai\JsonSerializer\Contracts\NamingStrategyInterface;

/**
 * Converts property names to PascalCase format.
 *
 * Examples:
 * - firstName -> FirstName
 * - first_name -> FirstName
 * - first-name -> FirstName
 */
class PascalCaseStrategy implements NamingStrategyInterface
{
    #[\Override]
    public function transform(string $propertyName): string
    {
        // Already in PascalCase
        if ($this->isPascalCase($propertyName)) {
            return $propertyName;
        }

        // Split by underscore, hyphen, or capital letters
        $parts = preg_split('/[_\-\s]+|(?=[A-Z])/', $propertyName, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false || $parts === []) {
            return ucfirst($propertyName);
        }

        // Capitalize all parts
        $result = '';
        foreach ($parts as $part) {
            $result .= ucfirst(strtolower($part));
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
        return 'PascalCase';
    }

    private function isPascalCase(string $string): bool
    {
        // PascalCase starts with uppercase, no underscores or hyphens
        return ctype_upper($string[0]) &&
            ! str_contains($string, '_') &&
            ! str_contains($string, '-');
    }
}
