<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Exceptions;

/**
 * Base exception for security-related errors during serialization/deserialization.
 *
 * This exception is thrown when security limits are exceeded or
 * security policies are violated.
 */
class SecurityException extends SerializationException
{
    /**
     * Create an exception for exceeding maximum depth.
     */
    public static function maxDepthExceeded(int $maxDepth, int $currentDepth): self
    {
        return new self(
            "Maximum nesting depth of {$maxDepth} exceeded (current depth: {$currentDepth})"
        );
    }

    /**
     * Create an exception for exceeding maximum string length.
     */
    public static function maxStringLengthExceeded(int $maxLength, int $actualLength): self
    {
        return new self(
            "Maximum string length of {$maxLength} exceeded (actual length: {$actualLength})"
        );
    }

    /**
     * Create an exception for exceeding maximum array size.
     */
    public static function maxArraySizeExceeded(int $maxSize, int $actualSize): self
    {
        return new self(
            "Maximum array size of {$maxSize} exceeded (actual size: {$actualSize})"
        );
    }

    /**
     * Create an exception for exceeding memory limit.
     */
    public static function memoryLimitExceeded(int $maxBytes, int $currentBytes): self
    {
        $maxMB = round($maxBytes / 1024 / 1024, 2);
        $currentMB = round($currentBytes / 1024 / 1024, 2);

        return new self(
            "Memory limit of {$maxMB}MB exceeded (current: {$currentMB}MB)"
        );
    }

    /**
     * Create an exception for timeout.
     */
    public static function timeoutExceeded(int $maxSeconds): self
    {
        return new self(
            "Execution timeout of {$maxSeconds} seconds exceeded"
        );
    }

    /**
     * Create an exception for disallowed class.
     */
    public static function classNotAllowed(string $className): self
    {
        return new self(
            "Class '{$className}' is not in the allowed classes whitelist"
        );
    }

    /**
     * Create an exception for potential billion laughs attack.
     */
    public static function billionLaughsDetected(): self
    {
        return new self(
            'Potential billion laughs attack detected: excessive entity expansion'
        );
    }
}
