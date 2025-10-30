<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Exceptions\StreamException;
use Generator;

/**
 * Event-driven JSON pull parser that reads tokens incrementally.
 *
 * This parser reads JSON data from a stream one token at a time,
 * allowing for memory-efficient processing of large JSON documents
 * without loading the entire structure into memory.
 *
 * Usage:
 * ```php
 * $stream = new FileStream('large.json');
 * $parser = new JsonPullParser($stream);
 *
 * foreach ($parser->parse() as $event) {
 *     [$token, $value] = $event;
 *     match ($token) {
 *         JsonToken::START_OBJECT => //final  handle object start
 *         JsonToken::FIELD_NAME => // handle field name
 *         JsonToken::STRING => // handle string value
 *         // ... handle other tokens
 *     };
 * }
 * ```
 */
class JsonPullParser
{
    /**
     * Buffer size for reading from stream
     */
    private const BUFFER_SIZE = 8192;

    /**
     * Current buffer being processed
     */
    private string $buffer = '';

    /**
     * Current position in buffer
     */
    private int $position = 0;

    /**
     * Current line number (for error messages)
     */
    private int $line = 1;

    /**
     * Current column number (for error messages)
     */
    private int $column = 0;

    /**
     * Whether we've reached end of stream
     */
    private bool $endOfStream = false;

    /**
     * Create a new JSON pull parser.
     *
     * @param  StreamInterface  $stream  The stream to read from
     */
    public function __construct(
        private readonly StreamInterface $stream
    ) {}

    /**
     * Parse the JSON stream and yield tokens with their values.
     *
     * @return Generator<array{JsonToken, mixed}> Generator that yields [token, value] pairs
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException If invalid JSON is encountered
     */
    public function parse(): Generator
    {
        $this->fillBuffer();
        $this->skipWhitespace();

        while (! $this->isAtEnd()) {
            yield from $this->parseValue();
            $this->skipWhitespace();
        }

        yield [JsonToken::END_DOCUMENT, null];
    }

    /**
     * Parse a single JSON value and yield appropriate tokens.
     *
     * @return Generator<array{JsonToken, mixed}>
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseValue(): Generator
    {
        $char = $this->peek();

        yield from match ($char) {
            '{' => $this->parseObject(),
            '[' => $this->parseArray(),
            '"' => [[JsonToken::STRING, $this->parseString()]],
            't', 'f' => [[JsonToken::BOOLEAN, $this->parseBoolean()]],
            'n' => [[JsonToken::NULL, $this->parseNull()]],
            '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' => [[JsonToken::NUMBER, $this->parseNumber()]],
            default => throw new StreamException("Unexpected character '{$char}' at line {$this->line}, column {$this->column}")
        };
    }

    /**
     * Parse a JSON object and yield tokens.
     *
     * @return Generator<array{JsonToken, mixed}>
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseObject(): Generator
    {
        $this->consume('{');
        yield [JsonToken::START_OBJECT, null];

        $this->skipWhitespace();

        if ($this->peek() === '}') {
            $this->consume('}');
            yield [JsonToken::END_OBJECT, null];

            return;
        }

        while (! $this->isAtEnd()) {
            // Parse field name
            if ($this->peek() !== '"') {
                throw new StreamException("Expected field name at line {$this->line}, column {$this->column}");
            }

            $fieldName = $this->parseString();
            yield [JsonToken::FIELD_NAME, $fieldName];

            $this->skipWhitespace();
            $this->expect(':');
            $this->skipWhitespace();

            // Parse field value
            yield from $this->parseValue();

            $this->skipWhitespace();

            $next = $this->peek();
            if ($next === '}') {
                $this->consume('}');
                yield [JsonToken::END_OBJECT, null];
                break;
            } elseif ($next === ',') {
                $this->consume(',');
                $this->skipWhitespace();
            } else {
                throw new StreamException("Expected ',' or '}' at line {$this->line}, column {$this->column}");
            }
        }
    }

    /**
     * Parse a JSON array and yield tokens.
     *
     * @return Generator<array{JsonToken, mixed}>
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseArray(): Generator
    {
        $this->consume('[');
        yield [JsonToken::START_ARRAY, null];

        $this->skipWhitespace();

        if ($this->peek() === ']') {
            $this->consume(']');
            yield [JsonToken::END_ARRAY, null];

            return;
        }

        while (! $this->isAtEnd()) {
            yield from $this->parseValue();

            $this->skipWhitespace();

            $next = $this->peek();
            if ($next === ']') {
                $this->consume(']');
                yield [JsonToken::END_ARRAY, null];
                break;
            } elseif ($next === ',') {
                $this->consume(',');
                $this->skipWhitespace();
            } else {
                throw new StreamException("Expected ',' or ']' at line {$this->line}, column {$this->column}");
            }
        }
    }

    /**
     * Parse a JSON string.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseString(): string
    {
        $this->consume('"');
        $result = '';

        while (! $this->isAtEnd()) {
            $char = $this->peek();

            if ($char === '"') {
                $this->consume('"');

                return $result;
            }

            if ($char === '\\') {
                $this->advance();
                $escaped = $this->peek();

                $result .= match ($escaped) {
                    '"', '\\', '/' => $escaped,
                    'b' => "\b",
                    'f' => "\f",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'u' => $this->parseUnicodeEscape(),
                    default => throw new StreamException("Invalid escape sequence '\\{$escaped}' at line {$this->line}")
                };
                $this->advance();
            } else {
                $result .= $char;
                $this->advance();
            }
        }

        throw new StreamException("Unterminated string at line {$this->line}");
    }

    /**
     * Parse a Unicode escape sequence (\uXXXX).
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseUnicodeEscape(): string
    {
        $this->advance(); // Skip 'u'
        $hex = '';

        for ($i = 0; $i < 4; $i++) {
            if ($this->isAtEnd()) {
                throw new StreamException("Incomplete Unicode escape at line {$this->line}");
            }
            $hex .= $this->peek();
            $this->advance();
        }

        $codepoint = (int) hexdec($hex);

        return mb_chr($codepoint, 'UTF-8') ?: '';

    }

    /**
     * Parse a JSON number.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseNumber(): int|float
    {
        $number = '';

        // Optional minus
        if ($this->peek() === '-') {
            $number .= '-';
            $this->advance();
        }

        // Integer part
        if ($this->peek() === '0') {
            $number .= '0';
            $this->advance();
        } else {
            while (! $this->isAtEnd() && ctype_digit($this->peek())) {
                $number .= $this->peek();
                $this->advance();
            }
        }

        // Decimal part
        if (! $this->isAtEnd() && $this->peek() === '.') {
            $number .= '.';
            $this->advance();

            if ($this->isAtEnd() || ! ctype_digit($this->peek())) {
                throw new StreamException("Invalid number format at line {$this->line}");
            }

            while (! $this->isAtEnd() && ctype_digit($this->peek())) {
                $number .= $this->peek();
                $this->advance();
            }
        }

        // Exponent part
        if (! $this->isAtEnd() && in_array($this->peek(), ['e', 'E'], true)) {
            $number .= $this->peek();
            $this->advance();

            if (! $this->isAtEnd() && in_array($this->peek(), ['+', '-'], true)) {
                $number .= $this->peek();
                $this->advance();
            }

            if ($this->isAtEnd() || ! ctype_digit($this->peek())) {
                throw new StreamException("Invalid number format at line {$this->line}");
            }

            while (! $this->isAtEnd() && ctype_digit($this->peek())) {
                $number .= $this->peek();
                $this->advance();
            }
        }

        // Convert to appropriate numeric type
        if (str_contains($number, '.') || str_contains($number, 'e') || str_contains($number, 'E')) {
            return (float) $number;
        }

        return (int) $number;
    }

    /**
     * Parse a JSON boolean.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseBoolean(): bool
    {
        if ($this->peek() === 't') {
            $this->expectSequence('true');

            return true;
        }

        $this->expectSequence('false');

        return false;
    }

    /**
     * Parse a JSON null.
     *
     * @return null
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function parseNull()
    {
        $this->expectSequence('null');

        return null;
    }

    /**
     * Skip whitespace characters.
     */
    private function skipWhitespace(): void
    {
        while (! $this->isAtEnd()) {
            $char = $this->peek();

            if (! in_array($char, [' ', "\t", "\r", "\n"], true)) {
                break;
            }

            if ($char === "\n") {
                $this->line++;
                $this->column = 0;
            }

            $this->advance();
        }
    }

