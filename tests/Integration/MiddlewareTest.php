<?php

use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\SerializationContext;
use Farzai\JsonSerializer\Engine\SerializerEngine;
use Farzai\JsonSerializer\Middleware\DeserializationMiddleware;
use Farzai\JsonSerializer\Middleware\DeserializationMiddlewareChain;
use Farzai\JsonSerializer\Middleware\SerializationMiddleware;
use Farzai\JsonSerializer\Middleware\SerializationMiddlewareChain;

describe('Middleware System Tests', function () {
    describe('Serialization Middleware', function () {
        it('executes middleware during serialization', function () {
            $engine = new SerializerEngine;
            $called = false;

            $middleware = new class($called) implements SerializationMiddleware
            {
                public function __construct(private bool &$called) {}

                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    $this->called = true;

                    return $next($value, $context);
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $result = $engine->serialize(['key' => 'value']);

            expect($called)->toBeTrue()
                ->and($result)->toContain('"key"')
                ->and($result)->toContain('"value"');
        });

        it('allows middleware to modify value before serialization', function () {
            $engine = new SerializerEngine;

            $middleware = new class implements SerializationMiddleware
            {
                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    // Add prefix to all string values
                    $value['modified'] = true;

                    return $next($value, $context);
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $result = $engine->serialize(['key' => 'value']);

            expect($result)->toContain('"modified"')
                ->and($result)->toContain('true');
        });

        it('allows middleware to modify JSON after serialization', function () {
            $engine = new SerializerEngine;

            $middleware = new class implements SerializationMiddleware
            {
                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    $json = $next($value, $context);

                    // Add metadata wrapper
                    $data = json_decode($json, true);
                    $wrapped = [
                        'data' => $data,
                        'meta' => ['version' => '1.0'],
                    ];

                    return json_encode($wrapped);
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $result = $engine->serialize(['key' => 'value']);
            $decoded = json_decode($result, true);

            expect($decoded)->toHaveKey('data')
                ->and($decoded)->toHaveKey('meta')
                ->and($decoded['meta']['version'])->toBe('1.0')
                ->and($decoded['data']['key'])->toBe('value');
        });

        it('executes multiple middleware in order', function () {
            $engine = new SerializerEngine;
            $callOrder = [];

            $middleware1 = new class($callOrder) implements SerializationMiddleware
            {
                public function __construct(private array &$callOrder) {}

                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    $this->callOrder[] = 'before-1';
                    $result = $next($value, $context);
                    $this->callOrder[] = 'after-1';

                    return $result;
                }
            };

            $middleware2 = new class($callOrder) implements SerializationMiddleware
            {
                public function __construct(private array &$callOrder) {}

                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    $this->callOrder[] = 'before-2';
                    $result = $next($value, $context);
                    $this->callOrder[] = 'after-2';

                    return $result;
                }
            };

            $engine->getMiddlewareChain()->add($middleware1)->add($middleware2);

            $engine->serialize(['key' => 'value']);

            expect($callOrder)->toBe(['before-1', 'before-2', 'after-2', 'after-1']);
        });

        it('allows middleware to short-circuit the chain', function () {
            $engine = new SerializerEngine;
            $secondCalled = false;

            $middleware1 = new class implements SerializationMiddleware
            {
                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    // Short-circuit - don't call $next
                    return '{"cached":true}';
                }
            };

            $middleware2 = new class($secondCalled) implements SerializationMiddleware
            {
                public function __construct(private bool &$secondCalled) {}

                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    $this->secondCalled = true;

                    return $next($value, $context);
                }
            };

            $engine->getMiddlewareChain()->add($middleware1)->add($middleware2);

            $result = $engine->serialize(['key' => 'value']);

            expect($result)->toBe('{"cached":true}')
                ->and($secondCalled)->toBeFalse();
        });

        it('can check middleware chain state', function () {
            $chain = new SerializationMiddlewareChain;

            expect($chain->isEmpty())->toBeTrue()
                ->and($chain->count())->toBe(0);

            $middleware = new class implements SerializationMiddleware
            {
                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    return $next($value, $context);
                }
            };

            $chain->add($middleware);

            expect($chain->isEmpty())->toBeFalse()
                ->and($chain->count())->toBe(1);

            $chain->clear();

            expect($chain->isEmpty())->toBeTrue()
                ->and($chain->count())->toBe(0);
        });
    });

    describe('Deserialization Middleware', function () {
        it('executes middleware during deserialization', function () {
            $engine = new DeserializerEngine;
            $called = false;

            $middleware = new class($called) implements DeserializationMiddleware
            {
                public function __construct(private bool &$called) {}

                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    $this->called = true;

                    return $next($json, $className, $context);
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $testClass = new class
            {
                public string $key;
            };

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($called)->toBeTrue()
                ->and($result->key)->toBe('value');
        });

        it('allows middleware to modify JSON before deserialization', function () {
            $engine = new DeserializerEngine;

            $middleware = new class implements DeserializationMiddleware
            {
                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    // Inject additional data
                    $data = json_decode($json, true);
                    $data['injected'] = 'from-middleware';
                    $json = json_encode($data);

                    return $next($json, $className, $context);
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $testClass = new class
            {
                public string $key;

                public string $injected = '';
            };

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($result->key)->toBe('value')
                ->and($result->injected)->toBe('from-middleware');
        });

        it('allows middleware to modify result after deserialization', function () {
            $engine = new DeserializerEngine;

            $middleware = new class implements DeserializationMiddleware
            {
                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    $result = $next($json, $className, $context);

                    // Modify result after deserialization
                    if (property_exists($result, 'computed')) {
                        $result->computed = 'middleware-computed';
                    }

                    return $result;
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $testClass = new class
            {
                public string $key;

                public string $computed = '';
            };

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($result->key)->toBe('value')
                ->and($result->computed)->toBe('middleware-computed');
        });

        it('executes multiple middleware in order', function () {
            $engine = new DeserializerEngine;
            $callOrder = [];

            $middleware1 = new class($callOrder) implements DeserializationMiddleware
            {
                public function __construct(private array &$callOrder) {}

                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    $this->callOrder[] = 'before-1';
                    $result = $next($json, $className, $context);
                    $this->callOrder[] = 'after-1';

                    return $result;
                }
            };

            $middleware2 = new class($callOrder) implements DeserializationMiddleware
            {
                public function __construct(private array &$callOrder) {}

                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    $this->callOrder[] = 'before-2';
                    $result = $next($json, $className, $context);
                    $this->callOrder[] = 'after-2';

                    return $result;
                }
            };

            $engine->getMiddlewareChain()->add($middleware1)->add($middleware2);

            $testClass = new class
            {
                public string $key;
            };

            $json = json_encode(['key' => 'value']);
            $engine->deserializeToClass($json, get_class($testClass));

            expect($callOrder)->toBe(['before-1', 'before-2', 'after-2', 'after-1']);
        });

        it('allows middleware to short-circuit the chain', function () {
            $engine = new DeserializerEngine;
            $secondCalled = false;

            $testClass = new class
            {
                public string $key;
            };

            $cachedResult = new $testClass;
            $cachedResult->key = 'cached';

            $middleware1 = new class($cachedResult) implements DeserializationMiddleware
            {
                public function __construct(private object $cachedResult) {}

                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    // Return cached result without calling $next
                    return $this->cachedResult;
                }
            };

            $middleware2 = new class($secondCalled) implements DeserializationMiddleware
            {
                public function __construct(private bool &$secondCalled) {}

                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    $this->secondCalled = true;

                    return $next($json, $className, $context);
                }
            };

            $engine->getMiddlewareChain()->add($middleware1)->add($middleware2);

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($result->key)->toBe('cached')
                ->and($secondCalled)->toBeFalse();
        });

        it('can check middleware chain state', function () {
            $chain = new DeserializationMiddlewareChain;

            expect($chain->isEmpty())->toBeTrue()
                ->and($chain->count())->toBe(0);

            $middleware = new class implements DeserializationMiddleware
            {
                public function handle(string $json, string $className, \Farzai\JsonSerializer\Engine\DeserializationContext $context, callable $next): object
                {
                    return $next($json, $className, $context);
                }
            };

            $chain->add($middleware);

            expect($chain->isEmpty())->toBeFalse()
                ->and($chain->count())->toBe(1);

            $chain->clear();

            expect($chain->isEmpty())->toBeTrue()
                ->and($chain->count())->toBe(0);
        });
    });

    describe('Middleware with Events', function () {
        it('middleware wraps around events', function () {
            $engine = new SerializerEngine;
            $executionOrder = [];

            // Add event listener
            $engine->getEventDispatcher()->addListener(
                \Farzai\JsonSerializer\Events\PreSerializeEvent::class,
                function () use (&$executionOrder) {
                    $executionOrder[] = 'event';
                }
            );

            // Add middleware
            $middleware = new class($executionOrder) implements SerializationMiddleware
            {
                public function __construct(private array &$executionOrder) {}

                public function handle(mixed $value, SerializationContext $context, callable $next): string
                {
                    $this->executionOrder[] = 'middleware-before';
                    $result = $next($value, $context);
                    $this->executionOrder[] = 'middleware-after';

                    return $result;
                }
            };

            $engine->getMiddlewareChain()->add($middleware);

            $engine->serialize(['key' => 'value']);

            // Middleware should wrap around the entire process including events
            expect($executionOrder)->toBe(['middleware-before', 'event', 'middleware-after']);
        });
    });
});
