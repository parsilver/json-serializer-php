<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Security\SecurityConfig;
use Farzai\JsonSerializer\Security\SecurityValidator;
use Farzai\JsonSerializer\Types\TypeCoercionMode;

/**
 * Context object containing deserialization configuration and state.
 *
 * This object is passed through the deserialization process and maintains
 * configuration options and state tracking.
 */
class DeserializationContext
{
    private int $currentDepth = 0;

    private ?SecurityValidator $securityValidator = null;

    /**
     * Create a new deserialization context.
     *
     * @param  int  $maxDepth  Maximum nesting depth (default: 512)
     * @param  bool  $strictTypes  Enable strict type checking (default: true)
     * @param  string|null  $version  Version for versioned deserialization (default: null)
     * @param  bool  $allowExtraProperties  Allow properties not defined in class (default: true)
     * @param  TypeCoercionMode  $typeCoercionMode  Type coercion mode (default: SAFE)
     * @param  SecurityConfig|null  $securityConfig  Security configuration (default: null = no limits)
     */
    public function __construct(
        private readonly int $maxDepth = 512,
        private readonly bool $strictTypes = true,
        private readonly ?string $version = null,
        private readonly bool $allowExtraProperties = true,
        private readonly TypeCoercionMode $typeCoercionMode = TypeCoercionMode::SAFE,
        private readonly ?SecurityConfig $securityConfig = null,
    ) {
        if ($this->securityConfig !== null) {
            $this->securityValidator = new SecurityValidator($this->securityConfig);
        }
    }

    /**
     * Check if strict type checking is enabled.
     */
    public function isStrictTypes(): bool
    {
        return $this->strictTypes;
    }

    /**
     * Get the version for versioned deserialization.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Check if extra properties are allowed.
     */
    public function allowsExtraProperties(): bool
    {
        return $this->allowExtraProperties;
    }

    /**
     * Get the type coercion mode.
     */
    public function getTypeCoercionMode(): TypeCoercionMode
    {
        return $this->typeCoercionMode;
    }

    /**
     * Increase depth counter.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\SerializationException If max depth is exceeded
     */
    public function increaseDepth(): void
    {
        $this->currentDepth++;

        if ($this->currentDepth > $this->maxDepth) {
            throw new \Farzai\JsonSerializer\Exceptions\SerializationException(
                "Maximum depth of {$this->maxDepth} exceeded during deserialization"
            );
        }
    }

    /**
     * Decrease depth counter.
     */
    public function decreaseDepth(): void
    {
        if ($this->currentDepth > 0) {
            $this->currentDepth--;
        }
    }

    /**
     * Get the security validator (if security is enabled).
     */
    public function getSecurityValidator(): ?SecurityValidator
    {
        return $this->securityValidator;
    }

    /**
     * Validate a class is allowed for deserialization.
     *
     * @param  class-string  $className
     *
     * @throws \Farzai\JsonSerializer\Exceptions\SecurityException
     */
    public function validateClass(string $className): void
    {
        $this->securityValidator?->validateClassAllowed($className);
    }

    /**
     * Validate string length.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\SecurityException
     */
    public function validateString(string $value): void
    {
        $this->securityValidator?->validateStringLength($value);
    }

    /**
     * Validate array size.
     *
     * @param  array<mixed>  $value
     *
     * @throws \Farzai\JsonSerializer\Exceptions\SecurityException
     */
    public function validateArray(array $value): void
    {
        $this->securityValidator?->validateArraySize($value);
    }

    /**
     * Perform all security validations.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\SecurityException
     */
    public function validateSecurity(): void
    {
        $this->securityValidator?->validateTimeout();
        $this->securityValidator?->validateMemoryUsage();
    }
}
