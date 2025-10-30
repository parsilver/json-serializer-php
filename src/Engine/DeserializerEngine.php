<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Cache\MetadataCache;
use Farzai\JsonSerializer\Events\EventDispatcher;
use Farzai\JsonSerializer\Events\PostDeserializeEvent;
use Farzai\JsonSerializer\Events\PreDeserializeEvent;
use Farzai\JsonSerializer\Exceptions\TypeException;
use Farzai\JsonSerializer\Middleware\DeserializationMiddlewareChain;
use Farzai\JsonSerializer\Security\SecurityConfig;
use Farzai\JsonSerializer\Transformers\TransformerRegistry;
use Farzai\JsonSerializer\Types\TypeCoercionMode;

/**
 * Main deserialization engine that orchestrates JSON to PHP conversion.
 *
 * This engine coordinates between the JSON parser, type system, and object hydration
 * to convert JSON strings into PHP values.
 */
class DeserializerEngine
{
    private readonly ObjectHydrator $hydrator;

    private readonly EventDispatcher $eventDispatcher;

    private readonly DeserializationMiddlewareChain $middlewareChain;

    /**
     * Create a new deserializer engine.
     *
     * @param  MetadataCache|null  $metadataCache  Optional metadata cache
     * @param  TransformerRegistry|null  $transformerRegistry  Optional transformer registry
     * @param  int  $defaultMaxDepth  Default max depth for contexts
     * @param  bool  $defaultStrictTypes  Default strict types setting
     * @param  string|null  $defaultVersion  Default version for deserialization
     * @param  bool  $defaultAllowExtraProperties  Default allow extra properties setting
     * @param  TypeCoercionMode  $defaultTypeCoercionMode  Default type coercion mode
     * @param  SecurityConfig|null  $defaultSecurityConfig  Default security configuration
     * @param  EventDispatcher|null  $eventDispatcher  Optional event dispatcher for hooks
     * @param  DeserializationMiddlewareChain|null  $middlewareChain  Optional middleware chain
     */
    public function __construct(
        ?MetadataCache $metadataCache = null,
        ?TransformerRegistry $transformerRegistry = null,
        private readonly int $defaultMaxDepth = 512,
        private readonly bool $defaultStrictTypes = true,
        private readonly ?string $defaultVersion = null,
        private readonly bool $defaultAllowExtraProperties = true,
        private readonly TypeCoercionMode $defaultTypeCoercionMode = TypeCoercionMode::SAFE,
        private readonly ?SecurityConfig $defaultSecurityConfig = null,
        ?EventDispatcher $eventDispatcher = null,
        ?DeserializationMiddlewareChain $middlewareChain = null,
    ) {
        $this->hydrator = new ObjectHydrator($metadataCache, $transformerRegistry);
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher;
        $this->middlewareChain = $middlewareChain ?? new DeserializationMiddlewareChain;
    }

    /**
     * Deserialize JSON to a PHP value.
     *
     * @param  string  $json  The JSON string to deserialize
     * @param  DeserializationContext|null  $context  Optional deserialization context
     * @return mixed The deserialized value
     */
    public function deserialize(string $json, ?DeserializationContext $context = null): mixed
    {
        $this->createDefaultContext();

        $data = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * Deserialize JSON to a specific class.
     *
     * @template T of object
     *
     * @param  string  $json  The JSON string to deserialize
     * @param  class-string<T>  $className  The class to deserialize into
     * @param  DeserializationContext|null  $context  Optional deserialization context
     * @return T The deserialized object
     */
    public function deserializeToClass(string $json, string $className, ?DeserializationContext $context = null): object
    {
        $context ??= $this->createDefaultContext();

        // Define the core deserialization logic
        /**
         * @param  string  $j
         * @param  class-string<T>  $c
         * @param  DeserializationContext  $ctx
         * @return T
         */
        $core = function (string $j, string $c, DeserializationContext $ctx): object {
            // Dispatch pre-deserialize event
            /** @phpstan-ignore argument.type */
            $preEvent = new PreDeserializeEvent($j, $c, $ctx);
            $this->eventDispatcher->dispatch($preEvent);
            $j = $preEvent->getJson();

            // Validate JSON string length before decoding
            $ctx->validateString($j);

            $data = json_decode($j, associative: true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                throw new TypeException('Expected JSON object for class deserialization, got '.gettype($data));
            }

            // Validate the decoded data array
            $ctx->validateArray($data);

            // Ensure array has string keys (JSON objects become associative arrays)
            /** @var array<string, mixed> $data */
            /** @phpstan-ignore argument.type, argument.templateType */
            $result = $this->hydrator->hydrate($c, $data, $ctx);

            // Dispatch post-deserialize event
            $postEvent = new PostDeserializeEvent($j, $result, $ctx);
            $this->eventDispatcher->dispatch($postEvent);

            /** @phpstan-ignore return.type */
            return $postEvent->getResult();
        };

        // Execute through middleware chain if not empty
        if (! $this->middlewareChain->isEmpty()) {
            /** @var T */
            return $this->middlewareChain->execute($json, $className, $context, $core);
        }

        // Execute directly if no middleware
        /** @var T */
        return $core($json, $className, $context);
    }

    /**
     * Deserialize JSON array to array of class instances.
     *
     * @template T of object
     *
     * @param  string  $json  The JSON string to deserialize
     * @param  class-string<T>  $className  The class to deserialize each item into
     * @param  DeserializationContext|null  $context  Optional deserialization context
     * @return array<T> Array of deserialized objects
     */
    public function deserializeArray(string $json, string $className, ?DeserializationContext $context = null): array
    {
        $context ??= $this->createDefaultContext();

        $data = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new TypeException('Expected JSON array for array deserialization, got '.gettype($data));
        }

        $result = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new TypeException('Expected JSON object in array, got '.gettype($item));
            }

            // Ensure array has string keys (JSON objects become associative arrays)
            /** @var array<string, mixed> $item */
            $result[] = $this->hydrator->hydrate($className, $item, $context);
        }

        return $result;
    }

    /**
     * Create a default deserialization context with configured defaults.
     */
    private function createDefaultContext(): DeserializationContext
    {
        return new DeserializationContext(
            maxDepth: $this->defaultMaxDepth,
            strictTypes: $this->defaultStrictTypes,
            version: $this->defaultVersion,
            allowExtraProperties: $this->defaultAllowExtraProperties,
            typeCoercionMode: $this->defaultTypeCoercionMode,
            securityConfig: $this->defaultSecurityConfig
        );
    }

    /**
     * Get the event dispatcher.
     *
     * This allows registering event listeners for deserialization hooks.
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * Get the middleware chain.
     *
     * This allows adding middleware to the deserialization pipeline.
     */
    public function getMiddlewareChain(): DeserializationMiddlewareChain
    {
        return $this->middlewareChain;
    }
}
