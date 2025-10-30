<?php

use Farzai\JsonSerializer\Attributes\Ignore;
use Farzai\JsonSerializer\Attributes\VirtualProperty;
use Farzai\JsonSerializer\JsonSerializer;

describe('Virtual Properties Integration Tests', function () {
    it('can serialize virtual properties from methods', function () {
        $data = new class
        {
            public string $firstName = 'John';

            public string $lastName = 'Doe';

            #[VirtualProperty('full_name')]
            public function getFullName(): string
            {
                return "{$this->firstName} {$this->lastName}";
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"firstName":"John"')
            ->and($json)->toContain('"lastName":"Doe"')
            ->and($json)->toContain('"full_name":"John Doe"');
    });

    it('uses method name as property name when not specified', function () {
        $data = new class
        {
            public int $quantity = 5;

            public float $price = 10.50;

            #[VirtualProperty]
            public function getTotalPrice(): float
            {
                return $this->quantity * $this->price;
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"quantity":5')
            ->and($json)->toContain('"price":10.5')
            ->and($json)->toContain('"getTotalPrice":52.5');
    });

    it('can combine virtual properties with regular properties', function () {
        $data = new class
        {
            public string $username = 'johndoe';

            #[Ignore]
            public string $password = 'secret';

            public string $email = 'john@example.com';

            #[VirtualProperty('is_active')]
            public function isActive(): bool
            {
                return true;
            }

            #[VirtualProperty('created_ago')]
            public function getCreatedAgo(): string
            {
                return '2 days ago';
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"username":"johndoe"')
            ->and($json)->toContain('"email":"john@example.com"')
            ->and($json)->toContain('"is_active":true')
            ->and($json)->toContain('"created_ago":"2 days ago"')
            ->and($json)->not->toContain('password');
    });

    it('can serialize complex virtual property values', function () {
        $data = new class
        {
            public array $items = ['apple', 'banana', 'orange'];

            #[VirtualProperty('item_count')]
            public function getItemCount(): int
            {
                return count($this->items);
            }

            #[VirtualProperty('first_item')]
            public function getFirstItem(): ?string
            {
                return $this->items[0] ?? null;
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"item_count":3')
            ->and($json)->toContain('"first_item":"apple"');
    });

    it('handles virtual properties that return objects', function () {
        $data = new class
        {
            public string $name = 'John';

            #[VirtualProperty('metadata')]
            public function getMetadata(): object
            {
                $meta = new stdClass;
                $meta->version = '1.0';
                $meta->type = 'user';

                return $meta;
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"metadata"')
            ->and($json)->toContain('"version":"1.0"')
            ->and($json)->toContain('"type":"user"');
    });

    it('handles virtual properties that return arrays', function () {
        $data = new class
        {
            public string $name = 'Product';

            #[VirtualProperty('tags')]
            public function getTags(): array
            {
                return ['electronics', 'gadget', 'new'];
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"name":"Product"')
            ->and($json)->toContain('"tags":["electronics","gadget","new"]');
    });
});
