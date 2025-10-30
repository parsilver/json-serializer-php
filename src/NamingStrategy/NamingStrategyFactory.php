<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\NamingStrategy;

use Farzai\JsonSerializer\Attributes\NamingStrategy as NamingStrategyAttribute;
use Farzai\JsonSerializer\Contracts\NamingStrategyInterface;
use InvalidArgumentException;

/**
 * Factory for creating naming strategy instances.
 */
class NamingStrategyFactory
{
    /**
     * Create a naming strategy from a string identifier.
     *
     * @param  string  $strategy  Strategy identifier (e.g., 'snake_case', 'camelCase')
     *
     * @throws InvalidArgumentException If the strategy is not recognized
     */
    public function create(string $strategy): NamingStrategyInterface
    {
        return match ($strategy) {
            NamingStrategyAttribute::CAMEL_CASE, 'camelCase' => new CamelCaseStrategy,
            NamingStrategyAttribute::SNAKE_CASE, 'snake_case' => new SnakeCaseStrategy,
            NamingStrategyAttribute::PASCAL_CASE, 'PascalCase' => new PascalCaseStrategy,
            NamingStrategyAttribute::KEBAB_CASE, 'kebab-case' => new KebabCaseStrategy,
            'identity', '' => new IdentityNamingStrategy,
            default => throw new InvalidArgumentException("Unknown naming strategy: {$strategy}"),
        };
    }
}
