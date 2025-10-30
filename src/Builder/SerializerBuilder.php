<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Builder;

use Farzai\JsonSerializer\Cache\MetadataCache;
use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\SerializerEngine;
use Farzai\JsonSerializer\Security\SecurityConfig;
use Farzai\JsonSerializer\Transformers\TransformerRegistry;
use Farzai\JsonSerializer\Types\TypeCoercionMode;
use Farzai\JsonSerializer\Types\TypeHandlerFactory;
use Farzai\JsonSerializer\Types\TypeRegistry;
use Psr\SimpleCache\CacheInterface;

/**
 * Builder for creating configured serializer instances.
 *
 * This builder provides a fluent interface for configuring
 * serialization options and building a SerializerEngine.
 */
class SerializerBuilder
{
    private int $maxDepth = 512;

    private bool $detectCircularReferences = true;

    private bool $prettyPrint = false;

    private bool $strictTypes = true;

    private ?string $version = null;

    /** @var array<TypeHandlerInterface> */
    private array $customHandlers = [];

    private ?TypeRegistry $typeRegistry = null;

    private ?MetadataCache $metadataCache = null;

    private ?CacheInterface $psr16Cache = null;

    private ?SecurityConfig $securityConfig = null;

    // Deserializer-specific settings
    private bool $allowExtraProperties = true;

    private TypeCoercionMode $typeCoercionMode = TypeCoercionMode::SAFE;

    /**
     * Set the maximum depth for nested structures.
     *
     * @param  int  $maxDepth  The maximum nesting depth
     * @return $this
     */
    public function withMaxDepth(int $maxDepth): self
    {
        $this->maxDepth = $maxDepth;

        return $this;
    }

    /**
     * Enable or disable circular reference detection.
     *
     * @param  bool  $detect  Whether to detect circular references
     * @return $this
     */
    public function withCircularReferenceDetection(bool $detect): self
    {
        $this->detectCircularReferences = $detect;

        return $this;
    }

    /**
     * Enable or disable pretty print for JSON output.
     *
     * @param  bool  $prettyPrint  Whether to pretty print JSON
     * @return $this
     */
    public function withPrettyPrint(bool $prettyPrint): self
    {
        $this->prettyPrint = $prettyPrint;

        return $this;
    }

    /**
     * Enable or disable strict type checking.
     *
     * @param  bool  $strict  Whether to use strict types
     * @return $this
     */
    public function withStrictTypes(bool $strict): self
    {
        $this->strictTypes = $strict;

        return $this;
    }

    /**
     * Set whether to allow extra properties during deserialization.
     *
     * @param  bool  $allow  Whether to allow extra properties
     * @return $this
     */
    public function withAllowExtraProperties(bool $allow): self
    {
        $this->allowExtraProperties = $allow;

        return $this;
    }

    /**
     * Set the type coercion mode for deserialization.
     *
     * @param  TypeCoercionMode  $mode  The type coercion mode
     * @return $this
     */
    public function withTypeCoercionMode(TypeCoercionMode $mode): self
    {
        $this->typeCoercionMode = $mode;

        return $this;
    }

    /**
     * Set the version for versioned serialization.
     *
     * @param  string|null  $version  The version string
     * @return $this
     */
    public function withVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Set a PSR-16 cache for metadata caching.
     *
     * @param  CacheInterface  $cache  The PSR-16 cache implementation
     * @return $this
     *
     * @api
     */
    public function withCache(CacheInterface $cache): self
    {
        $this->psr16Cache = $cache;

        return $this;
    }

    /**
     * Set security configuration.
     *
     * @param  SecurityConfig  $config  The security configuration
     * @return $this
     *
     * @api
     */
    public function withSecurity(SecurityConfig $config): self
    {
        $this->securityConfig = $config;

        return $this;
    }

    /**
     * Enable secure defaults for untrusted input.
     *
     * This sets recommended security limits:
     * - Max depth: 32
     * - Max string length: 1MB
     * - Max array size: 10,000 elements
     * - Max memory: 128MB
     * - Timeout: 30 seconds
     *
     * @return $this
     *
     * @api
     */
    public function withSecureDefaults(): self
    {
        $this->securityConfig = SecurityConfig::secure();

        return $this;
    }

    /**
     * Build the serializer engine.
     */
    public function buildSerializer(): SerializerEngine
    {
        $registry = $this->buildTypeRegistry();
        $metadataCache = $this->buildMetadataCache();

        return new SerializerEngine(
            typeRegistry: $registry,
            metadataCache: $metadataCache,
            defaultMaxDepth: $this->maxDepth,
            defaultDetectCircularReferences: $this->detectCircularReferences,
            defaultPrettyPrint: $this->prettyPrint,
            defaultStrictTypes: $this->strictTypes,
            defaultVersion: $this->version
        );
    }

    /**
     * Build the deserializer engine.
     */
    public function buildDeserializer(): DeserializerEngine
    {
        $metadataCache = $this->buildMetadataCache();
        $transformerRegistry = new TransformerRegistry;

        return new DeserializerEngine(
            metadataCache: $metadataCache,
            transformerRegistry: $transformerRegistry,
            defaultMaxDepth: $this->maxDepth,
            defaultStrictTypes: $this->strictTypes,
            defaultVersion: $this->version,
            defaultAllowExtraProperties: $this->allowExtraProperties,
            defaultTypeCoercionMode: $this->typeCoercionMode,
            defaultSecurityConfig: $this->securityConfig
        );
    }

    /**
     * Build the serializer engine (alias for buildSerializer).
     *
     * @deprecated Use buildSerializer() instead
     */
    public function build(): SerializerEngine
    {
        return $this->buildSerializer();
    }

    /**
     * Build or return the metadata cache.
     */
    private function buildMetadataCache(): MetadataCache
    {
        if ($this->metadataCache !== null) {
            return $this->metadataCache;
        }

        if ($this->psr16Cache !== null) {
            return new MetadataCache($this->psr16Cache);
        }

        return new MetadataCache;
    }

    /**
     * Build the type registry with default and custom handlers.
     */
    private function buildTypeRegistry(): TypeRegistry
    {
        if ($this->typeRegistry !== null) {
            // If a custom registry was provided, use it and add custom handlers
            foreach ($this->customHandlers as $handler) {
                $this->typeRegistry->register($handler);
            }

            return $this->typeRegistry;
        }

        // Create default registry
        $factory = new TypeHandlerFactory;
        $registry = $factory->createDefaultRegistry();

        // Register custom handlers
        foreach ($this->customHandlers as $handler) {
            $registry->register($handler);
        }

        return $registry;
    }
}
