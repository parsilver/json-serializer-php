<?php

use Farzai\JsonSerializer\JsonSerializer;

describe('Generator Serialization Tests', function () {
    it('serializes a simple sequential generator', function () {
        $generator = function (): Generator {
            yield 1;
            yield 2;
            yield 3;
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toBe('[1,2,3]');
    });

    it('serializes a generator yielding strings', function () {
        $generator = function (): Generator {
            yield 'apple';
            yield 'banana';
            yield 'cherry';
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toBe('["apple","banana","cherry"]');
    });

    it('serializes a generator yielding objects', function () {
        $generator = function (): Generator {
            yield new class
            {
                public string $name = 'John';

                public int $age = 30;
            };
            yield new class
            {
                public string $name = 'Jane';

                public int $age = 25;
            };
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"age":30')
            ->and($json)->toContain('"name":"Jane"')
            ->and($json)->toContain('"age":25');
    });

    it('serializes an associative generator with string keys', function () {
        $generator = function (): Generator {
            yield 'name' => 'John';
            yield 'age' => 30;
            yield 'email' => 'john@example.com';
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"age":30')
            ->and($json)->toContain('"email":"john@example.com"');
    });

    it('serializes an associative generator with mixed keys', function () {
        $generator = function (): Generator {
            yield 'id' => 123;
            yield 'name' => 'Product';
            yield 'active' => true;
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"id":123')
            ->and($json)->toContain('"name":"Product"')
            ->and($json)->toContain('"active":true');
    });

    it('serializes an empty generator as empty array', function () {
        $generator = function (): Generator {
            return;
            yield; // @phpstan-ignore-line - unreachable but needed for Generator type
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toBe('[]');
    });

    it('serializes a generator with nested arrays', function () {
        $generator = function (): Generator {
            yield ['id' => 1, 'tags' => ['php', 'laravel']];
            yield ['id' => 2, 'tags' => ['javascript', 'react']];
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"id":1')
            ->and($json)->toContain('["php","laravel"]')
            ->and($json)->toContain('"id":2')
            ->and($json)->toContain('["javascript","react"]');
    });

    it('serializes a generator with nested objects', function () {
        $generator = function (): Generator {
            yield (object) ['user' => (object) ['name' => 'John', 'role' => 'admin']];
            yield (object) ['user' => (object) ['name' => 'Jane', 'role' => 'user']];
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"role":"admin"')
            ->and($json)->toContain('"name":"Jane"')
            ->and($json)->toContain('"role":"user"');
    });

    it('serializes a large generator efficiently', function () {
        $generator = function (): Generator {
            for ($i = 0; $i < 1000; $i++) {
                yield ['id' => $i, 'value' => "item_{$i}"];
            }
        };

        $json = JsonSerializer::encode($generator());
        $decoded = json_decode($json, true);

        expect($decoded)->toBeArray()
            ->and(count($decoded))->toBe(1000)
            ->and($decoded[0])->toBe(['id' => 0, 'value' => 'item_0'])
            ->and($decoded[999])->toBe(['id' => 999, 'value' => 'item_999']);
    });

    it('serializes a generator with pretty print', function () {
        $generator = function (): Generator {
            yield 'name' => 'John';
            yield 'age' => 30;
        };

        $json = JsonSerializer::encode($generator(), prettyPrint: true);

        expect($json)->toContain("{\n")
            ->and($json)->toContain('"name": "John"')
            ->and($json)->toContain('"age": 30');
    });

    it('serializes a generator with mixed value types', function () {
        $generator = function (): Generator {
            yield 'string' => 'text';
            yield 'integer' => 42;
            yield 'float' => 3.14;
            yield 'boolean' => true;
            yield 'null' => null;
            yield 'array' => [1, 2, 3];
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"string":"text"')
            ->and($json)->toContain('"integer":42')
            ->and($json)->toContain('"float":3.14')
            ->and($json)->toContain('"boolean":true')
            ->and($json)->toContain('"null":null')
            ->and($json)->toContain('"array":[1,2,3]');
    });

    it('serializes a generator yielding non-sequential numeric keys as object', function () {
        $generator = function (): Generator {
            yield 0 => 'first';
            yield 2 => 'third';  // Skipped key 1
            yield 4 => 'fifth';
        };

        $json = JsonSerializer::encode($generator());

        // Should serialize as object since keys are not sequential
        expect($json)->toContain('"0":"first"')
            ->and($json)->toContain('"2":"third"')
            ->and($json)->toContain('"4":"fifth"');
    });

    it('handles generators that yield generators', function () {
        $generator = function (): Generator {
            yield 'data' => (function (): Generator {
                yield 1;
                yield 2;
                yield 3;
            })();
        };

        $json = JsonSerializer::encode($generator());

        expect($json)->toContain('"data":[1,2,3]');
    });

    it('serializes generator with versioned properties in objects', function () {
        $generator = function (): Generator {
            yield new class
            {
                public string $name = 'John';

                #[\Farzai\JsonSerializer\Attributes\Since('2.0')]
                public string $email = 'john@example.com';
            };
        };

        // Version 1.0 - should not include email
        $serializer1 = JsonSerializer::builder()->withVersion('1.0')->build();
        $json1 = $serializer1->serialize($generator());

        expect($json1)->toContain('"name":"John"')
            ->and($json1)->not->toContain('email');

        // Version 2.0 - should include email
        $generator2 = function (): Generator {
            yield new class
            {
                public string $name = 'John';

                #[\Farzai\JsonSerializer\Attributes\Since('2.0')]
                public string $email = 'john@example.com';
            };
        };

        $serializer2 = JsonSerializer::builder()->withVersion('2.0')->build();
        $json2 = $serializer2->serialize($generator2());

        expect($json2)->toContain('"name":"John"')
            ->and($json2)->toContain('"email":"john@example.com"');
    });
});
