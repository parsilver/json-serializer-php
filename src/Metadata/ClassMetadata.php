<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Metadata;

/**
 * Metadata for a class.
 *
 * Contains cached information about a class and its properties,
 * including attribute configuration and naming strategies.
 */
class ClassMetadata
{
    /**
     * @param  class-string  $className  The class name
     * @param  array<string, PropertyMetadata>  $properties  Property metadata indexed by PHP name
     * @param  string|null  $namingStrategy  Class-level naming strategy
     * @param  string|null  $sinceVersion  Minimum version for this class
     * @param  string|null  $untilVersion  Maximum version for this class
     * @param  string|null  $discriminatorField  Field name for polymorphic discriminator
     * @param  array<string, class-string>  $discriminatorMap  Map of discriminator values to class names
     */
    public function __construct(
        public readonly string $className,
        public readonly array $properties,
        public readonly ?string $namingStrategy = null,
        public readonly ?string $sinceVersion = null,
        public readonly ?string $untilVersion = null,
        public readonly ?string $discriminatorField = null,
        public readonly array $discriminatorMap = [],
    ) {}

    /**
     * Get all properties that should be serialized.
     *
     * @param  string|null  $version  Optional version filter
     * @return array<string, PropertyMetadata>
     */
    public function getSerializableProperties(?string $version = null): array
    {
        return array_filter(
            $this->properties,
            fn (PropertyMetadata $property) => ! $property->ignore && $property->isAvailableInVersion($version)
        );
    }

    /**
     * Check if this class should be serialized for a given version.
     */
    public function isAvailableInVersion(?string $version): bool
    {
        if ($version === null) {
            return true;
        }

        if ($this->sinceVersion !== null && version_compare($version, $this->sinceVersion, '<')) {
            return false;
        }

        if ($this->untilVersion !== null && version_compare($version, $this->untilVersion, '>=')) {
            return false;
        }

        return true;
    }

    /**
     * Check if this class has discriminator support for polymorphic deserialization.
     */
    public function hasDiscriminator(): bool
    {
        return $this->discriminatorField !== null && ! empty($this->discriminatorMap);
    }

    /**
     * Resolve the concrete class name based on discriminator value.
     *
     * @param  string  $discriminatorValue  The discriminator value from JSON
     * @return class-string|null The concrete class name, or null if not found
     */
    public function resolveClass(string $discriminatorValue): ?string
    {
        return $this->discriminatorMap[$discriminatorValue] ?? null;
    }
}
