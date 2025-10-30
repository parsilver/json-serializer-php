<?php

use Farzai\JsonSerializer\Attributes\Ignore;
use Farzai\JsonSerializer\Attributes\NamingStrategy;
use Farzai\JsonSerializer\Attributes\SerializedName;
use Farzai\JsonSerializer\JsonSerializer;

describe('Attribute Integration Tests', function () {
    it('can use SerializedName attribute', function () {
        $data = new class
        {
            #[SerializedName('user_id')]
            public int $id = 123;

            #[SerializedName('user_name')]
            public string $name = 'John Doe';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"user_id":123')
            ->and($json)->toContain('"user_name":"John Doe"')
            ->and($json)->not->toContain('"id"')
            ->and($json)->not->toContain('"name"');
    });

    it('can ignore properties with Ignore attribute', function () {
        $data = new class
        {
            public string $username = 'john';

            #[Ignore]
            public string $password = 'secret123';

            public string $email = 'john@example.com';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"username":"john"')
            ->and($json)->toContain('"email":"john@example.com"')
            ->and($json)->not->toContain('password')
            ->and($json)->not->toContain('secret123');
    });

    it('can apply snake_case naming strategy at class level', function () {
        $data = new #[NamingStrategy('snake_case')] class
        {
            public string $firstName = 'John';

            public string $lastName = 'Doe';

            public string $emailAddress = 'john@example.com';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"first_name":"John"')
            ->and($json)->toContain('"last_name":"Doe"')
            ->and($json)->toContain('"email_address":"john@example.com"')
            ->and($json)->not->toContain('"firstName"');
    });

    it('can apply camelCase naming strategy', function () {
        $data = new #[NamingStrategy('camelCase')] class
        {
            public string $first_name = 'John';

            public string $last_name = 'Doe';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"firstName":"John"')
            ->and($json)->toContain('"lastName":"Doe"')
            ->and($json)->not->toContain('"first_name"');
    });

    it('can apply PascalCase naming strategy', function () {
        $data = new #[NamingStrategy('PascalCase')] class
        {
            public string $firstName = 'John';

            public string $lastName = 'Doe';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"FirstName":"John"')
            ->and($json)->toContain('"LastName":"Doe"');
    });

    it('can apply kebab-case naming strategy', function () {
        $data = new #[NamingStrategy('kebab-case')] class
        {
            public string $firstName = 'John';

            public string $emailAddress = 'john@example.com';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"first-name":"John"')
            ->and($json)->toContain('"email-address":"john@example.com"');
    });

    it('SerializedName takes precedence over naming strategy', function () {
        $data = new #[NamingStrategy('snake_case')] class
        {
            public string $firstName = 'John';

            #[SerializedName('custom_lastname')]
            public string $lastName = 'Doe';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"first_name":"John"')
            ->and($json)->toContain('"custom_lastname":"Doe"')
            ->and($json)->not->toContain('"last_name"');
    });

    it('can combine multiple attributes', function () {
        $data = new #[NamingStrategy('snake_case')] class
        {
            #[SerializedName('id')]
            public int $userId = 123;

            public string $firstName = 'John';

            #[Ignore]
            public string $internalData = 'secret';

            public string $emailAddress = 'john@example.com';
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"id":123')
            ->and($json)->toContain('"first_name":"John"')
            ->and($json)->toContain('"email_address":"john@example.com"')
            ->and($json)->not->toContain('userId')
            ->and($json)->not->toContain('internalData')
            ->and($json)->not->toContain('secret');
    });
});