    /**
     * Peek at the current character without advancing.
     */
    private function peek(): string
    {
        if ($this->position >= strlen($this->buffer)) {
            $this->fillBuffer();
        }

        return $this->position < strlen($this->buffer)
            ? $this->buffer[$this->position]
            : '';
    }

    /**
     * Advance to the next character.
     */
    private function advance(): void
    {
        if ($this->position < strlen($this->buffer)) {
            $this->position++;
            $this->column++;
        }

        if ($this->position >= strlen($this->buffer)) {
            $this->fillBuffer();
        }
    }

    /**
     * Consume a specific character, throwing if it doesn't match.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function consume(string $expected): void
    {
        $actual = $this->peek();
        if ($actual !== $expected) {
            throw new StreamException("Expected '{$expected}' but got '{$actual}' at line {$this->line}, column {$this->column}");
        }
        $this->advance();
    }

    /**
     * Expect a specific character at current position.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function expect(string $expected): void
    {
        $this->consume($expected);
    }

    /**
     * Expect a specific sequence of characters.
     *
     * @throws \Farzai\JsonSerializer\Exceptions\StreamException
     */
    private function expectSequence(string $sequence): void
    {
        foreach (str_split($sequence) as $char) {
            $this->consume($char);
        }
    }

    /**
     * Check if we're at the end of the stream.
     */
    private function isAtEnd(): bool
    {
        return $this->endOfStream && $this->position >= strlen($this->buffer);
    }

    /**
     * Fill the buffer with more data from the stream.
     */
    private function fillBuffer(): void
    {
        if ($this->endOfStream) {
            return;
        }

        // Keep unprocessed data
        if ($this->position < strlen($this->buffer)) {
            $this->buffer = substr($this->buffer, $this->position);
            $this->position = 0;
        } else {
            $this->buffer = '';
            $this->position = 0;
        }

        // Read more data
        $chunk = $this->stream->read(self::BUFFER_SIZE);

        if ($chunk === '') {
            $this->endOfStream = true;

            return;
        }

        $this->buffer .= $chunk;
    }
}
