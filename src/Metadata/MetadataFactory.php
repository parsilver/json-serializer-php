<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Metadata;

use Farzai\JsonSerializer\Attributes\DateFormat;
use Farzai\JsonSerializer\Attributes\Discriminator;
use Farzai\JsonSerializer\Attributes\Ignore;
use Farzai\JsonSerializer\Attributes\NamingStrategy as NamingStrategyAttribute;
use Farzai\JsonSerializer\Attributes\SerializedName;
use Farzai\JsonSerializer\Attributes\Since;
use Farzai\JsonSerializer\Attributes\Transformer;
use Farzai\JsonSerializer\Attributes\Type;
use Farzai\JsonSerializer\Attributes\Until;
use Farzai\JsonSerializer\Attributes\VirtualProperty;
use Farzai\JsonSerializer\NamingStrategy\NamingStrategyFactory;
use Farzai\JsonSerializer\Transformers\DateTimeTransformer;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Factory for creating class metadata from reflection.
 *
 * Processes PHP attributes and builds metadata structures
 * for efficient serialization.
 */
class MetadataFactory
{
    private readonly NamingStrategyFactory $namingStrategyFactory;

    public function __construct()
    {
        $this->namingStrategyFactory = new NamingStrategyFactory;
    }

    /**
     * Create metadata for a class.
     *
     * @param  class-string  $className
     */
    public function createForClass(string $className): ClassMetadata
    {
        $reflection = new ReflectionClass($className);

        // Get class-level attributes
        $classNamingStrategy = $this->getClassNamingStrategy($reflection);
        $classSince = $this->getClassSince($reflection);
        $classUntil = $this->getClassUntil($reflection);
        $discriminator = $this->getDiscriminator($reflection);

        // Process properties
        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            $metadata = $this->createPropertyMetadata($property, $classNamingStrategy);
            $properties[$metadata->phpName] = $metadata;
        }

        // Process virtual properties (methods with VirtualProperty attribute)
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $virtualProperty = $this->getVirtualProperty($method);
            if ($virtualProperty !== null) {
                $properties[$virtualProperty->phpName] = $virtualProperty;
            }
        }

        return new ClassMetadata(
            className: $className,
            properties: $properties,
            namingStrategy: $classNamingStrategy,
            sinceVersion: $classSince,
            untilVersion: $classUntil,
            discriminatorField: $discriminator['field'] ?? null,
            discriminatorMap: $discriminator['map'] ?? []
        );
    }

    /**
     * Create metadata for a property.
     */
    private function createPropertyMetadata(
        ReflectionProperty $property,
        ?string $classNamingStrategy
    ): PropertyMetadata {
        $phpName = $property->getName();

        // Check if property should be ignored
        $ignore = ! empty($property->getAttributes(Ignore::class));

        // Get serialized name
        $serializedName = $this->getSerializedName($property, $phpName, $classNamingStrategy);

        // Get type hint
        $type = $this->getTypeHint($property);

        // Get transformer
        [$transformerClass, $transformerOptions] = $this->getTransformer($property);

        // Get versioning
        $sinceVersion = $this->getSince($property);
        $untilVersion = $this->getUntil($property);

        return new PropertyMetadata(
            phpName: $phpName,
            serializedName: $serializedName,
            ignore: $ignore,
            type: $type,
            transformerClass: $transformerClass,
            transformerOptions: $transformerOptions,
            sinceVersion: $sinceVersion,
            untilVersion: $untilVersion
        );
    }

    /**
     * Get the serialized name for a property.
     */
    private function getSerializedName(
        ReflectionProperty $property,
        string $phpName,
        ?string $classNamingStrategy
    ): string {
        // Check for SerializedName attribute
        $attributes = $property->getAttributes(SerializedName::class);
        if (! empty($attributes)) {
            $instance = $attributes[0]->newInstance();

            return $instance->name;
        }

        // Check for property-level naming strategy
        $propertyStrategy = $this->getPropertyNamingStrategy($property);
        $strategy = $propertyStrategy ?? $classNamingStrategy;

        if ($strategy !== null) {
            $namingStrategy = $this->namingStrategyFactory->create($strategy);

            return $namingStrategy->transform($phpName);
        }

        return $phpName;
    }

    /**
     * Get the type hint from Type attribute.
     */
    private function getTypeHint(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(Type::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->type;
    }

    /**
     * Get the transformer configuration.
     *
     * @return array{0: class-string<\Farzai\JsonSerializer\Contracts\TransformerInterface>|null, 1: array<string, mixed>}
     */
    private function getTransformer(ReflectionProperty $property): array
    {
        // Check for explicit Transformer attribute
        $transformerAttrs = $property->getAttributes(Transformer::class);
        if (! empty($transformerAttrs)) {
            $instance = $transformerAttrs[0]->newInstance();
            /** @var class-string<\Farzai\JsonSerializer\Contracts\TransformerInterface> $transformerClass */
            $transformerClass = $instance->class;

            return [$transformerClass, $instance->options];
        }

        // Check for DateFormat attribute (implies DateTimeTransformer)
        $dateFormatAttrs = $property->getAttributes(DateFormat::class);
        if (! empty($dateFormatAttrs)) {
            $instance = $dateFormatAttrs[0]->newInstance();

            // Detect if property is DateTime or DateTimeImmutable
            $type = $property->getType();
            $useImmutable = true;
            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();
                if ($typeName === 'DateTime') {
                    $useImmutable = false;
                }
            }

            return [DateTimeTransformer::class, [
                'format' => $instance->format,
                'immutable' => $useImmutable,
            ]];
        }

        return [null, []];
    }

    /**
     * Get class-level naming strategy.
     *
     * @param  ReflectionClass<object>  $reflection
     */
    private function getClassNamingStrategy(ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(NamingStrategyAttribute::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->strategy;
    }

    /**
     * Get property-level naming strategy.
     */
    private function getPropertyNamingStrategy(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(NamingStrategyAttribute::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->strategy;
    }

    /**
     * Get Since version for property.
     */
    private function getSince(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(Since::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->version;
    }

    /**
     * Get Until version for property.
     */
    private function getUntil(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(Until::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->version;
    }

    /**
     * Get class-level Since version.
     *
     * @param  ReflectionClass<object>  $reflection
     */
    private function getClassSince(ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(Since::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->version;
    }

    /**
     * Get class-level Until version.
     *
     * @param  ReflectionClass<object>  $reflection
     */
    private function getClassUntil(ReflectionClass $reflection): ?string
    {
        $attributes = $reflection->getAttributes(Until::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();

        return $instance->version;
    }

    /**
     * Get discriminator configuration from class.
     *
     * @param  ReflectionClass<object>  $reflection
     * @return array{field: string, map: array<string, class-string>}|array{}
     */
    private function getDiscriminator(ReflectionClass $reflection): array
    {
        $attributes = $reflection->getAttributes(Discriminator::class);
        if (empty($attributes)) {
            return [];
        }

        $instance = $attributes[0]->newInstance();

        return [
            'field' => $instance->field,
            'map' => $instance->map,
        ];
    }

    /**
     * Get virtual property metadata from method.
     */
    private function getVirtualProperty(ReflectionMethod $method): ?PropertyMetadata
    {
        $attributes = $method->getAttributes(VirtualProperty::class);
        if (empty($attributes)) {
            return null;
        }

        $instance = $attributes[0]->newInstance();
        $methodName = $method->getName();
        $phpName = $instance->name ?? $methodName;

        return new PropertyMetadata(
            phpName: $phpName,
            serializedName: $phpName,
            isVirtual: true,
            virtualMethod: $methodName
        );
    }
}
