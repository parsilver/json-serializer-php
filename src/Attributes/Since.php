<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Marks a property as available starting from a specific version.
 *
 * @example
 * class User {
 *     #[Since('2.0')]
 *     public ?string $middleName = null;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final class Since
{
    /**
     * Create a new Since attribute.
     *
     * @param  string  $version  The minimum version for this property/class
     */
    public function __construct(
        public readonly string $version
    ) {}
}
