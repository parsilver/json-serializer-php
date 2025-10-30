<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Specifies a custom name for a property in JSON output.
 *
 * @example
 * class User {
 *     #[SerializedName('full_name')]
 *     public string $fullName;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class SerializedName
{
    /**
     * Create a new SerializedName attribute.
     *
     * @param  string  $name  The name to use in JSON output
     */
    public function __construct(
        public readonly string $name
    ) {}
}
