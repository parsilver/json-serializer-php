<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Marks a class as polymorphic with a discriminator field for deserialization.
 *
 * The discriminator field determines which concrete class to instantiate
 * based on its value in the JSON. This is useful for abstract classes,
 * interfaces, or union types.
 *
 * Example:
 * ```php
 * #[Discriminator(field: 'type', map: [
 *     'car' => Car::class,
 *     'bike' => Bike::class,
 * ])]
 * abstract class Vehicle {
 *     public string $name;
 * }
 *
 * class Car extends Vehicle {
 *     public int $doors;
 * }
 *
 * // JSON: {"type": "car", "name": "Toyota", "doors": 4}
 * // Deserializes to Car instance
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Discriminator
{
    /**
     * @param  string  $field  The JSON field name that contains the discriminator value
     * @param  array<string, class-string>  $map  Map of discriminator values to class names
     */
    public function __construct(
        public readonly string $field,
        public readonly array $map,
    ) {}
}
