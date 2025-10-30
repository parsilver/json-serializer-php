<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Transformers;

use BackedEnum;
use Farzai\JsonSerializer\Contracts\TransformerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\Exceptions\TypeException;
use UnitEnum;

/**
 * Transformer for PHP 8.1+ Enum values.
 *
 * Supports both BackedEnum and UnitEnum.
 */
class EnumTransformer implements TransformerInterface
{
    #[\Override]
    public function serialize(mixed $value, SerializationContext $context, array $options = []): string|int
    {
        if (! $value instanceof UnitEnum) {
            throw new TypeException('EnumTransformer requires a UnitEnum instance');
        }

        // BackedEnum: return the backing value
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        // UnitEnum: return the name
        return $value->name;
    }

    #[\Override]
    public function deserialize(mixed $value, array $options = []): UnitEnum
    {
        if ($value instanceof UnitEnum) {
            return $value;
        }

        $enumClass = $options['class'] ?? null;

        if (! is_string($enumClass) || ! enum_exists($enumClass)) {
            throw new TypeException('EnumTransformer requires a valid enum class in options');
        }

        // Try BackedEnum first
        if (is_subclass_of($enumClass, BackedEnum::class)) {
            if (! is_int($value) && ! is_string($value)) {
                throw new TypeException("Invalid value type for backed enum {$enumClass}");
            }

            $enum = $enumClass::tryFrom($value);
            if ($enum !== null) {
                return $enum;
            }

            $valueStr = is_string($value) ? $value : (string) $value;
            throw new TypeException("Invalid value for backed enum {$enumClass}: {$valueStr}");
        }

        // Try UnitEnum by name
        if (enum_exists($enumClass) && is_string($value)) {
            /** @var class-string<UnitEnum> $enumClass */
            $cases = $enumClass::cases();
            foreach ($cases as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }

            throw new TypeException("Invalid name for unit enum {$enumClass}: {$value}");
        }

        throw new TypeException("Cannot deserialize value to enum {$enumClass}");
    }
}
