<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types\Handlers;

use Farzai\JsonSerializer\Cache\MetadataCache;
use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\Metadata\PropertyMetadata;
use Farzai\JsonSerializer\Transformers\TransformerRegistry;
use JsonSerializable;
use ReflectionProperty;
use UnitEnum;

/**
 * Advanced object type handler with attribute support.
 *
 * This handler uses metadata caching and supports all PHP attributes:
 * - SerializedName, Ignore, NamingStrategy
 * - Transformer, DateFormat
 * - Since, Until (versioning)
 * - VirtualProperty
 */
class ObjectTypeHandler implements TypeHandlerInterface
{
    private readonly MetadataCache $metadataCache;

    private readonly TransformerRegistry $transformerRegistry;

    public function __construct(
        ?MetadataCache $metadataCache = null,
        ?TransformerRegistry $transformerRegistry = null
    ) {
        $this->metadataCache = $metadataCache ?? new MetadataCache;
        $this->transformerRegistry = $transformerRegistry ?? new TransformerRegistry;
    }

    #[\Override]
    public function supports(mixed $value): bool
    {
        return is_object($value);
    }

    #[\Override]
    public function serialize(mixed $value, SerializationContext $context): string
    {
        if (! is_object($value)) {
            return 'null';
        }

        // Check for circular references
        $context->visitObject($value);
        $context->increaseDepth();

        try {
            // Handle JsonSerializable
            if ($value instanceof JsonSerializable) {
                $data = $value->jsonSerialize();

                return $this->serializeValue($data, $context);
            }

            // Handle stdClass
            if ($value instanceof \stdClass) {
                return $this->serializeStdClass($value, $context);
            }

            // Handle DateTime/DateTimeInterface
            if ($value instanceof \DateTimeInterface) {
                return $this->serializeDateTime($value);
            }

            // Handle regular objects with metadata
            return $this->serializeObjectWithMetadata($value, $context);
        } finally {
            $context->decreaseDepth();
            $context->leaveObject($value);
        }
    }

    #[\Override]
    public function getPriority(): int
    {
        return 10; // Low priority (catch-all for objects)
    }

    /**
     * Serialize a stdClass object.
     */
    private function serializeStdClass(\stdClass $object, SerializationContext $context): string
    {
        $properties = get_object_vars($object);

        return $this->serializePropertiesAsObject($properties, $context);
    }

    /**
     * Serialize an object using metadata and attributes.
     */
    private function serializeObjectWithMetadata(object $object, SerializationContext $context): string
    {
        $className = get_class($object);
        $metadata = $this->metadataCache->get($className);

        // Check class-level versioning
        if (! $metadata->isAvailableInVersion($context->getVersion())) {
            return '{}';
        }

        // Get serializable properties for current version
        $serializableProps = $metadata->getSerializableProperties($context->getVersion());

        $data = [];

        foreach ($serializableProps as $propertyMetadata) {
            // Handle virtual properties
            if ($propertyMetadata->isVirtual) {
                $value = $this->getVirtualPropertyValue($object, $propertyMetadata);
            } else {
                $value = $this->getPropertyValue($object, $propertyMetadata->phpName);
            }

            // Apply transformer if configured
            if ($propertyMetadata->transformerClass !== null) {
                $transformer = $this->transformerRegistry->get($propertyMetadata->transformerClass);
                $value = $transformer->serialize($value, $context, $propertyMetadata->transformerOptions);
            }

            $data[$propertyMetadata->serializedName] = $value;
        }

        return $this->serializePropertiesAsObject($data, $context);
    }

    /**
     * Get property value using reflection.
     */
    private function getPropertyValue(object $object, string $propertyName): mixed
    {
        try {
            $reflection = new ReflectionProperty($object, $propertyName);
            $reflection->setAccessible(true);

            return $reflection->getValue($object);
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Get virtual property value by calling method.
     */
    private function getVirtualPropertyValue(object $object, PropertyMetadata $metadata): mixed
    {
        if ($metadata->virtualMethod === null) {
            return null;
        }

        try {
            $method = new \ReflectionMethod($object, $metadata->virtualMethod);

            return $method->invoke($object);
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Serialize properties as a JSON object.
     *
     * @param  array<string, mixed>  $properties
     */
    private function serializePropertiesAsObject(array $properties, SerializationContext $context): string
    {
        if ($properties === []) {
            return '{}';
        }

        $pairs = [];

        foreach ($properties as $key => $value) {
            $jsonKey = json_encode((string) $key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $jsonValue = $this->serializeValue($value, $context);
            $colon = ':'.$context->getColonSpace();
            $pairs[] = $jsonKey.$colon.$jsonValue;
        }

        if ($context->shouldPrettyPrint()) {
            $context->getLineBreak();
            $indent = $context->getIndentation();
            $innerIndent = str_repeat('    ', $context->getCurrentDepth() + 1);

            return "{\n{$innerIndent}".implode(",\n{$innerIndent}", $pairs)."\n{$indent}}";
        }

        return '{'.implode(',', $pairs).'}';
    }

    /**
     * Serialize a DateTime object.
     */
    private function serializeDateTime(\DateTimeInterface $dateTime): string
    {
        return json_encode($dateTime->format(\DateTimeInterface::ATOM)) ?: '""';
    }

    /**
     * Serialize a value (delegates to appropriate handler).
     */
    private function serializeValue(mixed $value, SerializationContext $context): string
    {
        // Handle null
        if (is_null($value)) {
            return 'null';
        }

        // Handle scalars
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded !== false ? $encoded : '""';
        }

        // Handle arrays
        if (is_array($value)) {
            $handler = new ArrayTypeHandler;

            return $handler->serialize($value, $context);
        }

        // Handle enums before general objects
        if ($value instanceof UnitEnum) {
            $enumHandler = new EnumTypeHandler;

            return $enumHandler->serialize($value, $context);
        }

        // Handle objects
        if (is_object($value)) {
            return $this->serialize($value, $context);
        }

        return 'null';
    }
}
