<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\NamingStrategy;

use Farzai\JsonSerializer\Contracts\NamingStrategyInterface;

/**
 * Converts property names to kebab-case format.
 *
 * Examples:
 * - firstName -> first-name
 * - FirstName -> first-name
 * - first_name -> first-name
 */
class KebabCaseStrategy implements NamingStrategyInterface
{
    #[\Override]
    public function transform(string $propertyName): string
    {
        // Already in kebab-case
        if ($this->isKebabCase($propertyName)) {
            return $propertyName;
        }

        // Insert hyphen before uppercase letters
        $result = preg_replace('/(?<!^)[A-Z]/', '-$0', $propertyName);

        if ($result === null) {
            return strtolower($propertyName);
        }

        // Replace underscores and spaces with hyphens
        $result = str_replace(['_', ' '], '-', $result);

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
        return 'kebab-case';
    }

    private function isKebabCase(string $string): bool
    {
        // kebab-case is all lowercase with hyphens
        return $string === strtolower($string) &&
            ! str_contains($string, '_') &&
            ! str_contains($string, ' ');
    }
}
