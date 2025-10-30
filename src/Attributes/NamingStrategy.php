<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Specifies a naming strategy for property names.
 *
 * Can be applied to classes or individual properties.
 *
 * @example
 * #[NamingStrategy('snake_case')]
 * class User {
 *     public string $firstName; // Serialized as "first_name"
 * }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class NamingStrategy
{
    public const CAMEL_CASE = 'camelCase';

    public const SNAKE_CASE = 'snake_case';

    public const PASCAL_CASE = 'PascalCase';

    public const KEBAB_CASE = 'kebab-case';

    /**
     * Create a new NamingStrategy attribute.
     *
     * @param  string  $strategy  The naming strategy to use (e.g., 'snake_case', 'camelCase')
     */
    public function __construct(
        public readonly string $strategy
    ) {}
}
