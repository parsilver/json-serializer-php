# JSON Serializer for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/farzai/json-serializer.svg?style=flat-square)](https://packagist.org/packages/farzai/json-serializer)
[![Tests](https://img.shields.io/github/actions/workflow/status/parsilver/json-serializer-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/parsilver/json-serializer-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/farzai/json-serializer.svg?style=flat-square)](https://packagist.org/packages/farzai/json-serializer)

A high-performance, type-safe JSON serializer and deserializer for PHP 8.1+ with advanced features including attribute-based configuration, nested object support, versioning, transformers, and comprehensive error handling.

## Installation

You can install the package via composer:

```bash
composer require farzai/json-serializer
```

## Features

- **High Performance** - Optimized for speed and memory efficiency
- **Generator Support** - Memory-efficient serialization of large datasets using PHP generators
- **Type-Safe** - Full PHP 8.1+ type system support including union types
- **Nested Objects** - Automatic recursive serialization/deserialization
- **Collections** - `array<Type>` notation for typed collections
- **Attributes** - Powerful attribute-based configuration
- **Versioning** - API versioning with `#[Since]` and `#[Until]`
- **Transformers** - Custom transformers for DateTime, Enums, and more
- **Naming Strategies** - snake_case, camelCase, PascalCase, kebab-case
- **Virtual Properties** - Computed properties from methods
- **Error Handling** - Detailed error messages with property paths
- **Circular Reference Detection** - Automatic detection and prevention
- **Max Depth Control** - Configurable depth limits
- **Type Coercion** - STRICT, SAFE, and LENIENT modes for flexible type handling

## When to Use This Library

### ✅ Use This Library When:

- **Type-Safe Object Mapping**: You need to deserialize JSON into strongly-typed PHP objects
- **API Versioning**: Your API supports multiple versions with `#[Since]` and `#[Until]` attributes
- **Large File Processing**: You need to process JSON files > 100MB without memory issues
- **Advanced Features**: You need transformers, virtual properties, or custom naming strategies
- **Attribute-Based Config**: You prefer declarative configuration over imperative code
- **Streaming Operations**: You need to process JSON incrementally or extract specific paths

### ⚠️ Use Native Functions When:

- **Simple Arrays**: Basic `json_encode(['key' => 'value'])` for simple data structures
- **Maximum Performance**: Serialization speed is critical and you don't need advanced features
- **Small Data**: Processing < 1KB of data where overhead doesn't matter
- **No Type Safety Needed**: You're comfortable working with untyped arrays

### Performance Trade-offs

**Serialization**: ~50-60x slower than native `json_encode` due to rich features
- Native: 1.0μs for small data, 3.6ms for 10MB
- This library: 49μs for small data, 204ms for 10MB
- **Recommendation**: Use native functions for hot paths where features aren't needed

**Deserialization**: Competitive with native `json_decode` (~1.0x speed)
- Native: 942μs for 1MB, 9.9ms for 10MB
- This library: 936μs for 1MB, 10.0ms for 10MB
- **Recommendation**: Safe to use everywhere for deserialization with type-safe object mapping

**Memory**: Only ~7% overhead compared to native functions

## Usage

### Basic Serialization

```php
use Farzai\JsonSerializer\JsonSerializer;

// Simple array to JSON
$data = ['name' => 'John', 'age' => 30];
$json = JsonSerializer::encode($data);
// {"name":"John","age":30}

// Object to JSON
class User {
    public function __construct(
        public string $name,
        public int $age
    ) {}
}

$user = new User('John Doe', 30);
$json = JsonSerializer::encode($user);
// {"name":"John Doe","age":30}

// Pretty print
$json = JsonSerializer::encode($user, prettyPrint: true);
```

### Basic Deserialization

```php
// JSON to array
$json = '{"name":"John","age":30}';
$data = JsonSerializer::decode($json);
// ['name' => 'John', 'age' => 30]

// JSON to class
class User {
    public string $name;
    public int $age;
}

$user = JsonSerializer::decodeToClass($json, User::class);
// User instance with name='John', age=30

// JSON array to array of objects
$json = '[{"name":"John","age":30},{"name":"Jane","age":25}]';
$users = JsonSerializer::decodeArray($json, User::class);
// Array of User instances
```

### Nested Objects & Collections

```php
use Farzai\JsonSerializer\Attributes\Type;
use Farzai\JsonSerializer\JsonSerializer;

class Address {
    public string $street;
    public string $city;
    public string $country;
}

class Author {
    public string $name;
    public string $email;
}

class Book {
    public string $title;
    public Address $publisher;

    #[Type('array<Author>')]
    public array $authors;
}

// Deserialize nested objects and collections
$json = '{
    "title": "PHP Guide",
    "publisher": {
        "street": "123 Main St",
        "city": "NYC",
        "country": "USA"
    },
    "authors": [
        {"name": "John Doe", "email": "john@example.com"},
        {"name": "Jane Smith", "email": "jane@example.com"}
    ]
}';

$book = JsonSerializer::decodeToClass($json, Book::class);
// $book->publisher is an Address instance
// $book->authors is an array of Author instances
```

### Attributes

```php
use Farzai\JsonSerializer\Attributes\{
    SerializedName,
    Ignore,
    DateFormat,
    Since,
    Until,
    VirtualProperty,
    NamingStrategy
};

#[NamingStrategy('snake_case')]
class User {
    #[SerializedName('user_id')]
    public int $id;

    public string $name;

    #[Ignore]
    public string $password;

    #[DateFormat('Y-m-d')]
    public DateTime $createdAt;

    #[Since('2.0')]
    public ?string $email = null;

    #[Until('3.0')]
    public ?string $legacyField = null;

    #[VirtualProperty]
    public function getDisplayName(): string {
        return strtoupper($this->name);
    }
}
```

### Versioning

```php
// Serialize for version 1.0 (excludes email, includes legacyField)
$json = JsonSerializer::builder()
    ->withVersion('1.0')
    ->build()
    ->encode($user);

// Serialize for version 2.0 (includes email, includes legacyField)
$json = JsonSerializer::builder()
    ->withVersion('2.0')
    ->build()
    ->encode($user);

// Serialize for version 3.0+ (includes email, excludes legacyField)
$json = JsonSerializer::builder()
    ->withVersion('3.0')
    ->build()
    ->encode($user);
```

### Custom Transformers

```php
use Farzai\JsonSerializer\Attributes\Transformer;
use Farzai\JsonSerializer\Attributes\DateFormat;

class Event {
    public string $name;

    #[DateFormat('Y-m-d H:i:s')]
    public DateTime $startTime;

    #[DateFormat(DateFormat::ISO8601)]
    public DateTime $endTime;

    #[Transformer(MyCustomTransformer::class, ['option' => 'value'])]
    public mixed $customField;
}
```

### Union Types

```php
class FlexibleData {
    public string|int $id;  // Can be string or int
    public User|null $owner; // Can be User object or null
    public string|float $value; // Can be string or float
}

$json = '{"id": "abc123", "owner": null, "value": 3.14}';
$data = JsonSerializer::decodeToClass($json, FlexibleData::class);
// Automatically handles union types
```

### Error Handling

```php
use Farzai\JsonSerializer\Exceptions\DeserializationException;

try {
    $json = '{"name":"John","address":"invalid"}'; // address should be object
    $user = JsonSerializer::decodeToClass($json, UserWithAddress::class);
} catch (DeserializationException $e) {
    echo $e->getMessage();
    // "Type mismatch during deserialization. Property path: address. Type mismatch: expected Address, got string"

    echo $e->getPropertyPath(); // "address"
    echo $e->getExpectedType(); // "Address"
    echo $e->getActualType();   // "string"
}
```

### Circular Reference Detection

The library automatically detects and prevents circular references during serialization:

```php
use Farzai\JsonSerializer\JsonSerializer;
use Farzai\JsonSerializer\Exceptions\SerializationException;

class Node {
    public string $name;
    public ?Node $parent = null;
    public ?Node $child = null;
}

// Create circular reference
$parent = new Node();
$parent->name = 'Parent';

$child = new Node();
$child->name = 'Child';
$child->parent = $parent;

$parent->child = $child; // Circular reference!

try {
    $json = JsonSerializer::encode($parent);
} catch (SerializationException $e) {
    echo $e->getMessage();
    // "Circular reference detected for object of class Node"
}

// Disable circular reference detection if needed
$serializer = JsonSerializer::builder()
    ->withCircularReferenceDetection(false)
    ->build();

// Now it will serialize (may cause infinite loop!)
$json = $serializer->encode($parent);
```

### Builder Pattern

```php
use Farzai\JsonSerializer\Builder\SerializerBuilder;

$serializer = SerializerBuilder::create()
    ->withVersion('2.0')
    ->withMaxDepth(100)
    ->withPrettyPrint(true)
    ->build();

$json = $serializer->encode($data);
```

### Security & Configuration

Protect your application from malicious or malformed JSON with built-in security features:

```php
use Farzai\JsonSerializer\JsonSerializer;
use Farzai\JsonSerializer\Exceptions\SerializationException;

// Configure security limits
$serializer = JsonSerializer::builder()
    ->withMaxDepth(50)                          // Prevent deeply nested structures
    ->withCircularReferenceDetection(true)      // Detect circular references (default: true)
    ->withStrictTypes(true)                     // Enable strict type checking (default: true)
    ->build();

// Example: Max depth protection
class DeepNested {
    public ?DeepNested $child = null;
}

$root = new DeepNested();
$current = $root;
for ($i = 0; $i < 100; $i++) {
    $current->child = new DeepNested();
    $current = $current->child;
}

try {
    $json = $serializer->encode($root);
} catch (SerializationException $e) {
    echo $e->getMessage();
    // "Maximum depth of 50 exceeded"
}

// Strict types mode prevents unsafe type coercion
$strictSerializer = JsonSerializer::builder()
    ->withStrictTypes(true)
    ->build();

// Disable strict mode for more lenient serialization
$lenientSerializer = JsonSerializer::builder()
    ->withStrictTypes(false)
    ->build();
```

**Security Best Practices:**

- Always set a reasonable `maxDepth` limit (default: 512)
- Keep circular reference detection enabled for untrusted data
- Use strict types mode when deserializing external data
- Validate data structure before deserialization for critical applications

### Type Coercion Modes

Control how the deserializer handles type mismatches with three coercion modes:

```php
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\DeserializationContext;
use Farzai\JsonSerializer\Types\TypeCoercionMode;
use Farzai\JsonSerializer\JsonSerializer;

class TypedData {
    public int $id;
    public string $name;
    public float $score;
    public bool $active;
}

// STRICT Mode - No coercion, throw on mismatch
$context = new DeserializationContext(
    typeCoercionMode: TypeCoercionMode::STRICT
);
$deserializer = new DeserializerEngine;
$data = $deserializer->deserializeToClass($json, TypedData::class, $context);
// {"id": "123"} → Throws DeserializationException

// SAFE Mode - Conservative coercion (DEFAULT)
$context = new DeserializationContext(
    typeCoercionMode: TypeCoercionMode::SAFE  // This is the default
);
$deserializer = new DeserializerEngine;
$data = $deserializer->deserializeToClass($json, TypedData::class, $context);
// {"id": "123"} → id = 123 (int)
// {"score": "3.14"} → score = 3.14 (float)
// {"active": "true"} → active = true (bool)
// {"active": 1} → active = true (bool)

// LENIENT Mode - Aggressive coercion
$context = new DeserializationContext(
    typeCoercionMode: TypeCoercionMode::LENIENT
);
$deserializer = new DeserializerEngine;
$data = $deserializer->deserializeToClass($json, TypedData::class, $context);
// {"id": "abc123"} → id = 123 (extracts number)
// {"name": 123} → name = "123" (cast to string)
// {"active": "yes"} → active = true (bool)
// {"active": "no"} → active = false (bool)

// Configure default deserializer for all static methods
$customDeserializer = new DeserializerEngine(
    defaultTypeCoercionMode: TypeCoercionMode::LENIENT
);
JsonSerializer::setDefaultDeserializer($customDeserializer);

// Now all JsonSerializer::decodeToClass() calls use LENIENT mode
$data = JsonSerializer::decodeToClass($json, TypedData::class);
```

#### Coercion Rules

**STRICT Mode:**
- No type coercion
- int → float (safe widening)
- Throws exception on any other mismatch

**SAFE Mode (Default):**
- Numeric strings → numbers ("123" → 123)
- Boolean strings → bool ("true" → true, "false" → false)
- 1/0 → boolean
- int → float
- Whole number floats → int (42.0 → 42)

**LENIENT Mode:**
- Any value → string (cast)
- Numeric-like → numbers ("abc123" → 123)
- Truthy/falsy → bool ("yes"/"no", non-empty strings)
- Very permissive conversions

### Custom Type Handlers

Extend the serializer with custom type handlers for specialized serialization logic:

```php
use Farzai\JsonSerializer\Contracts\TypeHandlerInterface;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\JsonSerializer;

// Custom type handler for Money objects
class MoneyTypeHandler implements TypeHandlerInterface
{
    public function supports(mixed $value): bool
    {
        return $value instanceof Money;
    }

    public function serialize(mixed $value, SerializationContext $context): string
    {
        /** @var Money $value */
        return json_encode([
            'amount' => $value->getAmount(),
            'currency' => $value->getCurrency()
        ]);
    }

    public function getPriority(): int
    {
        return 100; // Higher priority = checked first
    }
}

// Register custom handler
$serializer = JsonSerializer::builder()
    ->addTypeHandler(new MoneyTypeHandler())
    ->build();

// Use with custom type
$price = new Money(1999, 'USD');
$json = $serializer->encode($price);
// {"amount":1999,"currency":"USD"}
```

**Use Cases for Custom Type Handlers:**

- Special formatting for value objects (Money, Email, UUID)
- Custom serialization for third-party library objects
- Encryption/decryption during serialization
- Legacy data format compatibility
- Performance optimization for specific types

### PSR-16 Cache Support

Improve performance by caching metadata with any PSR-16 compatible cache:

```php
use Farzai\JsonSerializer\JsonSerializer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

// Create PSR-16 cache (using Symfony Cache as example)
$filesystemAdapter = new FilesystemAdapter();
$cache = new Psr16Cache($filesystemAdapter);

// Configure serializer with cache
$serializer = JsonSerializer::builder()
    ->withCache($cache)
    ->build();

// Metadata for classes is now cached
// First serialization: analyzes class metadata
$json1 = $serializer->encode($user);

// Subsequent serializations: uses cached metadata (much faster!)
$json2 = $serializer->encode($anotherUser);
```

**Compatible Cache Libraries:**

- Symfony Cache
- Laravel Cache (via PSR-16 adapter)
- Doctrine Cache
- Any PSR-16 Simple Cache implementation

**Performance Impact:**

- First serialization: ~5-10% slower (cache write)
- Subsequent serializations: ~30-50% faster (cache hit)
- Recommended for applications serializing many instances of the same class

## Streaming & Large Files

For processing large JSON files without loading everything into memory, use the streaming API:

### Streaming Deserialization

```php
use Farzai\JsonSerializer\JsonSerializer;

// Stream large JSON file
$stream = JsonSerializer::streamFromFile('large-data.json');

// Iterate over array elements
foreach ($stream->iterateArray() as $item) {
    // Process each item without loading entire array
    echo $item['name'];
}

// Iterate over object fields
foreach ($stream->iterateObject() as [$key, $value]) {
    echo "$key => $value\n";
}
```

### Lazy Iterator

Process large JSON arrays with on-demand deserialization:

```php
class User {
    public string $name;
    public string $email;
}

// Lazy load and deserialize array elements
$iterator = JsonSerializer::iterateFile('users.json', User::class);

// Only loads items as you iterate
foreach ($iterator as $user) {
    echo $user->name; // User object created on-demand
}

// Functional operations without loading all data
$adults = $iterator->filter(fn($user) => $user->age >= 18);
$names = $iterator->map(fn($user) => $user->name);
$first10 = $iterator->take(10);
$afterFirst100 = $iterator->skip(100);
```

### Path-based Extraction

Extract specific data without parsing the entire JSON:

```php
// Extract single value
$name = JsonSerializer::extractPath('data.json', '.user.name');

// Extract nested field
$email = JsonSerializer::extractPath('data.json', '.user.profile.email');

// Extract array element
$firstItem = JsonSerializer::extractPath('data.json', '.items[0]');

// Extract multiple paths at once
$values = JsonSerializer::extractPaths('data.json', [
    '.user.name',
    '.user.email',
    '.settings.theme'
]);
```

### Memory-Efficient Processing

```php
// Process 1GB+ JSON file with constant memory usage
$iterator = JsonSerializer::iterateFile('huge-dataset.json');

$count = 0;
$sum = 0;

foreach ($iterator->take(1000) as $item) {
    $count++;
    $sum += $item['value'];
}

echo "Average: " . ($sum / $count);
```

### Generator Serialization

Serialize large datasets memory-efficiently using PHP generators:

```php
use Farzai\JsonSerializer\JsonSerializer;

// Simple generator - sequential array
function getUsers(): Generator {
    for ($i = 1; $i <= 1000; $i++) {
        yield ['id' => $i, 'name' => "User {$i}"];
    }
}

$json = JsonSerializer::encode(getUsers());
// [{"id":1,"name":"User 1"},{"id":2,"name":"User 2"},...]

// Generator yielding objects
function getUserObjects(): Generator {
    for ($i = 1; $i <= 1000; $i++) {
        yield new User(id: $i, name: "User {$i}");
    }
}

$json = JsonSerializer::encode(getUserObjects());

// Associative generator - JSON object
function getConfig(): Generator {
    yield 'app_name' => 'MyApp';
    yield 'version' => '1.0.0';
    yield 'debug' => false;
}

$json = JsonSerializer::encode(getConfig());
// {"app_name":"MyApp","version":"1.0.0","debug":false}

// Nested generators
function getCategories(): Generator {
    yield 'electronics' => getProducts('electronics');
    yield 'books' => getProducts('books');
}

function getProducts(string $category): Generator {
    // Fetch products from database one at a time
    foreach ($db->query("SELECT * FROM products WHERE category = ?", [$category]) as $row) {
        yield $row;
    }
}

// Serialize nested generators without loading all data into memory
$json = JsonSerializer::encode(getCategories());
```

**Benefits of Generator Serialization:**
- **Constant Memory Usage**: Only one item in memory at a time
- **Database Streaming**: Fetch and serialize rows without buffering
- **Lazy Evaluation**: Generate data on-demand during serialization
- **Large Datasets**: Process millions of records without memory issues

**When to Use Generators:**
- Serializing database query results (1,000+ rows)
- Processing large CSV files or API responses
- Building JSON from expensive computations
- Any scenario where data doesn't fit comfortably in memory

## Performance

The library has been benchmarked against native `json_encode/decode` functions using PHPBench. Benchmarks run on PHP 8.1+ with comparable results across versions.

> **Note**: This library prioritizes features and type safety over raw speed. The performance overhead is justified by capabilities like attribute-based configuration, versioning, transformers, and streaming support. For simple use cases where these features aren't needed, native `json_encode/decode` functions are recommended.

### Serialization Performance

| Data Size | Native | JsonSerializer | Ratio | Notes |
|-----------|--------|----------------|-------|-------|
| Small (<1KB) | 1.0μs | 49.3μs | ~49x slower | Overhead negligible for small data |
| 1MB | 325μs | 20.3ms | ~62x slower | Rich feature set trade-off |
| 5MB | 1.7ms | 101.7ms | ~60x slower | Consistent overhead ratio |
| 10MB | 3.6ms | 204.6ms | ~57x slower | Scales linearly |

### Deserialization Performance

| Data Size | Native | JsonSerializer | Ratio | Notes |
|-----------|--------|----------------|-------|-------|
| 1MB | 942μs | 936μs | **Competitive** | Same speed as native! |
| 5MB | 4.9ms | 4.8ms | **Competitive** | Same speed as native! |
| 10MB | 9.9ms | 10.0ms | **Competitive** | Same speed as native! |

### Object Hydration Performance

| Operation | Time | Notes |
|-----------|------|-------|
| Decode to Class (1MB) | 1.1ms | Minimal overhead for object mapping |
| Decode to Class (5MB) | 5.5ms | Scales linearly with data size |
| Nested Objects | 36μs | Fast recursive hydration |

### Memory Usage

| Operation | Memory Peak | Notes |
|-----------|-------------|-------|
| Native (10MB) | 32.5MB | Baseline |
| JsonSerializer (10MB) | 34.9MB | ~7% overhead |
| Streaming (10MB) | 34.9MB | Constant memory with file operations |

### Key Findings

**Deserialization**: JsonSerializer achieves **native-level performance** for deserialization, making it ideal for API consumption and data processing.

**Serialization**: While slower than native for encoding, the overhead is justified by the rich feature set:
- Attribute-based configuration
- Type transformers
- API versioning
- Virtual properties
- Custom naming strategies

**Memory Efficiency**: Memory usage is comparable to native functions with only ~7% overhead for medium to large datasets.

### Running Benchmarks

```bash
# Run all benchmarks
composer bench

# Run specific benchmark suite
vendor/bin/phpbench run benchmarks/SmallDataBench.php --report=default

# Generate detailed reports
vendor/bin/phpbench run --report=default --store
```

## Troubleshooting

### Common Issues and Solutions

#### Memory Limit Errors

```php
// Problem: Fatal error: Allowed memory size exhausted
// Solution 1: Use streaming for large files
$iterator = JsonSerializer::iterateFile('large-file.json', User::class);
foreach ($iterator as $user) {
    // Process one at a time
}

// Solution 2: Increase max depth limit
$serializer = JsonSerializer::builder()
    ->withMaxDepth(100) // Reduce if hitting memory limits
    ->build();
```

#### Type Mismatch Errors

```php
// Problem: Type mismatch during deserialization
// Solution: Use LENIENT type coercion mode
use Farzai\JsonSerializer\Types\TypeCoercionMode;
use Farzai\JsonSerializer\Engine\DeserializationContext;

$context = new DeserializationContext(
    typeCoercionMode: TypeCoercionMode::LENIENT
);

$deserializer = new DeserializerEngine;
$data = $deserializer->deserializeToClass($json, MyClass::class, $context);
```

#### Circular Reference Errors

```php
// Problem: Circular reference detected
// Solution 1: Fix data structure (recommended)
// Solution 2: Disable detection (use with caution)
$serializer = JsonSerializer::builder()
    ->withCircularReferenceDetection(false)
    ->build();
```

#### Performance Issues

```php
// Problem: Serialization too slow
// Solution 1: Use native functions for simple data
$json = json_encode($simpleArray);

// Solution 2: Enable PSR-16 cache for metadata
$serializer = JsonSerializer::builder()
    ->withCache($psr16Cache)
    ->build();

// Solution 3: Use streaming for large data
JsonSerializer::encodeToFile($data, 'output.json');
```

#### Max Depth Exceeded

```php
// Problem: Maximum depth exceeded
// Solution: Increase max depth limit
$serializer = JsonSerializer::builder()
    ->withMaxDepth(1000) // Default is 512
    ->build();
```

### Performance Optimization Tips

1. **Use PSR-16 Cache**: Cache metadata for classes you serialize frequently
2. **Stream Large Files**: Use `iterateFile()` for files > 100MB
3. **Disable Unused Features**: Turn off circular reference detection if not needed
4. **Use Native for Simple Data**: Reserve this library for complex object mapping
5. **Batch Processing**: Process multiple objects in batches with streaming

### Getting Help

- **GitHub Issues**: [Report bugs or request features](https://github.com/parsilver/json-serializer-php/issues)
- **Documentation**: Check the examples in this README
- **Stack Overflow**: Tag your question with `farzai-json-serializer`

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/parsilver/json-serializer-php/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [parsilver](https://github.com/parsilver)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
