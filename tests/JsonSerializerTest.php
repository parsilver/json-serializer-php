<?php

use Farzai\JsonSerializer\JsonSerializer;

it('can serialize simple array', function () {
    $data = ['name' => 'John', 'age' => 30];
    $json = JsonSerializer::encode($data);

    expect($json)->toBe('{"name":"John","age":30}');
});

it('can serialize nested arrays', function () {
    $data = [
        'user' => [
            'name' => 'John',
            'email' => 'john@example.com'
        ],
        'active' => true
    ];

    $json = JsonSerializer::encode($data);

    expect($json)->toContain('"user"')
        ->and($json)->toContain('"name":"John"')
        ->and($json)->toContain('"active":true');
});

it('can serialize with pretty print', function () {
    $data = ['name' => 'John', 'age' => 30];
    $json = JsonSerializer::encodePretty($data);

    expect($json)->toContain("\n")
        ->and($json)->toContain('    ');
});

it('can serialize scalar values', function () {
    expect(JsonSerializer::encode('hello'))->toBe('"hello"');
    expect(JsonSerializer::encode(42))->toBe('42');
    expect(JsonSerializer::encode(true))->toBe('true');
    expect(JsonSerializer::encode(false))->toBe('false');
    expect(JsonSerializer::encode(null))->toBe('null');
});

it('can serialize sequential arrays', function () {
    $data = [1, 2, 3, 4, 5];
    $json = JsonSerializer::encode($data);

    expect($json)->toBe('[1,2,3,4,5]');
});

it('can serialize objects', function () {
    $obj = new stdClass();
    $obj->name = 'Jane';
    $obj->age = 25;

    $json = JsonSerializer::encode($obj);

    expect($json)->toContain('"name":"Jane"')
        ->and($json)->toContain('"age":25');
});
