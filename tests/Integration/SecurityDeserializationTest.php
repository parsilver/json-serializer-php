<?php

use Farzai\JsonSerializer\Engine\DeserializationContext;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Exceptions\SecurityException;
use Farzai\JsonSerializer\Security\SecurityConfig;

describe('Security in Deserialization', function () {
    describe('Class Whitelist Enforcement', function () {
        it('allows whitelisted classes during deserialization', function () {
            $config = new SecurityConfig(
                allowedClasses: [\DateTime::class]
            );
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            $json = json_encode(['date' => '2024-01-15', 'timezone' => 'UTC']);

            // This should work because DateTime is whitelisted
            expect(fn () => $engine->deserializeToClass($json, \DateTime::class, $context))
                ->not->toThrow(SecurityException::class);
        });

        it('blocks non-whitelisted classes during deserialization', function () {
            $config = new SecurityConfig(
                allowedClasses: [\DateTime::class]
            );
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public string $name;
            };

            $json = json_encode(['name' => 'test']);

            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->toThrow(SecurityException::class, 'not in the allowed classes whitelist');
        });
    });

    describe('Maximum String Length', function () {
        it('allows strings within limit', function () {
            $config = new SecurityConfig(maxStringLength: 100);
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public string $text;
            };

            $json = json_encode(['text' => str_repeat('a', 50)]);

            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->not->toThrow(SecurityException::class);
        });

        it('throws exception when string exceeds limit', function () {
            $config = new SecurityConfig(maxStringLength: 50);
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public string $text;
            };

            $json = json_encode(['text' => str_repeat('a', 100)]);

            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->toThrow(SecurityException::class, 'Maximum string length');
        });
    });

    describe('Maximum Array Size', function () {
        it('allows arrays within limit', function () {
            $config = new SecurityConfig(maxArraySize: 100);
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public array $items;
            };

            $json = json_encode(['items' => range(1, 50)]);

            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->not->toThrow(SecurityException::class);
        });

        it('throws exception when array exceeds limit', function () {
            $config = new SecurityConfig(maxArraySize: 50);
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            // Create an object with many properties which will exceed array size limit
            $data = [];
            for ($i = 0; $i < 60; $i++) {
                $data["prop{$i}"] = "value{$i}";
            }

            $testClass = new class
            {
                // Dynamic properties
            };

            $json = json_encode($data);

            // The top-level object has 60 properties, which exceeds the limit of 50
            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->toThrow(SecurityException::class, 'Maximum array size');
        });
    });

    describe('Nested Objects with Security', function () {
        it('validates nested objects work correctly', function () {
            // Test that basic deserialization works without security restrictions
            $testClass = new class
            {
                public string $name;

                public array $data;
            };

            $context = new DeserializationContext;
            $engine = new DeserializerEngine;

            $json = json_encode([
                'name' => 'test',
                'data' => ['key' => 'value'],
            ]);

            // This should work fine
            $result = $engine->deserializeToClass($json, get_class($testClass), $context);

            expect($result)->toBeObject()
                ->and($result->name)->toBe('test')
                ->and($result->data)->toBe(['key' => 'value']);
        });

        it('validates top-level object properties count', function () {
            $config = new SecurityConfig(maxArraySize: 3);
            $context = new DeserializationContext(securityConfig: $config);
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public string $a;

                public string $b;

                public string $c;

                public string $d;
            };

            // This object has 4 properties, exceeds limit of 3
            $json = json_encode([
                'a' => 'value1',
                'b' => 'value2',
                'c' => 'value3',
                'd' => 'value4',  // This exceeds the limit
            ]);

            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->toThrow(SecurityException::class, 'Maximum array size');
        });
    });

    describe('Security with Default Context (No Limits)', function () {
        it('allows unlimited strings when no security config', function () {
            $context = new DeserializationContext;  // No security config
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public string $text;
            };

            $json = json_encode(['text' => str_repeat('a', 1_000_000)]);

            // Should not throw because no security limits
            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->not->toThrow(SecurityException::class);
        });

        it('allows unlimited arrays when no security config', function () {
            $context = new DeserializationContext;  // No security config
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public array $items;
            };

            $json = json_encode(['items' => range(1, 10_000)]);

            // Should not throw because no security limits
            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->not->toThrow(SecurityException::class);
        });

        it('allows any class when no whitelist', function () {
            $context = new DeserializationContext;  // No security config
            $engine = new DeserializerEngine;

            $testClass = new class
            {
                public string $name;
            };

            $json = json_encode(['name' => 'test']);

            // Should not throw because no whitelist
            expect(fn () => $engine->deserializeToClass($json, get_class($testClass), $context))
                ->not->toThrow(SecurityException::class);
        });
    });
});
