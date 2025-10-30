<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Exceptions;

/**
 * Exception thrown during deserialization with contextual information.
 *
 * This exception provides detailed context about where and why deserialization
 * failed, including property paths and type information.
 */
class DeserializationException extends SerializationException
{
    /**
     * Create a new deserialization exception with context.
     *
     * @param  string  $message  The error message
     * @param  string|null  $propertyPath  The property path (e.g., "user.address.city")
     * @param  string|null  $expectedType  The expected type
     * @param  string|null  $actualType  The actual type received
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $message,
        private readonly ?string $propertyPath = null,
        private readonly ?string $expectedType = null,
        private readonly ?string $actualType = null,
        ?\Throwable $previous = null
    ) {
        $contextualMessage = $this->buildContextualMessage($message);
        parent::__construct($contextualMessage, 0, $previous);
    }

    /**
     * Build a contextual error message.
     */
    private function buildContextualMessage(string $baseMessage): string
    {
        $parts = [$baseMessage];

        if ($this->propertyPath !== null) {
            $parts[] = "Property path: {$this->propertyPath}";
        }

        if ($this->expectedType !== null || $this->actualType !== null) {
            $typeInfo = [];
            if ($this->expectedType !== null) {
                $typeInfo[] = "expected {$this->expectedType}";
            }
            if ($this->actualType !== null) {
                $typeInfo[] = "got {$this->actualType}";
            }
            $parts[] = 'Type mismatch: '.implode(', ', $typeInfo);
        }

        return implode('. ', $parts);
    }

    /**
     * Create an exception for type mismatch.
     */
    public static function typeMismatch(
        string $propertyPath,
        string $expectedType,
        string $actualType
    ): self {
        return new self(
            message: 'Type mismatch during deserialization',
            propertyPath: $propertyPath,
            expectedType: $expectedType,
            actualType: $actualType
        );
    }

    /**
     * Create an exception for unknown property.
     */
    public static function unknownProperty(string $propertyPath, string $className): self
    {
        return new self(
            message: "Unknown property in class {$className}",
            propertyPath: $propertyPath
        );
    }

    /**
     * Get the property path where the error occurred.
     */
    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    /**
     * Get the expected type.
     */
    public function getExpectedType(): ?string
    {
        return $this->expectedType;
    }

    /**
     * Get the actual type that was encountered.
     */
    public function getActualType(): ?string
    {
        return $this->actualType;
    }
}
