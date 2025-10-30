<?php

use Farzai\JsonSerializer\Engine\DeserializationContext;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Exceptions\DeserializationException;
use Farzai\JsonSerializer\Types\TypeCoercionMode;

describe('Type Coercion Integration Tests', function () {
    it('can use SAFE mode to coerce numeric strings', function () {
        class SafeData
        {
            public string $text;

            public int $number;

            public float $decimal;

            public bool $flag;
        }

        $json = '{"text":"hello","number":"42","decimal":"3.14","flag":"true"}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::SAFE);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, SafeData::class, $context);

        expect($data)->toBeInstanceOf(SafeData::class)
            ->and($data->text)->toBe('hello')
            ->and($data->number)->toBe(42)
            ->and($data->decimal)->toBe(3.14)
            ->and($data->flag)->toBeTrue();
    });

    it('can use SAFE mode with 1/0 for booleans', function () {
        class BoolData
        {
            public bool $trueFlag;

            public bool $falseFlag;
        }

        $json = '{"trueFlag":1,"falseFlag":0}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::SAFE);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, BoolData::class, $context);

        expect($data->trueFlag)->toBeTrue()
            ->and($data->falseFlag)->toBeFalse();
    });

    it('can use LENIENT mode for aggressive coercion', function () {
        class LenientData
        {
            public string $text;

            public int $number;

            public float $decimal;

            public bool $flag;
        }

        $json = '{"text":123,"number":"abc123","decimal":true,"flag":"yes"}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::LENIENT);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, LenientData::class, $context);

        expect($data)->toBeInstanceOf(LenientData::class)
            ->and($data->text)->toBe('123')
            ->and($data->number)->toBe(123)
            ->and($data->decimal)->toBe(1.0)
            ->and($data->flag)->toBeTrue();
    });

    it('can use LENIENT mode with truthy/falsy values', function () {
        class TruthyData
        {
            public bool $trueFlag;

            public bool $falseFlag;

            public bool $emptyString;
        }

        $json = '{"trueFlag":"yes","falseFlag":"no","emptyString":""}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::LENIENT);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, TruthyData::class, $context);

        expect($data->trueFlag)->toBeTrue()
            ->and($data->falseFlag)->toBeFalse()
            ->and($data->emptyString)->toBeFalse();
    });

    it('throws exception in STRICT mode on type mismatch', function () {
        class StrictData
        {
            public int $number;
        }

        $json = '{"number":"42"}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::STRICT);
        $deserializer = new DeserializerEngine;

        expect(fn () => $deserializer->deserializeToClass($json, StrictData::class, $context))
            ->toThrow(DeserializationException::class);
    });

    it('allows int to float widening in STRICT mode', function () {
        class FloatData
        {
            public float $value;
        }

        $json = '{"value":42}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::STRICT);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, FloatData::class, $context);

        expect($data->value)->toBe(42.0);
    });

    it('uses SAFE mode by default', function () {
        class DefaultData
        {
            public int $number;
        }

        $json = '{"number":"42"}';

        $deserializer = new DeserializerEngine;
        $data = $deserializer->deserializeToClass($json, DefaultData::class);

        expect($data->number)->toBe(42);
    });

    it('provides detailed error messages with property paths', function () {
        class NestedData
        {
            public int $id;
        }

        $json = '{"id":"not-a-number"}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::SAFE);
        $deserializer = new DeserializerEngine;

        try {
            $deserializer->deserializeToClass($json, NestedData::class, $context);
            expect(false)->toBeTrue(); // Should not reach here
        } catch (DeserializationException $e) {
            expect($e->getPropertyPath())->toBe('id')
                ->and($e->getExpectedType())->toBe('int')
                ->and($e->getMessage())->toContain('id');
        }
    });

    it('can coerce float strings in SAFE mode', function () {
        class FloatStringData
        {
            public float $value;
        }

        $json = '{"value":"3.14159"}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::SAFE);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, FloatStringData::class, $context);

        expect($data->value)->toBe(3.14159);
    });

    it('handles whole number floats in SAFE mode', function () {
        class IntFromFloat
        {
            public int $value;
        }

        $json = '{"value":42.0}';

        $context = new DeserializationContext(typeCoercionMode: TypeCoercionMode::SAFE);
        $deserializer = new DeserializerEngine;

        $data = $deserializer->deserializeToClass($json, IntFromFloat::class, $context);

        expect($data->value)->toBe(42);
    });
});
