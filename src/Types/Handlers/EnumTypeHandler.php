<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Types\Handlers;

use BackedEnum;
use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use UnitEnum;

/**
 * Handler for PHP 8.1+ Enum types.
 *
 * Supports both BackedEnum (with int/string values) and UnitEnum (no backing value).
 * BackedEnum values are serialized to their backing value.
 * UnitEnum values are serialized to their case name as a string.
 */
class EnumTypeHandler implements TypeHandlerInterface
{
    #[\Override]
    public function supports(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    #[\Override]
    public function serialize(mixed $value, SerializationContext $context): string
    {
        if (! $value instanceof UnitEnum) {
            return 'null';
        }

        // BackedEnum: return the backing value directly
        if ($value instanceof BackedEnum) {
            $backingValue = $value->value;

            // Serialize as string or int
            if (is_string($backingValue)) {
                $encoded = json_encode($backingValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return $encoded !== false ? $encoded : '""';
            }

            return (string) $backingValue;
        }

        // UnitEnum: return the case name as a string
        $encoded = json_encode($value->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '""';
    }

    #[\Override]
    public function getPriority(): int
    {
        return 80; // High priority, before object handler
    }
}
