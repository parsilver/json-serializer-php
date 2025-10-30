<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\StreamingDeserializer;
use Farzai\JsonSerializer\Exceptions\DeserializationException;
use Iterator;

/**
 * Lazy-loading iterator for large JSON arrays that deserializes objects on-demand.
 *
 * This iterator reads from a JSON array stream and deserializes each element
 * only when it's accessed, minimizing memory usage for large datasets.
 *
 * Usage:
 * ```php
 * $stream = new FileStream('users.json'); // Contains: [{"name":"John"}, {"name":"Jane"}, ...]
 * $iterator = new LazyJsonIterator($stream, User::class);
 *
 * foreach ($iterator as $user) {
 *     // Each User object is created on-demand
 *     echo $user->name;
 * }
 * ```
 *
 * @template T
 *
 * @implements Iterator<int, T>
 */
class LazyJsonIterator implements Iterator
{
    /**
     * The streaming deserializer
     */
    private readonly StreamingDeserializer $streamingDeserializer;

    /**
     * The deserializer engine for object hydration
     */
    private readonly DeserializerEngine $deserializer;

    /**
     * Current position in the array
     */
    private int $position = 0;

    /**
     * Current element
     *
     * @var T|null
     */
    private mixed $current = null;

    /**
     * Whether the iterator is valid
     */
    private bool $valid = false;

    /**
     * Whether we've started iterating
     */
    private bool $started = false;

    /**
     * The array iterator from streaming deserializer
     *
     * @var \Generator<mixed>|null
     */
    private ?\Generator $arrayIterator = null;

    /**
     * Create a new lazy JSON iterator.
     *
     * @param  StreamInterface  $stream  The stream to read from (must contain a JSON array)
     * @param  class-string<T>|null  $className  Optional class name to deserialize elements to
     * @param  DeserializerEngine|null  $deserializer  Optional custom deserializer
     */
    public function __construct(
        StreamInterface $stream,
        private readonly ?string $className = null,
        ?DeserializerEngine $deserializer = null
    ) {
        $this->streamingDeserializer = new StreamingDeserializer($stream);
        $this->deserializer = $deserializer ?? new DeserializerEngine;
    }

    /**
     * Get the current element.
     *
     * @return T
     */
    #[\Override]
    public function current(): mixed
    {
        // @phpstan-ignore-next-line return.type
        return $this->current;
    }

    /**
     * Get the current position.
     */
    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Move to the next element.
     */
    #[\Override]
    public function next(): void
    {
        if (! $this->started) {
            return;
        }

        $this->position++;
        $this->fetchNext();
    }

    /**
     * Rewind the iterator to the first element.
     *
     * Note: This will throw an exception as streams cannot be rewound.
     *
     * @throws DeserializationException
     */
    #[\Override]
    public function rewind(): void
    {
        if ($this->started) {
            throw new DeserializationException('Cannot rewind a streaming iterator. Streams can only be read once.');
        }

        $this->started = true;
        $this->position = 0;

        // Expect START_ARRAY token
        $token = $this->streamingDeserializer->getCurrentToken();
        if ($token !== JsonToken::START_ARRAY) {
            throw new DeserializationException('Expected JSON array, got '.($token->name ?? 'null'));
        }

        // Start the array iterator
        $this->arrayIterator = $this->streamingDeserializer->iterateArray();

        $this->fetchNext();
    }

    /**
     * Check if the current position is valid.
     */
    #[\Override]
    public function valid(): bool
    {
        return $this->valid;
    }

    /**
     * Fetch the next element from the stream.
     */
    private function fetchNext(): void
    {
        if ($this->arrayIterator === null || ! $this->arrayIterator->valid()) {
            $this->valid = false;
            $this->current = null;

            return;
        }

        try {
            $data = $this->arrayIterator->current();
            $this->arrayIterator->next();

            if ($data === null) {
                $this->valid = false;
                $this->current = null;

                return;
            }

            // Deserialize to class if specified
            if ($this->className !== null) {
                $json = json_encode($data);
                if ($json === false) {
                    throw new DeserializationException('Failed to encode data for deserialization');
                }

                /** @phpstan-ignore argument.type, assign.propertyType, argument.templateType */
                $this->current = $this->deserializer->deserializeToClass($json, $this->className);
            } else {
                $this->current = $data;
            }

            $this->valid = true;
        } catch (\Exception $e) {
            $this->valid = false;
            $this->current = null;
            throw new DeserializationException(
                message: 'Failed to fetch next element: '.$e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Transform each element using a callback.
     *
     * @param  callable(T): mixed  $callback  The transformation function
     * @return \Generator Generator with transformed elements
     */
    public function map(callable $callback): \Generator
    {
        foreach ($this as $key => $value) {
            yield $key => $callback($value);
        }
    }

    /**
     * Filter elements using a predicate.
     *
     * @param  callable(T): bool  $callback  The filter predicate
     * @return \Generator Generator with filtered elements
     */
    public function filter(callable $callback): \Generator
    {
        foreach ($this as $key => $value) {
            if ($callback($value)) {
                yield $key => $value;
            }
        }
    }

    /**
     * Take only the first n elements.
     *
     * @param  int  $count  Number of elements to take
     * @return \Generator Generator limited to n elements
     */
    public function take(int $count): \Generator
    {
        $taken = 0;
        foreach ($this as $key => $value) {
            if ($taken >= $count) {
                break;
            }
            yield $key => $value;
            $taken++;
        }
    }

    /**
     * Skip the first n elements.
     *
     * @param  int  $count  Number of elements to skip
     * @return \Generator Generator starting after n elements
     */
    public function skip(int $count): \Generator
    {
        $skipped = 0;
        foreach ($this as $key => $value) {
            if ($skipped < $count) {
                $skipped++;
                continue;
            }
            yield $key => $value;
        }
    }

    /**
     * Process each element with a callback without transforming.
     *
     * @param  callable(T): void  $callback  The callback to execute for each element
     */
    public function each(callable $callback): void
    {
        foreach ($this as $item) {
            $callback($item);
        }
    }
}
