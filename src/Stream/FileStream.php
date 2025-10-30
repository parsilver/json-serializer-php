<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Contracts\StreamWriterInterface;
use Farzai\JsonSerializer\Exceptions\StreamException;

/**
 * File-based stream implementation.
 *
 * This stream reads from and writes to actual files on the filesystem.
 * It's suitable for large data that doesn't fit in memory, as it only
 * keeps a small buffer in memory at any time.
 */
class FileStream implements StreamInterface, StreamWriterInterface
{
    /** @var resource|null */
    private $handle = null;

    private readonly string $mode;

    /**
     * Create a new file stream.
     *
     * @param  string  $path  The file path
     * @param  string  $mode  The file open mode (e.g., 'r', 'w', 'a', 'r+', 'w+')
     *
     * @throws StreamException If the file cannot be opened
     */
    public function __construct(
        private readonly string $path,
        string $mode = 'r+'
    ) {
        $this->mode = $mode;
        $this->open();
    }

    #[\Override]
    public function write(string $data): int
    {
        $handle = $this->ensureOpen();

        if (! $this->isWritable()) {
            throw new StreamException("Stream is not writable (mode: {$this->mode})");
        }

        $written = @fwrite($handle, $data);

        if ($written === false) {
            throw new StreamException("Failed to write to file: {$this->path}");
        }

        return $written;
    }

    #[\Override]
    public function read(int $length): string
    {
        $handle = $this->ensureOpen();

        if (! $this->isReadable()) {
            throw new StreamException("Stream is not readable (mode: {$this->mode})");
        }

        if ($length < 1) {
            return '';
        }

        $data = @fread($handle, $length);

        if ($data === false) {
            throw new StreamException("Failed to read from file: {$this->path}");
        }

        return $data;
    }

    #[\Override]
    public function eof(): bool
    {
        $handle = $this->ensureOpen();

        return feof($handle);
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        $handle = $this->ensureOpen();

        if (! $this->isSeekable()) {
            return false;
        }

        return @fseek($handle, $offset, $whence) === 0;
    }

    #[\Override]
    public function flush(): bool
    {
        $handle = $this->ensureOpen();

        return @fflush($handle);
    }

    #[\Override]
    public function close(): void
    {
        if ($this->handle !== null) {
            @fclose($this->handle);
            $this->handle = null;
        }
    }

    #[\Override]
    public function isReadable(): bool
    {
        if ($this->handle === null) {
            return false;
        }

        return str_contains($this->mode, 'r') || str_contains($this->mode, '+');
    }

    #[\Override]
    public function isWritable(): bool
    {
        if ($this->handle === null) {
            return false;
        }

        return str_contains($this->mode, 'w')
            || str_contains($this->mode, 'a')
            || str_contains($this->mode, 'x')
            || str_contains($this->mode, 'c')
            || str_contains($this->mode, '+');
    }

    #[\Override]
    public function isSeekable(): bool
    {
        if ($this->handle === null) {
            return false;
        }

        $meta = stream_get_meta_data($this->handle);

        return $meta['seekable'];
    }

    /**
     * Open the file handle.
     *
     * @throws StreamException If the file cannot be opened
     */
    private function open(): void
    {
        $handle = @fopen($this->path, $this->mode);

        if ($handle === false) {
            throw new StreamException("Failed to open file: {$this->path} with mode: {$this->mode}");
        }

        $this->handle = $handle;
    }

    /**
     * Ensure the stream is open.
     *
     * @return resource
     *
     * @throws StreamException If the stream is not open
     */
    private function ensureOpen()
    {
        if ($this->handle === null) {
            throw new StreamException('Stream is not open');
        }

        return $this->handle;
    }

    public function __destruct()
    {
        $this->close();
    }
}
