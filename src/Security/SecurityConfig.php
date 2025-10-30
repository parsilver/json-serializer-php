<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Security;

/**
 * Security configuration for serialization/deserialization operations.
 *
 * Provides protection against:
 * - Memory exhaustion attacks (large strings, deep nesting)
 * - Billion laughs attack (entity expansion)
 * - Deserialization attacks (arbitrary class instantiation)
 * - Resource exhaustion (timeouts, memory limits)
 */
final class SecurityConfig
{
    /**
     * Create a new security configuration.
     *
     * @param  int  $maxDepth  Maximum nesting depth (default: 512)
     * @param  int|null  $maxStringLength  Maximum string length in characters (null = unlimited)
     * @param  int|null  $maxArraySize  Maximum array size (null = unlimited)
     * @param  int|null  $maxMemoryBytes  Maximum memory usage in bytes (null = unlimited)
     * @param  int|null  $timeoutSeconds  Maximum execution time in seconds (null = unlimited)
     * @param  array<class-string>  $allowedClasses  Whitelist of classes allowed for deserialization (empty = all allowed)
     * @param  bool  $strictTypes  Enable strict type checking during deserialization
     */
    public function __construct(
        public readonly int $maxDepth = 512,
        public readonly ?int $maxStringLength = null,
        public readonly ?int $maxArraySize = null,
        public readonly ?int $maxMemoryBytes = null,
        public readonly ?int $timeoutSeconds = null,
        public readonly array $allowedClasses = [],
        public readonly bool $strictTypes = true
    ) {}

    /**
     * Create a secure configuration with recommended defaults.
     */
    public static function secure(): self
    {
        return new self(
            maxDepth: 32,
            maxStringLength: 1_000_000, // 1MB string limit
            maxArraySize: 10_000,
            maxMemoryBytes: 128 * 1024 * 1024, // 128MB
            timeoutSeconds: 30,
            allowedClasses: [],
            strictTypes: true
        );
    }

    /**
     * Create a lenient configuration for trusted input.
     */
    public static function lenient(): self
    {
        return new self(
            maxDepth: 512,
            maxStringLength: null,
            maxArraySize: null,
            maxMemoryBytes: null,
            timeoutSeconds: null,
            allowedClasses: [],
            strictTypes: false
        );
    }

    /**
     * Check if a class is allowed for deserialization.
     *
     * @param  class-string  $className
     */
    public function isClassAllowed(string $className): bool
    {
        // If whitelist is empty, all classes are allowed
        if (empty($this->allowedClasses)) {
            return true;
        }

        return in_array($className, $this->allowedClasses, true);
    }
}
