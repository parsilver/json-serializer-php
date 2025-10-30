<?php

use Farzai\JsonSerializer\Engine\StreamingDeserializer;
use Farzai\JsonSerializer\Exceptions\DeserializationException;
use Farzai\JsonSerializer\JsonSerializer;
use Farzai\JsonSerializer\Stream\JsonPath;
use Farzai\JsonSerializer\Stream\LazyJsonIterator;
use Farzai\JsonSerializer\Stream\StringStream;

beforeEach(function () {
    // Create temp directory for test files
    $this->tempDir = sys_get_temp_dir().'/json-serializer-test-'.uniqid();
    if (! file_exists($this->tempDir)) {
        mkdir($this->tempDir, 0777, true);
    }
});

afterEach(function () {
    // Clean up temp files
    if (isset($this->tempDir) && file_exists($this->tempDir)) {
        $files = glob($this->tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }
});

describe('Streaming Deserialization', function () {
    it('can stream deserialize a simple array', function () {
        $json = '["apple", "banana", "cherry"]';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        $items = [];
        foreach ($deserializer->iterateArray() as $item) {
            $items[] = $item;
        }

        expect($items)->toBe(['apple', 'banana', 'cherry']);
    });

    it('can stream deserialize a simple object', function () {
        $json = '{"name": "John", "age": 30, "city": "NYC"}';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        $fields = [];
        foreach ($deserializer->iterateObject() as [$key, $value]) {
            $fields[$key] = $value;
        }

        expect($fields)->toBe([
            'name' => 'John',
            'age' => 30,
            'city' => 'NYC',
        ]);
    });

    it('can stream deserialize nested structures', function () {
        $json = '[{"id": 1, "name": "Alice"}, {"id": 2, "name": "Bob"}]';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        $users = [];
        foreach ($deserializer->iterateArray() as $user) {
            $users[] = $user;
        }

        expect($users)->toBe([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);
    });

    it('can read complete value at once', function () {
        $json = '{"users": [{"name": "Alice"}, {"name": "Bob"}]}';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        $data = $deserializer->readValue();

        expect($data)->toBe([
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
            ],
        ]);
    });

    it('can skip values', function () {
        $json = '[1, 2, 3, 4, 5]';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        $items = [];
        $index = 0;
        foreach ($deserializer->iterateArray() as $item) {
            if ($index % 2 === 0) {
                $items[] = $item;
            }
            $index++;
        }

        expect($items)->toBe([1, 3, 5]);
    });

    it('throws exception when expecting array but got object', function () {
        $json = '{"key": "value"}';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        expect(fn () => iterator_to_array($deserializer->iterateArray()))
            ->toThrow(DeserializationException::class);
    });

    it('throws exception when expecting object but got array', function () {
        $json = '[1, 2, 3]';
        $stream = new StringStream($json);
        $deserializer = new StreamingDeserializer($stream);

        expect(fn () => iterator_to_array($deserializer->iterateObject()))
            ->toThrow(DeserializationException::class);
    });

    it('can stream from file using facade', function () {
        $filePath = $this->tempDir.'/test.json';
        file_put_contents($filePath, '["a", "b", "c"]');

        $deserializer = JsonSerializer::streamFromFile($filePath);

        $items = [];
        foreach ($deserializer->iterateArray() as $item) {
            $items[] = $item;
        }

        expect($items)->toBe(['a', 'b', 'c']);
    });
});

describe('Lazy JSON Iterator', function () {
    it('can iterate over array without loading all into memory', function () {
        $json = '[1, 2, 3, 4, 5]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        $items = [];
        foreach ($iterator as $item) {
            $items[] = $item;
        }

        expect($items)->toBe([1, 2, 3, 4, 5]);
    });

    it('can deserialize array elements to class', function () {
        $json = '[{"name": "Alice", "age": 30}, {"name": "Bob", "age": 25}]';
        $stream = new StringStream($json);

        $class = new class
        {
            public string $name;

            public int $age;
        };

        $iterator = new LazyJsonIterator($stream, $class::class);

        $users = [];
        foreach ($iterator as $user) {
            $users[] = ['name' => $user->name, 'age' => $user->age];
        }

        expect($users)->toBe([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);
    });

    it('can use map to transform elements', function () {
        $json = '[1, 2, 3]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        $doubled = iterator_to_array($iterator->map(fn ($x) => $x * 2));

        expect($doubled)->toBe([2, 4, 6]);
    });

    it('can use filter to select elements', function () {
        $json = '[1, 2, 3, 4, 5]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        $evens = iterator_to_array($iterator->filter(fn ($x) => $x % 2 === 0));

        expect($evens)->toBe([1 => 2, 3 => 4]);
    });

    it('can use take to limit elements', function () {
        $json = '[1, 2, 3, 4, 5]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        $first3 = iterator_to_array($iterator->take(3));

        expect($first3)->toBe([1, 2, 3]);
    });

    it('can use skip to skip elements', function () {
        $json = '[1, 2, 3, 4, 5]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        $after2 = iterator_to_array($iterator->skip(2));

        expect($after2)->toBe([2 => 3, 3 => 4, 4 => 5]);
    });

    it('can use each to process elements', function () {
        $json = '[1, 2, 3]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        $sum = 0;
        $iterator->each(function ($item) use (&$sum) {
            $sum += $item;
        });

        expect($sum)->toBe(6);
    });

    it('throws exception when trying to rewind', function () {
        $json = '[1, 2, 3]';
        $stream = new StringStream($json);
        $iterator = new LazyJsonIterator($stream);

        // First iteration
        foreach ($iterator as $item) {
            break;
        }

        // Try to rewind
        expect(fn () => $iterator->rewind())
            ->toThrow(DeserializationException::class, 'Cannot rewind a streaming iterator');
    });

    it('can iterate using facade helper', function () {
        $filePath = $this->tempDir.'/users.json';
        file_put_contents($filePath, '[{"name": "Alice"}, {"name": "Bob"}]');

        $class = new class
        {
            public string $name;
        };

        $iterator = JsonSerializer::iterateFile($filePath, $class::class);

        $names = [];
        foreach ($iterator as $user) {
            $names[] = $user->name;
        }

        expect($names)->toBe(['Alice', 'Bob']);
    });
});

describe('JSON Path Extraction', function () {
    it('can extract simple field', function () {
        $json = '{"name": "John", "age": 30}';
        $stream = new StringStream($json);
        $path = new JsonPath($stream);

        $name = $path->extract('.name');

        expect($name)->toBe('John');
    });

    it('can extract nested field', function () {
        $json = '{"user": {"profile": {"email": "john@example.com"}}}';
        $stream = new StringStream($json);
        $path = new JsonPath($stream);

        $email = $path->extract('.user.profile.email');

        expect($email)->toBe('john@example.com');
    });

    it('can extract array element by index', function () {
        $json = '{"items": ["apple", "banana", "cherry"]}';
        $stream = new StringStream($json);
        $path = new JsonPath($stream);

        $item = $path->extract('.items[1]');

        expect($item)->toBe('banana');
    });

    it('can extract nested array element', function () {
        $json = '{"users": [{"name": "Alice"}, {"name": "Bob"}]}';
        $stream = new StringStream($json);
        $path = new JsonPath($stream);

        $user = $path->extract('.users[0]');

        expect($user)->toBe(['name' => 'Alice']);
    });

    it('returns null for non-existent path', function () {
        $json = '{"name": "John"}';
        $stream = new StringStream($json);
        $path = new JsonPath($stream);

        $value = $path->extract('.nonexistent');

        expect($value)->toBeNull();
    });

    it('can extract multiple paths', function () {
        $json = '{"name": "John", "age": 30, "city": "NYC"}';
        $stream = new StringStream($json);
        $path = new JsonPath($stream);

        $values = $path->extractMultiple(['.name', '.age', '.city']);

        expect($values)->toBe([
            '.name' => 'John',
            '.age' => 30,
            '.city' => 'NYC',
        ]);
    });

    it('can use facade helper for path extraction', function () {
        $filePath = $this->tempDir.'/data.json';
        file_put_contents($filePath, '{"user": {"name": "Alice", "email": "alice@example.com"}}');

        $name = JsonSerializer::extractPath($filePath, '.user.name');

        expect($name)->toBe('Alice');
    });

    it('can use facade helper for multiple path extraction', function () {
        $filePath = $this->tempDir.'/data.json';
        file_put_contents($filePath, '{"name": "Alice", "age": 30}');

        $values = JsonSerializer::extractPaths($filePath, ['.name', '.age']);

        expect($values)->toBe([
            '.name' => 'Alice',
            '.age' => 30,
        ]);
    });
});

describe('Large File Handling', function () {
    it('can process large array file efficiently', function () {
        // Create a file with many items
        $filePath = $this->tempDir.'/large.json';
        $fp = fopen($filePath, 'w');
        fwrite($fp, '[');
        for ($i = 0; $i < 1000; $i++) {
            if ($i > 0) {
                fwrite($fp, ',');
            }
            fwrite($fp, json_encode(['id' => $i, 'name' => "User {$i}"]));
        }
        fwrite($fp, ']');
        fclose($fp);

        $iterator = JsonSerializer::iterateFile($filePath);

        // Process only first 10 items
        $count = 0;
        foreach ($iterator->take(10) as $item) {
            expect($item)->toHaveKey('id');
            expect($item)->toHaveKey('name');
            $count++;
        }

        expect($count)->toBe(10);
    });
});
