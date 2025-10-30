<?php

use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Engine\SerializerEngine;
use Farzai\JsonSerializer\Events\EventDispatcher;
use Farzai\JsonSerializer\Events\PostDeserializeEvent;
use Farzai\JsonSerializer\Events\PostSerializeEvent;
use Farzai\JsonSerializer\Events\PreDeserializeEvent;
use Farzai\JsonSerializer\Events\PreSerializeEvent;

describe('Event System Tests', function () {
    describe('Pre-Serialize Event', function () {
        it('dispatches pre-serialize event before serialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            $called = false;
            $capturedValue = null;

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$called, &$capturedValue) {
                $called = true;
                $capturedValue = $event->getValue();
            });

            $data = ['key' => 'value'];
            $engine->serialize($data);

            expect($called)->toBeTrue()
                ->and($capturedValue)->toBe($data);
        });

        it('allows modifying value before serialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            // Transform the value before serialization
            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) {
                $value = $event->getValue();
                $event->setValue(['modified' => true, 'original' => $value]);
            });

            $json = $engine->serialize(['key' => 'value']);

            expect($json)->toContain('"modified":true')
                ->and($json)->toContain('"original"');
        });
    });

    describe('Post-Serialize Event', function () {
        it('dispatches post-serialize event after serialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            $called = false;
            $capturedJson = null;

            $eventDispatcher->addListener(PostSerializeEvent::class, function (PostSerializeEvent $event) use (&$called, &$capturedJson) {
                $called = true;
                $capturedJson = $event->getJson();
            });

            $data = ['key' => 'value'];
            $json = $engine->serialize($data);

            expect($called)->toBeTrue()
                ->and($capturedJson)->toBe($json);
        });

        it('allows modifying JSON after serialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            // Add metadata to JSON output
            $eventDispatcher->addListener(PostSerializeEvent::class, function (PostSerializeEvent $event) {
                $json = $event->getJson();
                $data = json_decode($json, true);
                $data['_metadata'] = ['timestamp' => time()];
                $event->setJson(json_encode($data));
            });

            $json = $engine->serialize(['key' => 'value']);

            expect($json)->toContain('"_metadata"')
                ->and($json)->toContain('"timestamp"');
        });
    });

    describe('Pre-Deserialize Event', function () {
        it('dispatches pre-deserialize event before deserialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new DeserializerEngine(eventDispatcher: $eventDispatcher);

            $called = false;
            $capturedJson = null;

            $eventDispatcher->addListener(PreDeserializeEvent::class, function (PreDeserializeEvent $event) use (&$called, &$capturedJson) {
                $called = true;
                $capturedJson = $event->getJson();
            });

            $testClass = new class
            {
                public string $key;
            };

            $json = json_encode(['key' => 'value']);
            $engine->deserializeToClass($json, get_class($testClass));

            expect($called)->toBeTrue()
                ->and($capturedJson)->toBe($json);
        });

        it('allows modifying JSON before deserialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new DeserializerEngine(eventDispatcher: $eventDispatcher);

            // Inject additional data before deserialization
            $eventDispatcher->addListener(PreDeserializeEvent::class, function (PreDeserializeEvent $event) {
                $data = json_decode($event->getJson(), true);
                $data['injected'] = 'value';
                $event->setJson(json_encode($data));
            });

            $testClass = new class
            {
                public string $key;

                public ?string $injected = null;
            };

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($result->key)->toBe('value')
                ->and($result->injected)->toBe('value');
        });
    });

    describe('Post-Deserialize Event', function () {
        it('dispatches post-deserialize event after deserialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new DeserializerEngine(eventDispatcher: $eventDispatcher);

            $called = false;
            $capturedResult = null;

            $eventDispatcher->addListener(PostDeserializeEvent::class, function (PostDeserializeEvent $event) use (&$called, &$capturedResult) {
                $called = true;
                $capturedResult = $event->getResult();
            });

            $testClass = new class
            {
                public string $key;
            };

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($called)->toBeTrue()
                ->and($capturedResult)->toBe($result);
        });

        it('allows modifying result after deserialization', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new DeserializerEngine(eventDispatcher: $eventDispatcher);

            // Add a computed property after deserialization
            $eventDispatcher->addListener(PostDeserializeEvent::class, function (PostDeserializeEvent $event) {
                $result = $event->getResult();
                if (property_exists($result, 'computed')) {
                    $result->computed = 'modified';
                }
            });

            $testClass = new class
            {
                public string $key;

                public ?string $computed = null;
            };

            $json = json_encode(['key' => 'value']);
            $result = $engine->deserializeToClass($json, get_class($testClass));

            expect($result->computed)->toBe('modified');
        });
    });

    describe('Event Propagation', function () {
        it('allows stopping event propagation', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            $firstCalled = false;
            $secondCalled = false;

            // First listener stops propagation
            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$firstCalled) {
                $firstCalled = true;
                $event->stopPropagation();
            });

            // Second listener should NOT be called
            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$secondCalled) {
                $secondCalled = true;
            });

            $engine->serialize(['key' => 'value']);

            expect($firstCalled)->toBeTrue()
                ->and($secondCalled)->toBeFalse();
        });
    });

    describe('Multiple Listeners', function () {
        it('calls multiple listeners in order', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            $callOrder = [];

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$callOrder) {
                $callOrder[] = 'listener1';
            });

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$callOrder) {
                $callOrder[] = 'listener2';
            });

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$callOrder) {
                $callOrder[] = 'listener3';
            });

            $engine->serialize(['key' => 'value']);

            expect($callOrder)->toBe(['listener1', 'listener2', 'listener3']);
        });

        it('respects listener priority order', function () {
            $eventDispatcher = new EventDispatcher;
            $engine = new SerializerEngine(eventDispatcher: $eventDispatcher);

            $callOrder = [];

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$callOrder) {
                $callOrder[] = 'low';
            }, priority: 1);

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$callOrder) {
                $callOrder[] = 'high';
            }, priority: 100);

            $eventDispatcher->addListener(PreSerializeEvent::class, function (PreSerializeEvent $event) use (&$callOrder) {
                $callOrder[] = 'medium';
            }, priority: 50);

            $engine->serialize(['key' => 'value']);

            expect($callOrder)->toBe(['high', 'medium', 'low']);
        });
    });

    describe('EventDispatcher', function () {
        it('can check if listeners exist for an event', function () {
            $eventDispatcher = new EventDispatcher;

            expect($eventDispatcher->hasListeners(PreSerializeEvent::class))->toBeFalse();

            $eventDispatcher->addListener(PreSerializeEvent::class, fn () => null);

            expect($eventDispatcher->hasListeners(PreSerializeEvent::class))->toBeTrue();
        });

        it('can remove listeners for a specific event', function () {
            $eventDispatcher = new EventDispatcher;

            $eventDispatcher->addListener(PreSerializeEvent::class, fn () => null);
            $eventDispatcher->addListener(PostSerializeEvent::class, fn () => null);

            expect($eventDispatcher->hasListeners(PreSerializeEvent::class))->toBeTrue()
                ->and($eventDispatcher->hasListeners(PostSerializeEvent::class))->toBeTrue();

            $eventDispatcher->removeListeners(PreSerializeEvent::class);

            expect($eventDispatcher->hasListeners(PreSerializeEvent::class))->toBeFalse()
                ->and($eventDispatcher->hasListeners(PostSerializeEvent::class))->toBeTrue();
        });

        it('can clear all listeners', function () {
            $eventDispatcher = new EventDispatcher;

            $eventDispatcher->addListener(PreSerializeEvent::class, fn () => null);
            $eventDispatcher->addListener(PostSerializeEvent::class, fn () => null);

            expect($eventDispatcher->hasListeners(PreSerializeEvent::class))->toBeTrue()
                ->and($eventDispatcher->hasListeners(PostSerializeEvent::class))->toBeTrue();

            $eventDispatcher->clearListeners();

            expect($eventDispatcher->hasListeners(PreSerializeEvent::class))->toBeFalse()
                ->and($eventDispatcher->hasListeners(PostSerializeEvent::class))->toBeFalse();
        });

        it('can count listeners for an event', function () {
            $eventDispatcher = new EventDispatcher;

            expect($eventDispatcher->getListenerCount(PreSerializeEvent::class))->toBe(0);

            $eventDispatcher->addListener(PreSerializeEvent::class, fn () => null);
            expect($eventDispatcher->getListenerCount(PreSerializeEvent::class))->toBe(1);

            $eventDispatcher->addListener(PreSerializeEvent::class, fn () => null);
            expect($eventDispatcher->getListenerCount(PreSerializeEvent::class))->toBe(2);
        });
    });
});
