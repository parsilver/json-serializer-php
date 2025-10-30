<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Security;

use Farzai\JsonSerializer\Exceptions\SecurityException;

/**
 * Validator for security constraints during serialization/deserialization.
 */
final class SecurityValidator
{
    private int $startTime;

    private int $startMemory;

    public function __construct(
        private readonly SecurityConfig $config
    ) {
        $this->startTime = time();
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Validate nesting depth.
     *
     * @throws SecurityException
     */
    public function validateDepth(int $currentDepth): void
    {
        if ($currentDepth > $this->config->maxDepth) {
            throw SecurityException::maxDepthExceeded($this->config->maxDepth, $currentDepth);
        }
    }

    /**
     * Validate string length.
     *
     * @throws SecurityException
     */
    public function validateStringLength(string $value): void
    {
        if ($this->config->maxStringLength === null) {
            return;
        }

        $length = mb_strlen($value);
        if ($length > $this->config->maxStringLength) {
            throw SecurityException::maxStringLengthExceeded($this->config->maxStringLength, $length);
        }
    }

    /**
     * Validate array size.
     *
     * @param  array<mixed>  $array
     *
     * @throws SecurityException
     */
    public function validateArraySize(array $array): void
    {
        if ($this->config->maxArraySize === null) {
            return;
        }

        $size = count($array);
        if ($size > $this->config->maxArraySize) {
            throw SecurityException::maxArraySizeExceeded($this->config->maxArraySize, $size);
        }
    }

    /**
     * Validate memory usage.
     *
     * @throws SecurityException
     */
    public function validateMemoryUsage(): void
    {
        if ($this->config->maxMemoryBytes === null) {
            return;
        }

        $currentMemory = memory_get_usage(true);
        $memoryUsed = $currentMemory - $this->startMemory;

        if ($memoryUsed > $this->config->maxMemoryBytes) {
            throw SecurityException::memoryLimitExceeded($this->config->maxMemoryBytes, $memoryUsed);
        }
    }

    /**
     * Validate execution time.
     *
     * @throws SecurityException
     */
    public function validateTimeout(): void
    {
        if ($this->config->timeoutSeconds === null) {
            return;
        }

        $elapsed = time() - $this->startTime;
        if ($elapsed > $this->config->timeoutSeconds) {
            throw SecurityException::timeoutExceeded($this->config->timeoutSeconds);
        }
    }

    /**
     * Validate if a class is allowed for deserialization.
     *
     * @param  class-string  $className
     *
     * @throws SecurityException
     */
    public function validateClassAllowed(string $className): void
    {
        if (! $this->config->isClassAllowed($className)) {
            throw SecurityException::classNotAllowed($className);
        }
    }

    /**
     * Validate all constraints at once.
     *
     * @param  array<mixed>|null  $arrayValue
     *
     * @throws SecurityException
     */
    public function validate(int $currentDepth = 0, ?string $stringValue = null, ?array $arrayValue = null): void
    {
        $this->validateDepth($currentDepth);
        $this->validateTimeout();
        $this->validateMemoryUsage();

        if ($stringValue !== null) {
            $this->validateStringLength($stringValue);
        }

        if ($arrayValue !== null) {
            $this->validateArraySize($arrayValue);
        }
    }
}
