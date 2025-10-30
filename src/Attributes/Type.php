<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Specifies the type for deserialization.
 *
 * Useful for arrays, generics, or when type inference is ambiguous.
 *
 * @example
 * class Blog {
 *     #[Type('array<User>')]
 *     public array $authors;
 *
 *     #[Type(User::class)]
 *     public object $owner;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Type
{
    /**
     * Create a new Type attribute.
     *
     * @param  string  $type  The type specification (e.g., 'array<User>', User::class)
     */
    public function __construct(
        public readonly string $type
    ) {}
}
