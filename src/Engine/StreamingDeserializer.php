<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Engine;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Exceptions\DeserializationException;
use Farzai\JsonSerializer\Stream\JsonPullParser;
use Farzai\JsonSerializer\Stream\JsonToken;
use Generator;

/**
 * Streaming deserializer that processes JSON incrementally without loading everything into memory.
 *
 * This deserializer uses a pull parser to read JSON tokens one at a time,
 * enabling memory-efficient processing of large JSON documents.
 *
 * Usage:
 * ```php
 * $stream = new FileStream('large.json');
 * $deserializer = new StreamingDeserializer($stream);
 *
 * // Iterate over top-level array items
 * foreach ($deserializer->iterateArray() as $item) {
 *     // Process each item without loading entire array
 * }
 * ```
 */
class StreamingDeserializer
{
    /**
     * Current token iterator
     *
     * @var Generator<array{JsonToken, mixed}>
     */
    private Generator $tokens;

    /**
     * Current token
     */
    private ?JsonToken $currentToken = null;

    /**
     * Current value
     */
    private mixed $currentValue = null;

    /**
     * Whether we have a current token
     */
    private bool $hasToken = false;

    /**
     * Create a new streaming deserializer.
     *
     * @param  StreamInterface  $stream  The stream to read from
     */
    public function __construct(StreamInterface $stream)
    {
        $parser = new JsonPullParser($stream);
        $this->tokens = $parser->parse();
        $this->advance();
    }

    /**
     * Iterate over a JSON array, yielding each element.
     *
     * This method expects the current position to be at the start of a JSON array.
     * It will yield each array element as a PHP value.
     *
     * @return Generator<mixed> Generator that yields array elements
     *
     * @throws DeserializationException If current position is not an array
     */
    public function iterateArray(): Generator
    {
        if ($this->currentToken !== JsonToken::START_ARRAY) {
            throw new DeserializationException('Expected array start, got '.$this->currentToken?->name);
        }

        $this->advance(); // Skip START_ARRAY

        while ($this->hasToken && $this->currentToken !== JsonToken::END_ARRAY) {
            yield $this->readValue();
        }

        if ($this->currentToken === JsonToken::END_ARRAY) {
            $this->advance(); // Skip END_ARRAY
        }
    }

    /**
     * Iterate over a JSON object, yielding key-value pairs.
     *
     * This method expects the current position to be at the start of a JSON object.
     * It will yield each field as [key, value] pairs.
     *
     * @return Generator<array{string, mixed}> Generator that yields [key, value] pairs
     *
     * @throws DeserializationException If current position is not an object
     */
    public function iterateObject(): Generator
    {
        if ($this->currentToken !== JsonToken::START_OBJECT) {
            throw new DeserializationException('Expected object start, got '.$this->currentToken?->name);
        }

        $this->advance(); // Skip START_OBJECT

        while ($this->hasToken && $this->currentToken !== JsonToken::END_OBJECT) {
            if ($this->currentToken !== JsonToken::FIELD_NAME) {
                throw new DeserializationException('Expected field name, got '.$this->currentToken?->name);
            }

            /** @var string */
            $key = $this->currentValue;
            $this->advance(); // Move to value

            $value = $this->readValue();

            yield [$key, $value];
        }

        if ($this->currentToken === JsonToken::END_OBJECT) {
            $this->advance(); // Skip END_OBJECT
        }
    }

    /**
     * Read a complete value from current position.
     *
     * @return mixed The parsed value
     *
     * @throws DeserializationException
     */
    public function readValue(): mixed
    {
        if (! $this->hasToken) {
            throw new DeserializationException('Unexpected end of input');
        }

        return match ($this->currentToken) {
            JsonToken::START_OBJECT => $this->readObject(),
            JsonToken::START_ARRAY => $this->readArray(),
            JsonToken::STRING => $this->readScalar(),
            JsonToken::NUMBER => $this->readScalar(),
            JsonToken::BOOLEAN => $this->readScalar(),
            JsonToken::NULL => $this->readScalar(),
            default => throw new DeserializationException('Unexpected token: '.$this->currentToken?->name)
        };
    }

    /**
     * Read a scalar value (string, number, boolean, null).
     */
    private function readScalar(): mixed
    {
        $value = $this->currentValue;
        $this->advance();

        return $value;
    }

    /**
     * Read a complete object as an associative array.
     *
     * @return array<string, mixed>
     *
     * @throws DeserializationException
     */
    private function readObject(): array
    {
        $result = [];

        foreach ($this->iterateObject() as $pair) {
            [$key, $value] = $pair;
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Read a complete array.
     *
     * @return array<mixed>
     *
     * @throws DeserializationException
     */
    private function readArray(): array
    {
        $result = [];

        foreach ($this->iterateArray() as $value) {
            $result[] = $value;
        }

        return $result;
    }

    /**
     * Advance to the next token.
     */
    private function advance(): void
    {
        if (! $this->tokens->valid()) {
            $this->hasToken = false;
            $this->currentToken = null;
            $this->currentValue = null;

            return;
        }

        [$this->currentToken, $this->currentValue] = $this->tokens->current();
        $this->tokens->next();
        $this->hasToken = true;
    }

    /**
     * Get the current token.
     */
    public function getCurrentToken(): ?JsonToken
    {
        return $this->currentToken;
    }
}
