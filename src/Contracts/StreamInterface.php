<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Contracts;

/**
 * Interface for stream abstraction supporting both reading and writing operations.
 *
 * This interface provides a unified API for working with different types of streams
 * (memory, file, network, etc.) in a streaming serialization context.
 */
interface StreamInterface
{
    /**
     * Read data from the stream.
     *
     * @param  int  $length  Maximum number of bytes to read
     * @return string The data read from the stream
     */
    public function read(int $length): string;

    /**
     * Check if the stream has reached end-of-file.
     *
     * @return bool True if EOF reached, false otherwise
     */
    public function eof(): bool;

    /**
     * Seek to a specific position in the stream.
     *
     * @param  int  $offset  The offset to seek to
     * @param  int  $whence  The seek mode (SEEK_SET, SEEK_CUR, SEEK_END)
     * @return bool True on success, false on failure
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * Close the stream and release any resources.
     */
    public function close(): void;

    /**
     * Check if the stream is readable.
     *
     * @return bool True if readable, false otherwise
     */
    public function isReadable(): bool;

    /**
     * Check if the stream is writable.
     *
     * @return bool True if writable, false otherwise
     */
    public function isWritable(): bool;

    /**
     * Check if the stream is seekable.
     *
     * @return bool True if seekable, false otherwise
     */
    public function isSeekable(): bool;
}
