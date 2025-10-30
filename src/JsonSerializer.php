<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer;

use Farzai\JsonSerializer\Builder\SerializerBuilder;
use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\Engine\SerializerEngine;
use Farzai\JsonSerializer\Engine\StreamingDeserializer;
use Farzai\JsonSerializer\Stream\FileStream;
use Farzai\JsonSerializer\Stream\JsonPath;
use Farzai\JsonSerializer\Stream\LazyJsonIterator;

/**
 * Simple facade for JSON serialization operations.
 *
 * This class provides a static API for common serialization tasks,
 * making it easy to use the library without having to create builders
 * or configure options for simple use cases.
 */
class JsonSerializer
{
    private static ?SerializerEngine $defaultEngine = null;

    private static ?DeserializerEngine $defaultDeserializer = null;

    /**
     * Encode a value to a JSON string.
     *
     * @param  mixed  $value  The value to encode
     * @param  bool  $prettyPrint  Whether to enable pretty printing (default: false)
     * @return string The JSON string
     */
    public static function encode(mixed $value, bool $prettyPrint = false): string
    {
        $engine = self::getDefaultEngine();
        $context = new SerializationContext(prettyPrint: $prettyPrint);

        return $engine->serialize($value, $context);
    }

    /**
     * Encode a value to a JSON file.
     *
     * @param  mixed  $value  The value to encode
     * @param  string  $filePath  The file path to write to
     * @param  bool  $prettyPrint  Whether to enable pretty printing (default: false)
     * @return int The number of bytes written
     */
    public static function encodeToFile(mixed $value, string $filePath, bool $prettyPrint = false): int
    {
        $engine = self::getDefaultEngine();
        $context = new SerializationContext(prettyPrint: $prettyPrint);

        return $engine->serializeToFile($value, $filePath, $context);
    }

    /**
     * Encode a value with pretty printing enabled.
     *
     * @param  mixed  $value  The value to encode
     * @return string The pretty-printed JSON string
     */
    public static function encodePretty(mixed $value): string
    {
        return self::encode($value, prettyPrint: true);
    }

    /**
     * Create a new serializer builder for advanced configuration.
     */
    public static function builder(): SerializerBuilder
    {
        return new SerializerBuilder;
    }

    /**
     * Get the default serializer engine.
     */
    public static function getDefaultEngine(): SerializerEngine
    {
        if (self::$defaultEngine === null) {
            self::$defaultEngine = (new SerializerBuilder)->build();
        }

        return self::$defaultEngine;
    }

    /**
     * Set a custom default engine.
     *
     * This is useful for setting up custom configuration that will be
     * used by all static methods.
     *
     * @param  SerializerEngine  $engine  The engine to use as default
     */
    public static function setDefaultEngine(SerializerEngine $engine): void
    {
        self::$defaultEngine = $engine;
    }

    /**
     * Reset the default engine to null (will be recreated on next use).
     */
    public static function resetDefaultEngine(): void
    {
        self::$defaultEngine = null;
    }

    /**
     * Decode a JSON string to a PHP value.
     *
     * @param  string  $json  The JSON string to decode
     * @return mixed The decoded value
     */
    public static function decode(string $json): mixed
    {
        $deserializer = self::getDefaultDeserializer();

        return $deserializer->deserialize($json);
    }

    /**
     * Decode a JSON string to a specific class.
     *
     * @template T of object
     *
     * @param  string  $json  The JSON string to decode
     * @param  class-string<T>  $className  The class to deserialize into
     * @return T The deserialized object
     */
    public static function decodeToClass(string $json, string $className): object
    {
        $deserializer = self::getDefaultDeserializer();

        return $deserializer->deserializeToClass($json, $className);
    }

