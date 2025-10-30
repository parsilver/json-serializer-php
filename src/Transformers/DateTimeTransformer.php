<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Transformers;

use DateTimeImmutable;
use DateTimeInterface;
use Farzai\JsonSerializer\Contracts\TransformerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\Exceptions\TypeException;

/**
 * Transformer for DateTime/DateTimeImmutable values.
 *
 * Supports custom date format configuration via options.
 */
class DateTimeTransformer implements TransformerInterface
{
    #[\Override]
    public function serialize(mixed $value, SerializationContext $context, array $options = []): string
    {
        if (! $value instanceof DateTimeInterface) {
            throw new TypeException('DateTimeTransformer requires a DateTimeInterface instance');
        }

        $format = $options['format'] ?? DateTimeInterface::ATOM;

        if (! is_string($format)) {
            $format = DateTimeInterface::ATOM;
        }

        return $value->format($format);
    }

    #[\Override]
    public function deserialize(mixed $value, array $options = []): DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (! is_string($value)) {
            throw new TypeException('DateTimeTransformer requires a string or DateTimeInterface for deserialization');
        }

        $format = $options['format'] ?? null;
        $useImmutable = $options['immutable'] ?? true;

        // Try to parse with specific format if provided
        if ($format !== null && is_string($format)) {
            if ($useImmutable) {
                $dateTime = DateTimeImmutable::createFromFormat($format, $value);
            } else {
                $dateTime = \DateTime::createFromFormat($format, $value);
            }

            if ($dateTime !== false) {
                return $dateTime;
            }
        }

        // Fallback to automatic parsing
        try {
            if ($useImmutable) {
                return new DateTimeImmutable($value);
            } else {
                return new \DateTime($value);
            }
        } catch (\Exception $e) {
            throw new TypeException("Failed to parse datetime string: {$value}", 0, $e);
        }
    }
}
