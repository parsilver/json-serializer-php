<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Buffer;

use Farzai\JsonSerializer\Contracts\StreamWriterInterface;

/**
 * Optimized write buffer for efficient string concatenation.
 *
 * This buffer accumulates writes in memory and flushes to the underlying
 * stream infinal  chunks, reducing the number of write operations and improving
 * performance. It's particularly useful for large JSON documents.
 */
class WriteBuffer implements StreamWriterInterface
{
    private string $buffer = '';

    /**
     * Create a new write buffer.
     *
     * @param  StreamWriterInterface  $stream  The underlying stream to write to
     * @param  int  $chunkSize  The size of each chunk to flush (default: 8KB)
     */
    public function __construct(
        private readonly StreamWriterInterface $stream,
        private readonly int $chunkSize = 8192
    ) {}

    #[\Override]
    public function write(string $data): int
    {
        $this->buffer .= $data;
        $length = strlen($data);

        // Auto-flush if buffer exceeds chunk size
        if (strlen($this->buffer) >= $this->chunkSize) {
            $this->flush();
        }

        return $length;
    }

    #[\Override]
    public function flush(): bool
    {
        if ($this->buffer === '') {
            return true;
        }

        $written = $this->stream->write($this->buffer);
        $this->buffer = '';

        // Also flush the underlying stream
        return $written > 0 && $this->stream->flush();
    }

    #[\Override]
    public function close(): void
    {
        $this->flush();
        $this->stream->close();
    }

    #[\Override]
    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function __destruct()
    {
        $this->flush();
    }
}
