<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Cache\MetadataCache;
use Farzai\JsonSerializer\Exceptions\DeserializationException;
use Farzai\JsonSerializer\Exceptions\TypeException;
use Farzai\JsonSerializer\Transformers\TransformerRegistry;
use Farzai\JsonSerializer\Types\TypeCoercer;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Hydrates objects from associative arrays using metadata and transformers.
 *
 * This class creates instances of PHP objects and populates their properties
 * with values from deserialized JSON data.
 */
class ObjectHydrator
{
    private readonly MetadataCache $metadataCache;

    private readonly TransformerRegistry $transformerRegistry;

    private readonly TypeCoercer $typeCoercer;

    public function __construct(
        ?MetadataCache $metadataCache = null,
        ?TransformerRegistry $transformerRegistry = null
    ) {
        $this->metadataCache = $metadataCache ?? new MetadataCache;
        $this->transformerRegistry = $transformerRegistry ?? new TransformerRegistry;
        $this->typeCoercer = new TypeCoercer;
    }

    /**
     * Hydrate an object from an associative array.
     *
     * @template T of object
     *
     * @param  class-string<T>  $className  The class to instantiate
     * @param  array<string, mixed>  $data  The data to hydrate from
     * @param  DeserializationContext  $context  The deserialization context
     * @param  string  $propertyPath  The property path for error reporting
     * @return T The hydrated object
     */
    public function hydrate(string $className, array $data, DeserializationContext $context, string $propertyPath = ''): object
    {
        if (! class_exists($className) && ! interface_exists($className)) {
            throw new TypeException("Class or interface {$className} does not exist");
        }

        // Validate class is allowed for deserialization
        $context->validateClass($className);

        // Validate array size
        $context->validateArray($data);

        $context->increaseDepth();

        // Validate security constraints
        $context->validateSecurity();

        try {
            $metadata = $this->metadataCache->get($className);

            // Handle polymorphic deserialization with discriminator
            if ($metadata->hasDiscriminator()) {
                $discriminatorField = $metadata->discriminatorField;

                if (! isset($data[$discriminatorField])) {
                    throw new TypeException(
                        "Discriminator field '{$discriminatorField}' not found in JSON for polymorphic class {$className}"
                    );
                }

                /** @var string $discriminatorValue */
                /** @phpstan-ignore cast.string */
                $discriminatorValue = is_string($data[$discriminatorField]) ? $data[$discriminatorField] : (string) $data[$discriminatorField];
                $concreteClass = $metadata->resolveClass($discriminatorValue);

                if ($concreteClass === null) {
                    throw new TypeException(
                        "Unknown discriminator value '{$discriminatorValue}' for class {$className}. ".
                        'Expected one of: '.implode(', ', array_keys($metadata->discriminatorMap))
                    );
                }

                // Recursively hydrate with the concrete class
                $context->decreaseDepth(); // Decrease depth before recursive call

                /** @var T */
                return $this->hydrate($concreteClass, $data, $context, $propertyPath);
            }

            // Check class-level versioning
            if (! $metadata->isAvailableInVersion($context->getVersion())) {
                throw new TypeException("Class {$className} is not available in version {$context->getVersion()}");
            }

            // Create instance without calling constructor
            $reflection = new ReflectionClass($className);
            $instance = $reflection->newInstanceWithoutConstructor();

            // Get serializable properties for current version
            $properties = $metadata->getSerializableProperties($context->getVersion());

            // Map JSON keys to property names
            $keyToProperty = [];
            foreach ($properties as $property) {
                $keyToProperty[$property->serializedName] = $property;
            }

            // Hydrate properties
            foreach ($data as $key => $value) {
                if (! isset($keyToProperty[$key])) {
                    // Extra property not in class definition
                    if (! $context->allowsExtraProperties() && $context->isStrictTypes()) {
                        $currentPath = $propertyPath ? "{$propertyPath}.{$key}" : $key;
                        throw DeserializationException::unknownProperty($currentPath, $className);
                    }

                    continue;
                }

                $propertyMetadata = $keyToProperty[$key];
                $currentPath = $propertyPath ? "{$propertyPath}.{$propertyMetadata->serializedName}" : $propertyMetadata->serializedName;

                // Skip virtual properties (they're computed, not settable)
                if ($propertyMetadata->isVirtual) {
                    continue;
                }

                try {
                    // Transform value if transformer is configured
                    if ($propertyMetadata->transformerClass !== null) {
                        $transformer = $this->transformerRegistry->get($propertyMetadata->transformerClass);
                        $value = $transformer->deserialize($value, $propertyMetadata->transformerOptions);
                    } else {
                        // Process value for nested objects or collections
                        $value = $this->processPropertyValue($instance, $propertyMetadata, $value, $context, $currentPath);
                    }

                    // Set property value
                    $this->setPropertyValue($instance, $propertyMetadata->phpName, $value);
                } catch (DeserializationException $e) {
                    // Re-throw with context preserved
                    throw $e;
                } catch (\Exception $e) {
                    // Wrap other exceptions with context
                    throw new DeserializationException(
                        message: "Failed to deserialize property: {$e->getMessage()}",
                        propertyPath: $currentPath,
                        previous: $e
                    );
                }
            }

            return $instance;
        } finally {
            $context->decreaseDepth();
        }
    }

