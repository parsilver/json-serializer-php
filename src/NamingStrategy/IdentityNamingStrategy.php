<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\NamingStrategy;

use Farzai\JsonSerializer\Contracts\NamingStrategyInterface;

/**
 * final Identity naming strategy that returns the property name unchanged.
 *
 * This is the default strategy when no naming strategy is specified.
 */
class IdentityNamingStrategy implements NamingStrategyInterface
{
    #[\Override]
    public function transform(string $propertyName): string
    {
        return $propertyName;
    }

    /**
     * Get the strategy name.
     *
     * @api
     */
    public function getName(): string
    {
        return 'identity';
    }
}
