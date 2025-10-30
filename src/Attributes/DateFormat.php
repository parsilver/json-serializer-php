<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Specifies the format for DateTime serialization.
 *
 * @example
 * class Event {
 *     #[DateFormat('Y-m-d H:i:s')]
 *     public DateTime $startTime;
 *
 *     #[DateFormat(DateFormat::ISO8601)]
 *     public DateTime $endTime;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateFormat
{
    public const ISO8601 = \DateTimeInterface::ATOM;

    public const RFC3339 = \DateTimeInterface::RFC3339;

    public const RFC3339_EXTENDED = \DateTimeInterface::RFC3339_EXTENDED;

    /**
     * Create a new DateFormat attribute.
     *
     * @param  string  $format  The date format string (e.g., 'Y-m-d H:i:s', DateFormat::ISO8601)
     */
    public function __construct(
        public readonly string $format
    ) {}
}
