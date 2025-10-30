<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Metadata;

use Farzai\JsonSerializer\Contracts\TransformerInterface;

/**
 * Metadata for a singlefinal  property.
 *
 * Contains all information needed to serialize/deserialize a property,
 * including attributes and reflection data.
 */
class PropertyMetadata
{
    /**
     * @param  string  $phpName  The PHP property name
     * @param  string  $serializedName  The name to use in JSON
     * @param  bool  $ignore  Whether to ignore this property
     * @param  string|null  $type  Explicit type hint from attribute
     * @param  class-string<TransformerInterface>|null  $transformerClass  Custom transformer
     * @param  array<string, mixed>  $transformerOptions  Options for the transformer
     * @param  string|null  $sinceVersion  Minimum version for this property
     * @param  string|null  $untilVersion  Maximum version for this property
     * @param  bool  $isVirtual  Whether this is a virtual property (method)
     * @param  string|null  $virtualMethod  The method name for virtual properties
     */
    public function __construct(
        public readonly string $phpName,
        public readonly string $serializedName,
        public readonly bool $ignore = false,
        public readonly ?string $type = null,
        public readonly ?string $transformerClass = null,
        public readonly array $transformerOptions = [],
        public readonly ?string $sinceVersion = null,
        public readonly ?string $untilVersion = null,
        public readonly bool $isVirtual = false,
        public readonly ?string $virtualMethod = null,
    ) {}

    /**
     * Check if this property should be serialized for a given version.
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
}
