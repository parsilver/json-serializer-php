<?php

declare(strict_types=1);

use Farzai\JsonSerializer\JsonSerializer;

/**
 * Small Data Benchmarks
 *
 * Tests serialization and deserialization performance for small data structures (<1KB).
 * Compares JsonSerializer against native json_encode/json_decode.
 */
class SmallDataBench
{
    private array $smallData;
    private string $smallJson;
    private SimpleClass $simpleObject;
    private NestedClass $nestedObject;

    public function __construct()
    {
        // Load small fixture
        $this->smallData = json_decode(
            file_get_contents(__DIR__ . '/fixtures/small.json'),
            true
        );
        $this->smallJson = json_encode($this->smallData);

        // Create test objects
        $this->simpleObject = new SimpleClass(
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            age: 30
        );

        $this->nestedObject = new NestedClass(
            id: 1,
            title: 'Test Post',
            author: new AuthorClass(
                id: 1,
                name: 'John Doe',
                email: 'john@example.com'
            ),
            tags: ['php', 'benchmark', 'json']
        );
    }

    /**
     * Benchmark: Serialize simple array with native json_encode
     */
    public function benchNativeEncodeArray(): void
    {
        json_encode($this->smallData);
    }

    /**
     * Benchmark: Serialize simple array with JsonSerializer
     */
    public function benchJsonSerializerEncodeArray(): void
    {
        JsonSerializer::encode($this->smallData);
    }

    /**
     * Benchmark: Deserialize to array with native json_decode
     */
    public function benchNativeDecodeArray(): void
    {
        json_decode($this->smallJson, true);
    }

    /**
     * Benchmark: Deserialize to array with JsonSerializer
     */
    public function benchJsonSerializerDecodeArray(): void
    {
        JsonSerializer::decode($this->smallJson);
    }

    /**
     * Benchmark: Serialize simple object with native json_encode
     */
    public function benchNativeEncodeObject(): void
    {
        json_encode($this->simpleObject);
    }

    /**
     * Benchmark: Serialize simple object with JsonSerializer
     */
    public function benchJsonSerializerEncodeObject(): void
    {
        JsonSerializer::encode($this->simpleObject);
    }

    /**
     * Benchmark: Serialize nested object with native json_encode
     */
    public function benchNativeEncodeNestedObject(): void
    {
        json_encode($this->nestedObject);
    }

    /**
     * Benchmark: Serialize nested object with JsonSerializer
     */
    public function benchJsonSerializerEncodeNestedObject(): void
    {
        JsonSerializer::encode($this->nestedObject);
    }

    /**
     * Benchmark: Deserialize to class with JsonSerializer
     */
    public function benchJsonSerializerDecodeToClass(): void
    {
        $json = '{"id":1,"name":"Test User","email":"test@example.com","age":30}';
        JsonSerializer::decodeToClass($json, SimpleClass::class);
    }

    /**
     * Benchmark: Deserialize nested object with JsonSerializer
     */
    public function benchJsonSerializerDecodeNestedObject(): void
    {
        $json = '{"id":1,"title":"Test Post","author":{"id":1,"name":"John Doe","email":"john@example.com"},"tags":["php","benchmark","json"]}';
        JsonSerializer::decodeToClass($json, NestedClass::class);
    }
}

// Test classes for benchmarking
class SimpleClass
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $age
    ) {
    }
}

class AuthorClass
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email
    ) {
    }
}

class NestedClass
{
    public function __construct(
        public int $id,
        public string $title,
        public AuthorClass $author,
        public array $tags
    ) {
    }
}
