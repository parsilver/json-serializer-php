<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Marks a method as a virtual property to be included in serialization.
 *
 * The method should return a value and accept no parameters.
 *
 * @example
 * class User {
 *     private string $firstName;
 *     private string $lastName;
 *
 *     #[VirtualProperty('full_name')]
 *     public function getFullName(): string {
 *         return "{$this->firstName} {$this->lastName}";
 *     }
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class VirtualProperty
{
    /**
     * Create a new VirtualProperty attribute.
     *
     * @param  string|null  $name  The name to use in JSON output (defaults to method name)
     */
    public function __construct(
        public readonly ?string $name = null
    ) {}
}
