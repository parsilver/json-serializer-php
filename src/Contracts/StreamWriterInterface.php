<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Contracts;

/**
 * Interface for write-only stream operations.
 *
 * This interface provides a simplified API for streaming serialization
 * where only write operations are needed. It's optimized for performance
 * and memory efficiency in write-only scenarios.
 */
interface StreamWriterInterface
{
    /**
     * Write data to the stream.
     *
     * @param  string  $data  The data to write
     * @return int The number of bytes written
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException If write fails
     */
    public function write(string $data): int;

    /**
     * Flush any buffered data to the underlying storage.
     *
     * @return bool True on success, false on failure
     */
    public function flush(): bool;

    /**
     * Close the stream and release any resources.
     */
    public function close(): void;

    /**
     * Check if the stream is writable.
     *
     * @return bool True if writable, false otherwise
     */
    public function isWritable(): bool;
}
