<?php

declare(strict_types=1);

use Farzai\JsonSerializer\JsonSerializer;

/**
 * Memory Usage Benchmarks
 *
 * Tests memory consumption for various data sizes and operations.
 * Compares memory overhead between JsonSerializer and native functions.
 *
 * @OutputMode("time")
 */
class MemoryBench
{
    private array $dataSmall;

    private array $data1mb;

    private array $data5mb;

    private array $data10mb;

    public function __construct()
    {
        // Load fixtures
        $this->dataSmall = json_decode(
            file_get_contents(__DIR__.'/fixtures/small.json'),
            true
        );
        $this->data1mb = json_decode(
            file_get_contents(__DIR__.'/fixtures/medium-1mb.json'),
            true
        );
        $this->data5mb = json_decode(
            file_get_contents(__DIR__.'/fixtures/medium-5mb.json'),
            true
        );
        $this->data10mb = json_decode(
            file_get_contents(__DIR__.'/fixtures/medium-10mb.json'),
            true
        );
    }

    /**
     * @Groups({"memory", "small", "native"})
     */
    public function benchMemoryNativeSmall(): void
    {
        $before = memory_get_usage();
        json_encode($this->dataSmall);
        $after = memory_get_usage();
        $used = $after - $before;
        // Memory delta tracked by PHPBench
    }

    /**
     * @Groups({"memory", "small", "serializer"})
     */
    public function benchMemorySerializerSmall(): void
    {
        $before = memory_get_usage();
        JsonSerializer::encode($this->dataSmall);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "1mb", "native"})
     */
    public function benchMemoryNative1MB(): void
    {
        $before = memory_get_usage();
        json_encode($this->data1mb);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "1mb", "serializer"})
     */
    public function benchMemorySerializer1MB(): void
    {
        $before = memory_get_usage();
        JsonSerializer::encode($this->data1mb);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "5mb", "native"})
     *
     * @Revs(3)
     */
    public function benchMemoryNative5MB(): void
    {
        $before = memory_get_usage();
        json_encode($this->data5mb);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "5mb", "serializer"})
     *
     * @Revs(3)
     */
    public function benchMemorySerializer5MB(): void
    {
        $before = memory_get_usage();
        JsonSerializer::encode($this->data5mb);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "10mb", "native"})
     *
     * @Revs(2)
     */
    public function benchMemoryNative10MB(): void
    {
        $before = memory_get_usage();
        json_encode($this->data10mb);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "10mb", "serializer"})
     *
     * @Revs(2)
     */
    public function benchMemorySerializer10MB(): void
    {
        $before = memory_get_usage();
        JsonSerializer::encode($this->data10mb);
        $after = memory_get_usage();
        $used = $after - $before;
    }

    /**
     * @Groups({"memory", "peak", "decode"})
     */
    public function benchMemoryPeakDecode5MB(): void
    {
        $json = json_encode($this->data5mb);
        $peakBefore = memory_get_peak_usage();
        JsonSerializer::decode($json);
        $peakAfter = memory_get_peak_usage();
        $peakUsed = $peakAfter - $peakBefore;
    }

    /**
     * @Groups({"memory", "streaming", "serializer"})
     */
    public function benchMemoryStreamingEncode(): void
    {
        $tempFile = sys_get_temp_dir().'/bench-memory-stream.json';
        $peakBefore = memory_get_peak_usage();
        JsonSerializer::encodeToFile($this->data10mb, $tempFile);
        $peakAfter = memory_get_peak_usage();
        $peakUsed = $peakAfter - $peakBefore;

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