    /**
     * Process a property value, handling nested objects and collections.
     *
     * @param  object  $object  The parent object
     * @param  \Farzai\JsonSerializer\Metadata\PropertyMetadata  $propertyMetadata  The property metadata
     * @param  mixed  $value  The raw value from JSON
     * @param  DeserializationContext  $context  The deserialization context
     * @param  string  $propertyPath  The property path for error reporting
     * @return mixed The processed value
     */
    private function processPropertyValue(
        object $object,
        \Farzai\JsonSerializer\Metadata\PropertyMetadata $propertyMetadata,
        mixed $value,
        DeserializationContext $context,
        string $propertyPath
    ): mixed {
        // If value is null, return it as-is
        if ($value === null) {
            return null;
        }

        // Get the property reflection to check its type
        try {
            $reflection = new ReflectionProperty($object, $propertyMetadata->phpName);
            $reflectionType = $reflection->getType();
        } catch (\ReflectionException) {
            // Can't get property type, return value as-is
            return $value;
        }

        // Check if there's an explicit type hint from #[Type] attribute
        if ($propertyMetadata->type !== null) {
            return $this->processExplicitType($propertyMetadata->type, $value, $context, $propertyPath);
        }

        // Handle based on reflection type
        if ($reflectionType instanceof ReflectionNamedType) {
            return $this->processNamedType($reflectionType, $value, $context, $propertyPath);
        }

        if ($reflectionType instanceof ReflectionUnionType) {
            return $this->processUnionType($reflectionType, $value, $context, $propertyPath);
        }

        // No type information, return as-is
        return $value;
    }

