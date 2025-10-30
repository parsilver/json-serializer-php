<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Marks a property as deprecated after a specific version.
 *
 * @example
 * class User {
 *     #[Until('3.0')]
 *     public ?string $legacyId = null;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
final class Until
{
    /**
     * Create a new Until attribute.
     *
     * @param  string  $version  The maximum version for this property/class
     */
    public function __construct(
        public readonly string $version
    ) {}
}
