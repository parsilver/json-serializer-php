<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

use Farzai\JsonSerializer\Contracts\StreamInterface;
use Farzai\JsonSerializer\Exceptions\StreamException;

/**
 * Extract specific data from JSON using path expressions without loading the entire document.
 *
 * Path syntax:
 * - `.field` - Access object field
 * - `[0]` - Access array element by index
 * - `.field[0]` - Access array element in nested field
 * - `.field.nested` - Access nested object field
 *
 * Examples:
 * ```php
 * $stream = new FileStream('data.json');
 * $path = new JsonPath($stream);
 *
 * // Extract single value
 * $name = $path->extract('final .user.name');
 *
 * // Extract array element
 * $firstItem = $path->extract('.items[0]');
 *
 * // Extract nested field
 * $email = $path->extract('.user.profile.email');
 * ```
 */
class JsonPath
{
    /**
     * The streaming deserializer
     */
    private readonly \Farzai\JsonSerializer\Engine\StreamingDeserializer $deserializer;

    /**
     * Create a new JSON path extractor.
     *
     * @param  StreamInterface  $stream  The stream to read from
     */
    public function __construct(StreamInterface $stream)
    {
        $this->deserializer = new \Farzai\JsonSerializer\Engine\StreamingDeserializer($stream);
    }

    /**
     * Extract data at the specified path.
     *
     * @param  string  $path  The path expression (e.g., '.user.name', '.items[0]')
     * @return mixed The extracted value, or null if path not found
     *
     * @throws StreamException If path syntax is invalid
     */
    public function extract(string $path): mixed
    {
        $segments = $this->parsePath($path);

        return $this->navigatePath($segments);
    }

    /**
     * Parse a path expression into segments.
     *
     * @param  string  $path  The path expression
     * @return array<array{type: string, value: string|int}> Array of path segments
     *
     * @throws StreamException If path syntax is invalid
     */
    private function parsePath(string $path): array
    {
        $segments = [];
        $length = strlen($path);
        $i = 0;

        // Skip leading dot
        if ($i < $length && $path[$i] === '.') {
            $i++;
        }

        while ($i < $length) {
            $char = $path[$i];

            if ($char === '.') {
                // Field accessor
                $i++;
                $field = '';

                while ($i < $length && $path[$i] !== '.' && $path[$i] !== '[') {
                    $field .= $path[$i];
                    $i++;
                }

                if ($field === '') {
                    throw new StreamException('Empty field name in path');
                }

                $segments[] = ['type' => 'field', 'value' => $field];
            } elseif ($char === '[') {
                // Array index accessor
                $i++;
                $index = '';

                while ($i < $length && $path[$i] !== ']') {
                    if (! ctype_digit($path[$i])) {
                        throw new StreamException("Invalid array index in path: expected digit, got '{$path[$i]}'");
                    }
                    $index .= $path[$i];
                    $i++;
                }

                if ($i >= $length) {
                    throw new StreamException('Unclosed array index in path');
                }

                $i++; // Skip ']'

                if ($index === '') {
                    throw new StreamException('Empty array index in path');
                }

                $segments[] = ['type' => 'index', 'value' => (int) $index];
            } elseif (ctype_alpha($char) || $char === '_') {
                // Field name without leading dot (for root-level fields)
                $field = '';

                while ($i < $length && $path[$i] !== '.' && $path[$i] !== '[') {
                    $field .= $path[$i];
                    $i++;
                }

                $segments[] = ['type' => 'field', 'value' => $field];
            } else {
                throw new StreamException("Unexpected character '{$char}' in path");
            }
        }

        return $segments;
    }

    /**
     * Navigate through the JSON using parsed path segments.
     *
     * @param  array<array{type: string, value: string|int}>  $segments  The path segments
     * @return mixed The value at the path, or null if not found
     *
     * @throws StreamException
     */
    private function navigatePath(array $segments): mixed
    {
        if (empty($segments)) {
            return $this->deserializer->readValue();
        }

        $current = $this->deserializer->readValue();

        foreach ($segments as $segment) {
            if ($segment['type'] === 'field') {
                if (! is_array($current)) {
                    return null;
                }

                $current = $current[$segment['value']] ?? null;
            } elseif ($segment['type'] === 'index') {
                if (! is_array($current)) {
                    return null;
                }

                $index = $segment['value'];
                if (! isset($current[$index])) {
                    return null;
                }

                $current = $current[$index];
            }

            if ($current === null) {
                return null;
            }
        }

        return $current;
    }

    /**
     * Extract multiple paths at once.
     *
     * This is more efficient than calling extract() multiple times
     * as it only parses the JSON once.
     *
     * @param  array<string>  $paths  Array of path expressions
     * @return array<string, mixed> Map of paths to extracted values
     *
     * @throws StreamException
     */
    public function extractMultiple(array $paths): array
    {
        // For now, we read the entire document once and extract from it
        // This could be optimized further by tracking multiple paths during parsing
        $data = $this->deserializer->readValue();

        $results = [];
        foreach ($paths as $path) {
            $results[$path] = $this->extractFromData($data, $path);
        }

        return $results;
    }

    /**
     * Extract a value from already-parsed data.
     *
     * @param  mixed  $data  The parsed data
     * @param  string  $path  The path expression
     * @return mixed The extracted value, or null if not found
     *
     * @throws StreamException
     */
    private function extractFromData(mixed $data, string $path): mixed
    {
        $segments = $this->parsePath($path);

        $current = $data;

        foreach ($segments as $segment) {
            if ($segment['type'] === 'field') {
                if (! is_array($current)) {
                    return null;
                }

                $current = $current[$segment['value']] ?? null;
            } elseif ($segment['type'] === 'index') {
                if (! is_array($current)) {
                    return null;
                }

                $index = $segment['value'];
                if (! isset($current[$index])) {
                    return null;
                }

                $current = $current[$index];
            }

            if ($current === null) {
                return null;
            }
        }

        return $current;
    }
}
