<?php

declare(strict_types=1);

use Farzai\JsonSerializer\JsonSerializer;

/**
 * Large Data Benchmarks
 *
 * Tests serialization and deserialization performance for large data structures (50-100MB).
 * Compares JsonSerializer against native json_encode/json_decode.
 * Tests streaming capabilities and memory efficiency.
 *
 * @Revs(1)
 * @Iterations(2)
 * @Warmup(1)
 */
class LargeDataBench
{
    private array $data50mb;
    private array $data100mb;
    private string $json50mb;
    private string $json100mb;
    private string $fixture50mbPath;
    private string $fixture100mbPath;

    public function __construct()
    {
        $this->fixture50mbPath = __DIR__ . '/fixtures/large-50mb.json';
        $this->fixture100mbPath = __DIR__ . '/fixtures/large-100mb.json';

        // Load large fixtures
        $this->data50mb = json_decode(
            file_get_contents($this->fixture50mbPath),
            true
        );
        $this->data100mb = json_decode(
            file_get_contents($this->fixture100mbPath),
            true
        );

        // Pre-encode for deserialization benchmarks
        $this->json50mb = json_encode($this->data50mb);
        $this->json100mb = json_encode($this->data100mb);
    }

    /**
     * @Groups({"50mb", "encode", "native"})
     */
    public function benchNativeEncode50MB(): void
    {
        json_encode($this->data50mb);
    }

    /**
     * @Groups({"50mb", "encode", "serializer"})
     */
    public function benchJsonSerializerEncode50MB(): void
    {
        JsonSerializer::encode($this->data50mb);
    }

    /**
     * @Groups({"50mb", "decode", "native"})
     */
    public function benchNativeDecode50MB(): void
    {
        json_decode($this->json50mb, true);
    }

    /**
     * @Groups({"50mb", "decode", "serializer"})
     */
    public function benchJsonSerializerDecode50MB(): void
    {
        JsonSerializer::decode($this->json50mb);
    }

    /**
     * @Groups({"100mb", "encode", "native"})
     */
    public function benchNativeEncode100MB(): void
    {
        json_encode($this->data100mb);
    }

    /**
     * @Groups({"100mb", "encode", "serializer"})
     */
    public function benchJsonSerializerEncode100MB(): void
    {
        JsonSerializer::encode($this->data100mb);
    }

    /**
     * @Groups({"100mb", "decode", "native"})
     */
    public function benchNativeDecode100MB(): void
    {
        json_decode($this->json100mb, true);
    }

    /**
     * @Groups({"100mb", "decode", "serializer"})
     */
    public function benchJsonSerializerDecode100MB(): void
    {
        JsonSerializer::decode($this->json100mb);
    }

    /**
     * @Groups({"50mb", "streaming", "serializer"})
     */
    public function benchJsonSerializerStreamEncode50MB(): void
    {
        $tempFile = sys_get_temp_dir() . '/bench-50mb.json';
        JsonSerializer::encodeToFile($this->data50mb, $tempFile);
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    /**
     * @Groups({"100mb", "streaming", "serializer"})
     */
    public function benchJsonSerializerStreamEncode100MB(): void
    {
        $tempFile = sys_get_temp_dir() . '/bench-100mb.json';
        JsonSerializer::encodeToFile($this->data100mb, $tempFile);
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