    /**
     * Decode a JSON array to an array of class instances.
     *
     * @template T of object
     *
     * @param  string  $json  The JSON string to decode
     * @param  class-string<T>  $className  The class to deserialize each item into
     * @return array<T> Array of deserialized objects
     */
    public static function decodeArray(string $json, string $className): array
    {
        $deserializer = self::getDefaultDeserializer();

        return $deserializer->deserializeArray($json, $className);
    }

    /**
     * Get the default deserializer engine.
     */
    public static function getDefaultDeserializer(): DeserializerEngine
    {
        if (self::$defaultDeserializer === null) {
            self::$defaultDeserializer = new DeserializerEngine;
        }

        return self::$defaultDeserializer;
    }

    /**
     * Set a custom default deserializer.
     *
     * @param  DeserializerEngine  $deserializer  The deserializer to use as default
     */
    public static function setDefaultDeserializer(DeserializerEngine $deserializer): void
    {
        self::$defaultDeserializer = $deserializer;
    }

    /**
     * Reset the default deserializer to null (will be recreated on next use).
     */
    public static function resetDefaultDeserializer(): void
    {
        self::$defaultDeserializer = null;
    }

    /**
     * Create a streaming deserializer from a file path.
     *
     * This allows processing large JSON files without loading everything into memory.
     *
     * @param  string  $filePath  The path to the JSON file
     * @return StreamingDeserializer The streaming deserializer instance
     */
    public static function streamFromFile(string $filePath): StreamingDeserializer
    {
        $stream = new FileStream($filePath);

        return new StreamingDeserializer($stream);
    }

    /**
     * Create a streaming deserializer from a stream.
     *
     * @param  StreamInterface  $stream  The stream to read from
     * @return StreamingDeserializer The streaming deserializer instance
     */
    public static function stream(StreamInterface $stream): StreamingDeserializer
    {
        return new StreamingDeserializer($stream);
    }

    /**
     * Create a lazy iterator for a JSON array file.
     *
     * This is useful for processing large JSON arrays where each element
     * is deserialized on-demand.
     *
     * @template T of object
     *
     * @param  string  $filePath  The path to the JSON file (must contain an array)
     * @param  class-string<T>|null  $className  Optional class to deserialize elements to
     * @return LazyJsonIterator<T> The lazy iterator
     */
    public static function iterateFile(string $filePath, ?string $className = null): LazyJsonIterator
    {
        $stream = new FileStream($filePath);

        return new LazyJsonIterator($stream, $className, self::getDefaultDeserializer());
    }

    /**
     * Create a lazy iterator for a JSON array stream.
     *
     * @template T of object
     *
     * @param  StreamInterface  $stream  The stream to read from (must contain an array)
     * @param  class-string<T>|null  $className  Optional class to deserialize elements to
     * @return LazyJsonIterator<T> The lazy iterator
     */
    public static function iterate(StreamInterface $stream, ?string $className = null): LazyJsonIterator
    {
        return new LazyJsonIterator($stream, $className, self::getDefaultDeserializer());
    }

    /**
     * Extract a specific path from a JSON file without loading the entire file.
     *
     * Path syntax:
     * - `.field` - Access object field
     * - `[0]` - Access array element by index
     * - `.field[0]` - Access array element in nested field
     *
     * @param  string  $filePath  The path to the JSON file
     * @param  string  $path  The path expression to extract
     * @return mixed The extracted value, or null if path not found
     */
    public static function extractPath(string $filePath, string $path): mixed
    {
        $stream = new FileStream($filePath);
        $jsonPath = new JsonPath($stream);

        return $jsonPath->extract($path);
    }

    /**
     * Extract multiple paths from a JSON file at once.
     *
     * @param  string  $filePath  The path to the JSON file
     * @param  array<string>  $paths  Array of path expressions
     * @return array<string, mixed> Map of paths to extracted values
     */
    public static function extractPaths(string $filePath, array $paths): array
    {
        $stream = new FileStream($filePath);
        $jsonPath = new JsonPath($stream);

        return $jsonPath->extractMultiple($paths);
    }
}
