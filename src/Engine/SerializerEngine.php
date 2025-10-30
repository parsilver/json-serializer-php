<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Buffer\WriteBuffer;
use Farzai\JsonSerializer\Cache\MetadataCache;
use Farzai\JsonSerializer\Contracts\StreamWriterInterface;
use Farzai\JsonSerializer\Events\EventDispatcher;
use Farzai\JsonSerializer\Events\PostSerializeEvent;
use Farzai\JsonSerializer\Events\PreSerializeEvent;
use Farzai\JsonSerializer\Middleware\SerializationMiddlewareChain;
use Farzai\JsonSerializer\Types\TypeHandlerFactory;
use Farzai\JsonSerializer\Types\TypeRegistry;

/**
 * Main serialization engine that orchestrates the serialization process.
 *
 * This engine coordinates between the type system, streaming encoder,
 * and output streams to convert PHP values to JSON.
 */
class SerializerEngine
{
    private readonly TypeRegistry $typeRegistry;

    private readonly StreamingEncoder $encoder;

    private readonly MetadataCache $metadataCache;

    private readonly EventDispatcher $eventDispatcher;

    private readonly SerializationMiddlewareChain $middlewareChain;

    /**
     * Create a new serializer engine.
     *
     * @param  TypeRegistry|null  $typeRegistry  Optional custom type registry
     * @param  MetadataCache|null  $metadataCache  Optional metadata cache
     * @param  int  $defaultMaxDepth  Default max depth for contexts
     * @param  bool  $defaultDetectCircularReferences  Default circular reference detection
     * @param  bool  $defaultPrettyPrint  Default pretty print setting
     * @param  bool  $defaultStrictTypes  Default strict types setting
     * @param  string|null  $defaultVersion  Default version for serialization
     * @param  EventDispatcher|null  $eventDispatcher  Optional event dispatcher for hooks
     * @param  SerializationMiddlewareChain|null  $middlewareChain  Optional middleware chain
     */
    public function __construct(
        ?TypeRegistry $typeRegistry = null,
        ?MetadataCache $metadataCache = null,
        private readonly int $defaultMaxDepth = 512,
        private readonly bool $defaultDetectCircularReferences = true,
        private readonly bool $defaultPrettyPrint = false,
        private readonly bool $defaultStrictTypes = true,
        private readonly ?string $defaultVersion = null,
        ?EventDispatcher $eventDispatcher = null,
        ?SerializationMiddlewareChain $middlewareChain = null,
    ) {
        $this->typeRegistry = $typeRegistry ?? (new TypeHandlerFactory)->createDefaultRegistry();
        $this->metadataCache = $metadataCache ?? new MetadataCache;
        $this->encoder = new StreamingEncoder($this->typeRegistry, $this->metadataCache);
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher;
        $this->middlewareChain = $middlewareChain ?? new SerializationMiddlewareChain;
    }

    /**
     * Serialize a value to a JSON string.
     *
     * @param  mixed  $value  The value to serialize
     * @param  SerializationContext|null  $context  Optional serialization context
     * @return string The JSON string
     */
    public function serialize(mixed $value, ?SerializationContext $context = null): string
    {
        $context ??= $this->createDefaultContext();

        // Define the core serialization logic
        $core = function (mixed $val, SerializationContext $ctx) {
            // Dispatch pre-serialize event
            $preEvent = new PreSerializeEvent($val, $ctx);
            $this->eventDispatcher->dispatch($preEvent);
            $val = $preEvent->getValue();

            // Perform serialization
            $json = $this->encoder->encodeToString($val, $ctx);

            // Dispatch post-serialize event
            $postEvent = new PostSerializeEvent($val, $json, $ctx);
            $this->eventDispatcher->dispatch($postEvent);

            return $postEvent->getJson();
        };

        // Execute through middleware chain if not empty
        if (! $this->middlewareChain->isEmpty()) {
            return $this->middlewareChain->execute($value, $context, $core);
        }

        // Execute directly if no middleware
        return $core($value, $context);
    }

    /**
     * Create a default serialization context with configured defaults.
     */
    private function createDefaultContext(): SerializationContext
    {
        return new SerializationContext(
            maxDepth: $this->defaultMaxDepth,
            detectCircularReferences: $this->defaultDetectCircularReferences,
            prettyPrint: $this->defaultPrettyPrint,
            strictTypes: $this->defaultStrictTypes,
            version: $this->defaultVersion
        );
    }

    /**
     * Serialize a value to a stream.
     *
     * @param  mixed  $value  The value to serialize
     * @param  StreamWriterInterface  $stream  The stream to write to
     * @param  SerializationContext|null  $context  Optional serialization context
     * @return int The number of bytes written
     */
    public function serializeToStream(
        mixed $value,
        StreamWriterInterface $stream,
        ?SerializationContext $context = null
    ): int {
        $context ??= $this->createDefaultContext();

        // Wrap stream in a write buffer for performance
        $bufferedStream = new WriteBuffer($stream);
        $bytesWritten = $this->encoder->encode($value, $bufferedStream, $context);

        // Ensure all data is flushed
        $bufferedStream->flush();

        return $bytesWritten;
    }

    /**
     * Serialize a value to a file.
     *
     * @param  mixed  $value  The value to serialize
     * @param  string  $filePath  The file path to write to
     * @param  SerializationContext|null  $context  Optional serialization context
     * @return int The number of bytes written
     */
    public function serializeToFile(
        mixed $value,
        string $filePath,
        ?SerializationContext $context = null
    ): int {
        $context ??= $this->createDefaultContext();

        $stream = new \Farzai\JsonSerializer\Stream\FileStream($filePath, 'w');

        try {
            return $this->serializeToStream($value, $stream, $context);
        } finally {
            $stream->close();
        }
    }

    /**
     * Get the event dispatcher.
     *
     * This allows registering event listeners for serialization hooks.
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * Get the middleware chain.
     *
     * This allows adding middleware to the serialization pipeline.
     */
    public function getMiddlewareChain(): SerializationMiddlewareChain
    {
        return $this->middlewareChain;
    }
}
