<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Exceptions\StreamException;

/**
 * In-memory string-based stream implementation.
 *
 * Thifinal s stream stores all data in memory as a string buffer.
 * It's suitable for small to medium-sized data where memory
 * usage is not a concern and maximum performance is desired.
 */
class StringStream implements StreamInterface
{
    private string $buffer = '';

    private int $position = 0;

    private bool $closed = false;

    /**
     * Create a new string stream.
     *
     * @param  string  $initialContent  Optional initial content
     */
    public function __construct(string $initialContent = '')
    {
        $this->buffer = $initialContent;
    }

    public function write(string $data): int
    {
        $this->ensureNotClosed();

        if ($this->position === strlen($this->buffer)) {
            // Append to end
            $this->buffer .= $data;
        } else {
            // Insert at current position
            $this->buffer = substr($this->buffer, 0, $this->position)
                .$data
                .substr($this->buffer, $this->position);
        }

        $length = strlen($data);
        $this->position += $length;

        return $length;
    }

    #[\Override]
    public function read(int $length): string
    {
        $this->ensureNotClosed();

        if ($this->eof()) {
            return '';
        }

        $data = substr($this->buffer, $this->position, $length);
        $this->position += strlen($data);

        return $data;
    }

    #[\Override]
    public function eof(): bool
    {
        return $this->position >= strlen($this->buffer);
    }

    public function tell(): int
    {
        $this->ensureNotClosed();

        return $this->position;
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        $this->ensureNotClosed();

        $newPosition = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => strlen($this->buffer) + $offset,
            default => throw new StreamException("Invalid whence value: {$whence}"),
        };

        if ($newPosition < 0 || $newPosition > strlen($this->buffer)) {
            return false;
        }

        $this->position = $newPosition;

        return true;
    }

    public function rewind(): bool
    {
        return $this->seek(0, SEEK_SET);
    }

    public function flush(): bool
    {
        // No-op for string streams as there's no underlying storage
        return true;
    }

    #[\Override]
    public function close(): void
    {
        $this->closed = true;
    }

    #[\Override]
    public function isReadable(): bool
    {
        return ! $this->closed;
    }

    #[\Override]
    public function isWritable(): bool
    {
        return ! $this->closed;
    }

    #[\Override]
    public function isSeekable(): bool
    {
        return ! $this->closed;
    }

    public function getSize(): ?int
    {
        return strlen($this->buffer);
    }

    public function getContents(): string
    {
        $this->ensureNotClosed();

        $contents = substr($this->buffer, $this->position);
        $this->position = strlen($this->buffer);

        return $contents;
    }

    /**
     * Get the entire buffer as a string.
     *
     * Unlike getContents(), this returns the entire buffer
     * regardless of the current position.
     *
     * @return string The complete buffer
     */
    public function toString(): string
    {
        return $this->buffer;
    }

    /**
     * Ensure the stream is not closed.
     *
     * @throws StreamException If the stream is closed
     */
    private function ensureNotClosed(): void
    {
        if ($this->closed) {
            throw new StreamException('Stream is closed');
        }
    }
}
