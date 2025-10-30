<?php

use Farzai\JsonSerializer\Exceptions\SecurityException;
use Farzai\JsonSerializer\Security\SecurityConfig;
use Farzai\JsonSerializer\Security\SecurityValidator;

describe('Security Tests', function () {
    describe('SecurityConfig', function () {
        it('can create secure configuration with recommended defaults', function () {
            $config = SecurityConfig::secure();

            expect($config->maxDepth)->toBe(32)
                ->and($config->maxStringLength)->toBe(1_000_000)
                ->and($config->maxArraySize)->toBe(10_000)
                ->and($config->maxMemoryBytes)->toBe(128 * 1024 * 1024)
                ->and($config->timeoutSeconds)->toBe(30)
                ->and($config->strictTypes)->toBeTrue();
        });

        it('can create lenient configuration for trusted input', function () {
            $config = SecurityConfig::lenient();

            expect($config->maxDepth)->toBe(512)
                ->and($config->maxStringLength)->toBeNull()
                ->and($config->maxArraySize)->toBeNull()
                ->and($config->maxMemoryBytes)->toBeNull()
                ->and($config->timeoutSeconds)->toBeNull()
                ->and($config->strictTypes)->toBeFalse();
        });

        it('allows all classes when whitelist is empty', function () {
            $config = new SecurityConfig(allowedClasses: []);

            expect($config->isClassAllowed(\DateTime::class))->toBeTrue()
                ->and($config->isClassAllowed(\stdClass::class))->toBeTrue();
        });

        it('restricts classes when whitelist is provided', function () {
            $config = new SecurityConfig(
                allowedClasses: [\DateTime::class, \DateTimeImmutable::class]
            );

            expect($config->isClassAllowed(\DateTime::class))->toBeTrue()
                ->and($config->isClassAllowed(\DateTimeImmutable::class))->toBeTrue()
                ->and($config->isClassAllowed(\stdClass::class))->toBeFalse();
        });
    });

    describe('SecurityValidator - Depth Validation', function () {
        it('throws exception when max depth is exceeded', function () {
            $config = new SecurityConfig(maxDepth: 5);
            $validator = new SecurityValidator($config);

            expect(fn () => $validator->validateDepth(6))
                ->toThrow(SecurityException::class, 'Maximum nesting depth of 5 exceeded');
        });

        it('allows depth within limit', function () {
            $config = new SecurityConfig(maxDepth: 10);
            $validator = new SecurityValidator($config);

            expect(fn () => $validator->validateDepth(5))->not->toThrow(SecurityException::class);
        });
    });

    describe('SecurityValidator - String Length Validation', function () {
        it('throws exception when string exceeds max length', function () {
            $config = new SecurityConfig(maxStringLength: 100);
            $validator = new SecurityValidator($config);
            $longString = str_repeat('a', 101);

            expect(fn () => $validator->validateStringLength($longString))
                ->toThrow(SecurityException::class, 'Maximum string length');
        });

        it('allows strings within limit', function () {
            $config = new SecurityConfig(maxStringLength: 100);
            $validator = new SecurityValidator($config);
            $string = str_repeat('a', 50);

            expect(fn () => $validator->validateStringLength($string))
                ->not->toThrow(SecurityException::class);
        });

        it('allows unlimited strings when maxStringLength is null', function () {
            $config = new SecurityConfig(maxStringLength: null);
            $validator = new SecurityValidator($config);
            $veryLongString = str_repeat('a', 1_000_000);

            expect(fn () => $validator->validateStringLength($veryLongString))
                ->not->toThrow(SecurityException::class);
        });
    });

    describe('SecurityValidator - Array Size Validation', function () {
        it('throws exception when array exceeds max size', function () {
            $config = new SecurityConfig(maxArraySize: 10);
            $validator = new SecurityValidator($config);
            $largeArray = range(1, 11);

            expect(fn () => $validator->validateArraySize($largeArray))
                ->toThrow(SecurityException::class, 'Maximum array size');
        });

        it('allows arrays within limit', function () {
            $config = new SecurityConfig(maxArraySize: 100);
            $validator = new SecurityValidator($config);
            $array = range(1, 50);

            expect(fn () => $validator->validateArraySize($array))
                ->not->toThrow(SecurityException::class);
        });

        it('allows unlimited arrays when maxArraySize is null', function () {
            $config = new SecurityConfig(maxArraySize: null);
            $validator = new SecurityValidator($config);
            $largeArray = range(1, 100_000);

            expect(fn () => $validator->validateArraySize($largeArray))
                ->not->toThrow(SecurityException::class);
        });
    });

    describe('SecurityValidator - Class Whitelist Validation', function () {
        it('throws exception for disallowed class', function () {
            $config = new SecurityConfig(
                allowedClasses: [\DateTime::class]
            );
            $validator = new SecurityValidator($config);

            expect(fn () => $validator->validateClassAllowed(\stdClass::class))
                ->toThrow(SecurityException::class, 'not in the allowed classes whitelist');
        });

        it('allows whitelisted classes', function () {
            $config = new SecurityConfig(
                allowedClasses: [\DateTime::class, \DateTimeImmutable::class]
            );
            $validator = new SecurityValidator($config);

            expect(fn () => $validator->validateClassAllowed(\DateTime::class))
                ->not->toThrow(SecurityException::class);
        });
    });

    describe('SecurityValidator - Combined Validation', function () {
        it('can validate multiple constraints at once', function () {
            $config = new SecurityConfig(
                maxDepth: 10,
                maxStringLength: 100,
                maxArraySize: 50
            );
            $validator = new SecurityValidator($config);

            $string = 'test';
            $array = [1, 2, 3];

            expect(fn () => $validator->validate(5, $string, $array))
                ->not->toThrow(SecurityException::class);
        });

        it('throws exception on first violation in combined validation', function () {
            $config = new SecurityConfig(maxDepth: 5);
            $validator = new SecurityValidator($config);

            expect(fn () => $validator->validate(10))
                ->toThrow(SecurityException::class, 'Maximum nesting depth');
        });
    });

    describe('SecurityException Messages', function () {
        it('provides context for max depth exceeded', function () {
            $exception = SecurityException::maxDepthExceeded(32, 50);

            expect($exception->getMessage())
                ->toContain('32')
                ->and($exception->getMessage())->toContain('50');
        });

        it('provides context for max string length exceeded', function () {
            $exception = SecurityException::maxStringLengthExceeded(1000, 2000);

            expect($exception->getMessage())
                ->toContain('1000')
                ->and($exception->getMessage())->toContain('2000');
        });

        it('provides context for class not allowed', function () {
            $exception = SecurityException::classNotAllowed(\stdClass::class);

            expect($exception->getMessage())
                ->toContain('stdClass')
                ->and($exception->getMessage())->toContain('whitelist');
        });

        it('provides message for billion laughs attack', function () {
            $exception = SecurityException::billionLaughsDetected();

            expect($exception->getMessage())
                ->toContain('billion laughs')
                ->and($exception->getMessage())->toContain('entity expansion');
        });
    });
});