    /**
     * Process an explicit type hint from #[Type] attribute.
     *
     * @param  string  $typeHint  The type hint (e.g., "User", "array<User>")
     * @param  mixed  $value  The raw value from JSON
     * @param  DeserializationContext  $context  The deserialization context
     * @param  string  $propertyPath  The property path for error reporting
     * @return mixed The processed value
     */
    private function processExplicitType(string $typeHint, mixed $value, DeserializationContext $context, string $propertyPath): mixed
    {
        // Handle array<Type> notation
        if (preg_match('/^array<(.+)>$/i', $typeHint, $matches)) {
            $elementType = trim($matches[1]);

            if (! is_array($value)) {
                throw DeserializationException::typeMismatch(
                    $propertyPath,
                    $typeHint,
                    gettype($value)
                );
            }

            // Validate array size
            $context->validateArray($value);

            // Hydrate each element
            $result = [];
            foreach ($value as $key => $item) {
                if (is_array($item) && class_exists($elementType)) {
                    /** @var array<string, mixed> $item */
                    $itemPath = "{$propertyPath}[{$key}]";
                    $result[$key] = $this->hydrate($elementType, $item, $context, $itemPath);
                } else {
                    $result[$key] = $item;
                }
            }

            return $result;
        }

        // Handle simple class name
        if (class_exists($typeHint)) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                return $this->hydrate($typeHint, $value, $context, $propertyPath);
            }
        }

        return $value;
    }

    /**
     * Process a named type (e.g., string, int, User).
     *
     * @param  ReflectionNamedType  $type  The reflection type
     * @param  mixed  $value  The raw value from JSON
     * @param  DeserializationContext  $context  The deserialization context
     * @param  string  $propertyPath  The property path for error reporting
     * @return mixed The processed value
     */
    private function processNamedType(ReflectionNamedType $type, mixed $value, DeserializationContext $context, string $propertyPath): mixed
    {
        $typeName = $type->getName();

        // Handle built-in types
        if ($type->isBuiltin()) {
            return $this->coerceType($value, $typeName, $context, $propertyPath);
        }

        // Handle class types
        if (class_exists($typeName)) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                return $this->hydrate($typeName, $value, $context, $propertyPath);
            }

            // If value is not an array but type expects an object, can't hydrate
            if (! $type->allowsNull() || $value !== null) {
                throw DeserializationException::typeMismatch(
                    $propertyPath,
                    $typeName,
                    gettype($value)
                );
            }
        }

        return $value;
    }

    /**
     * Process a union type (e.g., string|int, User|null).
     *
     * @param  ReflectionUnionType  $type  The reflection union type
     * @param  mixed  $value  The raw value from JSON
     * @param  DeserializationContext  $context  The deserialization context
     * @param  string  $propertyPath  The property path for error reporting
     * @return mixed The processed value
     */
    private function processUnionType(ReflectionUnionType $type, mixed $value, DeserializationContext $context, string $propertyPath): mixed
    {
        $types = $type->getTypes();

        // Try each type in the union
        foreach ($types as $namedType) {
            if (! $namedType instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $namedType->getName();

            // If it's a class type and value is an array, try to hydrate
            if (! $namedType->isBuiltin() && class_exists($typeName) && is_array($value)) {
                try {
                    /** @var array<string, mixed> $value */
                    return $this->hydrate($typeName, $value, $context, $propertyPath);
                } catch (\Exception) {
                    // Try next type in union
                    continue;
                }
            }

            // For built-in types, check if value matches
            if ($namedType->isBuiltin()) {
                $matchesType = match ($typeName) {
                    'string' => is_string($value),
                    'int' => is_int($value),
                    'float' => is_float($value) || is_int($value),
                    'bool' => is_bool($value),
                    'array' => is_array($value),
                    'null' => $value === null,
                    default => false,
                };

                if ($matchesType) {
                    return $this->coerceType($value, $typeName, $context, $propertyPath);
                }
            }
        }

        // No type matched, return as-is
        return $value;
    }

    /**
     * Coerce a value to a specific built-in type.
     *
     * @param  mixed  $value  The value to coerce
     * @param  string  $typeName  The target type name
     * @param  DeserializationContext  $context  The deserialization context
     * @param  string  $propertyPath  The property path for error reporting
     * @return mixed The coerced value
     */
    private function coerceType(mixed $value, string $typeName, DeserializationContext $context, string $propertyPath = ''): mixed
    {
        // Validate string length for string types
        if ($typeName === 'string' && is_string($value)) {
            $context->validateString($value);
        }

        // Validate array size for array types
        if ($typeName === 'array' && is_array($value)) {
            $context->validateArray($value);
        }

        return $this->typeCoercer->coerce(
            $value,
            $typeName,
            $context->getTypeCoercionMode(),
            $propertyPath
        );
    }

    /**
     * Set a property value on an object using reflection.
     *
     * @param  object  $object  The object to set the property on
     * @param  string  $propertyName  The property name
     * @param  mixed  $value  The value to set
     */
    private function setPropertyValue(object $object, string $propertyName, mixed $value): void
    {
        try {
            $reflection = new ReflectionProperty($object, $propertyName);
            $reflection->setAccessible(true);
            $reflection->setValue($object, $value);
        } catch (\ReflectionException $e) {
            // Property doesn't exist - ignore if not strict
            throw new TypeException("Property {$propertyName} does not exist on ".get_class($object), 0, $e);
        }
    }
}
