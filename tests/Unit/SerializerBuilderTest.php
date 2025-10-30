<?php

use Farzai\JsonSerializer\Builder\SerializerBuilder;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\SerializerEngine;
use Farzai\JsonSerializer\Security\SecurityConfig;
use Farzai\JsonSerializer\Types\TypeCoercionMode;

describe('SerializerBuilder Tests', function () {
    describe('Basic Builder Usage', function () {
        it('can create a builder instance', function () {
            $builder = new SerializerBuilder;

            expect($builder)->toBeInstanceOf(SerializerBuilder::class);
        });

        it('can build a serializer engine', function () {
            $builder = new SerializerBuilder;
            $serializer = $builder->buildSerializer();

            expect($serializer)->toBeInstanceOf(SerializerEngine::class);
        });

        it('can build a deserializer engine', function () {
            $builder = new SerializerBuilder;
            $deserializer = $builder->buildDeserializer();

            expect($deserializer)->toBeInstanceOf(DeserializerEngine::class);
        });
    });

    describe('Configuration Methods', function () {
        it('can set max depth', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withMaxDepth(100);

            expect($result)->toBe($builder); // Fluent interface

            $serializer = $builder->buildSerializer();
            $json = $serializer->serialize(['test' => 'value']);

            expect($json)->toBeString();
        });

        it('can enable circular reference detection', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withCircularReferenceDetection(true);

            expect($result)->toBe($builder);
        });

        it('can disable circular reference detection', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withCircularReferenceDetection(false);

            expect($result)->toBe($builder);
        });

        it('can enable pretty print', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withPrettyPrint(true);

            expect($result)->toBe($builder);

            $serializer = $builder->buildSerializer();
            $json = $serializer->serialize(['test' => 'value']);

            // Pretty printed JSON should contain newlines
            expect($json)->toContain("\n");
        });

        it('can disable pretty print', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withPrettyPrint(false);

            expect($result)->toBe($builder);

            $serializer = $builder->buildSerializer();
            $json = $serializer->serialize(['test' => 'value']);

            // Compact JSON should not contain newlines
            expect($json)->not->toContain("\n");
        });

        it('can enable strict types', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withStrictTypes(true);

            expect($result)->toBe($builder);
        });

        it('can disable strict types', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withStrictTypes(false);

            expect($result)->toBe($builder);
        });

        it('can set version', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withVersion('1.0.0');

            expect($result)->toBe($builder);
        });

        it('can enable extra properties', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withAllowExtraProperties(true);

            expect($result)->toBe($builder);
        });

        it('can set type coercion mode', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withTypeCoercionMode(TypeCoercionMode::LENIENT);

            expect($result)->toBe($builder);
        });
    });

    describe('Security Configuration', function () {
        it('can set security config', function () {
            $builder = new SerializerBuilder;
            $config = SecurityConfig::secure();
            $result = $builder->withSecurity($config);

            expect($result)->toBe($builder);
        });

        it('can set secure defaults', function () {
            $builder = new SerializerBuilder;
            $result = $builder->withSecureDefaults();

            expect($result)->toBe($builder);
        });

        it('applies security config to deserializer', function () {
            $builder = new SerializerBuilder;
            $builder->withSecureDefaults();

            $deserializer = $builder->buildDeserializer();

            expect($deserializer)->toBeInstanceOf(DeserializerEngine::class);
        });
    });

    describe('Fluent Interface', function () {
        it('supports method chaining', function () {
            $builder = new SerializerBuilder;

            $result = $builder
                ->withMaxDepth(50)
                ->withPrettyPrint(true)
                ->withStrictTypes(false)
                ->withVersion('2.0.0')
                ->withCircularReferenceDetection(true);

            expect($result)->toBe($builder);
        });

        it('can chain security and other configs', function () {
            $builder = new SerializerBuilder;

            $result = $builder
                ->withSecureDefaults()
                ->withMaxDepth(100)
                ->withPrettyPrint(true);

            expect($result)->toBe($builder);

            $serializer = $builder->buildSerializer();
            expect($serializer)->toBeInstanceOf(SerializerEngine::class);
        });
    });

    describe('Multiple Engine Creation', function () {
        it('can create multiple serializers with same config', function () {
            $builder = new SerializerBuilder;
            $builder->withPrettyPrint(true)->withMaxDepth(100);

            $serializer1 = $builder->buildSerializer();
            $serializer2 = $builder->buildSerializer();

            expect($serializer1)->toBeInstanceOf(SerializerEngine::class);
            expect($serializer2)->toBeInstanceOf(SerializerEngine::class);
            expect($serializer1)->not->toBe($serializer2); // Different instances
        });

        it('can create multiple deserializers with same config', function () {
            $builder = new SerializerBuilder;
            $builder->withStrictTypes(false)->withMaxDepth(100);

            $deserializer1 = $builder->buildDeserializer();
            $deserializer2 = $builder->buildDeserializer();

            expect($deserializer1)->toBeInstanceOf(DeserializerEngine::class);
            expect($deserializer2)->toBeInstanceOf(DeserializerEngine::class);
            expect($deserializer1)->not->toBe($deserializer2); // Different instances
        });
    });

    describe('Integration with Engines', function () {
        it('built serializer can serialize data', function () {
            $builder = new SerializerBuilder;
            $serializer = $builder->buildSerializer();

            $data = ['name' => 'John', 'age' => 30];
            $json = $serializer->serialize($data);

            expect($json)->toBeString();
            expect($json)->toContain('"name"');
            expect($json)->toContain('"age"');
        });

        it('built deserializer can deserialize data', function () {
            $builder = new SerializerBuilder;
            $deserializer = $builder->buildDeserializer();

            $testClass = new class
            {
                public string $name;

                public int $age;
            };

            $json = json_encode(['name' => 'John', 'age' => 30]);
            $result = $deserializer->deserializeToClass($json, get_class($testClass));

            expect($result)->toBeObject();
            expect($result->name)->toBe('John');
            expect($result->age)->toBe(30);
        });

        it('preserves configuration across builds', function () {
            $builder = new SerializerBuilder;
            $builder->withPrettyPrint(true);

            $serializer1 = $builder->buildSerializer();
            $json1 = $serializer1->serialize(['test' => 'value']);

            $serializer2 = $builder->buildSerializer();
            $json2 = $serializer2->serialize(['test' => 'value']);

            // Both should produce pretty-printed JSON
            expect($json1)->toContain("\n");
            expect($json2)->toContain("\n");
        });
    });

    describe('Default Configuration', function () {
        it('uses sensible defaults when no config is set', function () {
            $builder = new SerializerBuilder;
            $serializer = $builder->buildSerializer();

            $data = ['key' => 'value'];
            $json = $serializer->serialize($data);

            expect($json)->toBe('{"key":"value"}'); // Compact by default
        });

        it('deserializer uses default config', function () {
            $builder = new SerializerBuilder;
            $deserializer = $builder->buildDeserializer();

            $testClass = new class
            {
                public string $key;
            };

            $json = '{"key":"value"}';
            $result = $deserializer->deserializeToClass($json, get_class($testClass));

            expect($result->key)->toBe('value');
        });
    });
});
