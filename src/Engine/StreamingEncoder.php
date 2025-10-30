<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Cache\MetadataCache;
use Farzai\JsonSerializer\Contracts\StreamWriterInterface;
use Farzai\JsonSerializer\Types\TypeRegistry;

/**
 * Streaming JSON encoder that writes directly to a stream.
 *
 * This encoder uses a type registry to find appropriate handlers
 * for values and writes the JSON output incrementally to a stream,
 * avoiding the need to build the entire JSON string in memory.
 */
class StreamingEncoder
{
    /**
     * Create a new streaming encoder.
     *
     * @param  TypeRegistry  $typeRegistry  The type registry for finding handlers
     * @param  MetadataCache|null  $metadataCache  Optional metadata cache (currently unused, reserved for future use)
     */
    public function __construct(
        private readonly TypeRegistry $typeRegistry,
        /** @phpstan-ignore-next-line property.onlyWritten */
        private readonly ?MetadataCache $metadataCache = null
    ) {}

    /**
     * Encode a value to JSON and write to the stream.
     *
     * @param  mixed  $value  The value to encode
     * @param  StreamWriterInterface  $stream  The stream to write to
     * @param  SerializationContext  $context  The serialization context
     * @return int The number of bytes written
     */
    public function encode(mixed $value, StreamWriterInterface $stream, SerializationContext $context): int
    {
        $handler = $this->typeRegistry->findHandler($value);
        $json = $handler->serialize($value, $context);

        return $stream->write($json);
    }

    /**
     * Encode a value to a JSON string.
     *
     * This is a convenience method that encodes to a string instead of a stream.
     *
     * @param  mixed  $value  The value to encode
     * @param  SerializationContext  $context  The serialization context
     * @return string The JSON string
     */
    public function encodeToString(mixed $value, SerializationContext $context): string
    {
        $handler = $this->typeRegistry->findHandler($value);

        return $handler->serialize($value, $context);
    }
}
