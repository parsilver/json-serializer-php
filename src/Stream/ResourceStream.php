<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Exceptions\StreamException;

/**
 * PHP resource-based stream wrapper.
 *
 * This stream wraps existing PHP stream resources (like those returned
 * by fopen, popen, etc.) and provides the StreamInterface API.
 */
class ResourceStream implements StreamInterface
{
    /**
     * Create a new resource stream.
     *
     * @param  resource  $resource  The PHP stream resource
     *
     * @throws StreamException If the resource is invalid
     */
    public function __construct(
        private $resource
    ) {
        if (! is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new StreamException('Invalid stream resource provided');
        }
    }

    public function write(string $data): int
    {
        $this->ensureOpen();

        if (! $this->isWritable()) {
            throw new StreamException('Stream is not writable');
        }

        $written = @fwrite($this->resource, $data);

        if ($written === false) {
            throw new StreamException('Failed to write to stream resource');
        }

        return $written;
    }

    #[\Override]
    public function read(int $length): string
    {
        $this->ensureOpen();

        if (! $this->isReadable()) {
            throw new StreamException('Stream is not readable');
        }

        if ($length < 1) {
            return '';
        }

        $data = @fread($this->resource, $length);

        if ($data === false) {
            throw new StreamException('Failed to read from stream resource');
        }

        return $data;
    }

    #[\Override]
    public function eof(): bool
    {
        $this->ensureOpen();

        return feof($this->resource);
    }

    public function tell(): int
    {
        $this->ensureOpen();

        $position = @ftell($this->resource);

        if ($position === false) {
            throw new StreamException('Failed to get position in stream resource');
        }

        return $position;
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->ensureOpen();

        if (! $this->isSeekable()) {
            return false;
        }

        return @fseek($this->resource, $offset, $whence) === 0;
    }

    public function rewind(): bool
    {
        $this->ensureOpen();

        return @rewind($this->resource) !== false;
    }

    public function flush(): bool
    {
        $this->ensureOpen();

        return @fflush($this->resource);
    }

    #[\Override]
    public function close(): void
    {
        if (is_resource($this->resource)) {
            @fclose($this->resource);
        }
    }

    #[\Override]
    public function isReadable(): bool
    {
        if (! is_resource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return str_contains($mode, 'r') || str_contains($mode, '+');
    }

    #[\Override]
    public function isWritable(): bool
    {
        if (! is_resource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return str_contains($mode, 'w')
            || str_contains($mode, 'a')
            || str_contains($mode, 'x')
            || str_contains($mode, 'c')
            || str_contains($mode, '+');
    }

    #[\Override]
    public function isSeekable(): bool
    {
        if (! is_resource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'];
    }

    public function getSize(): ?int
    {
        if (! is_resource($this->resource)) {
            return null;
        }

        $stats = @fstat($this->resource);

        return $stats['size'] ?? null;
    }

    public function getContents(): string
    {
        $this->ensureOpen();

        if (! $this->isReadable()) {
            throw new StreamException('Stream is not readable');
        }

        $contents = @stream_get_contents($this->resource);

        if ($contents === false) {
            throw new StreamException('Failed to read contents from stream resource');
        }

        return $contents;
    }

    /**
     * Ensure the stream is open.
     *
     * @throws StreamException If the resource is not valid
     */
    private function ensureOpen(): void
    {
        if (! is_resource($this->resource)) {
            throw new StreamException('Stream resource is not open');
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
