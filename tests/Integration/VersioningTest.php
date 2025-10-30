<?php

use Farzai\JsonSerializer\Attributes\Since;
use Farzai\JsonSerializer\Attributes\Until;
use Farzai\JsonSerializer\JsonSerializer;

describe('Versioning Integration Tests', function () {
    it('includes properties marked with Since when version matches', function () {
        $data = new class
        {
            public string $name = 'John';

            #[Since('2.0')]
            public ?string $middleName = 'Michael';

            public string $email = 'john@example.com';
        };

        // Serialize with version 2.0
        $engine = JsonSerializer::builder()
            ->withVersion('2.0')
            ->build();

        $json = $engine->serialize($data);

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"middleName":"Michael"')
            ->and($json)->toContain('"email":"john@example.com"');
    });

    it('excludes properties marked with Since when version is lower', function () {
        $data = new class
        {
            public string $name = 'John';

            #[Since('2.0')]
            public ?string $middleName = 'Michael';

            public string $email = 'john@example.com';
        };

        // Serialize with version 1.0
        $engine = JsonSerializer::builder()
            ->withVersion('1.0')
            ->build();

        $json = $engine->serialize($data);

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"email":"john@example.com"')
            ->and($json)->not->toContain('middleName')
            ->and($json)->not->toContain('Michael');
    });

    it('excludes properties marked with Until when version matches or higher', function () {
        $data = new class
        {
            public string $name = 'John';

            #[Until('2.0')]
            public ?string $legacyId = 'old-123';

            public string $email = 'john@example.com';
        };

        // Serialize with version 2.0 (should exclude)
        $engine = JsonSerializer::builder()
            ->withVersion('2.0')
            ->build();

        $json = $engine->serialize($data);

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"email":"john@example.com"')
            ->and($json)->not->toContain('legacyId');
    });

    it('includes properties marked with Until when version is lower', function () {
        $data = new class
        {
            public string $name = 'John';

            #[Until('2.0')]
            public ?string $legacyId = 'old-123';

            public string $email = 'john@example.com';
        };

        // Serialize with version 1.0 (should include)
        $engine = JsonSerializer::builder()
            ->withVersion('1.0')
            ->build();

        $json = $engine->serialize($data);

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"legacyId":"old-123"')
            ->and($json)->toContain('"email":"john@example.com"');
    });

    it('can combine Since and Until on different properties', function () {
        $data = new class
        {
            public string $name = 'John';

            #[Since('2.0')]
            public ?string $newField = 'new-value';

            #[Until('2.0')]
            public ?string $oldField = 'old-value';

            public string $email = 'john@example.com';
        };

        // Version 1.0: should have oldField, not newField
        $engine1 = JsonSerializer::builder()
            ->withVersion('1.0')
            ->build();

        $json1 = $engine1->serialize($data);

        expect($json1)->toContain('"oldField":"old-value"')
            ->and($json1)->not->toContain('newField');

        // Version 2.0: should have newField, not oldField
        $engine2 = JsonSerializer::builder()
            ->withVersion('2.0')
            ->build();

        $json2 = $engine2->serialize($data);

        expect($json2)->toContain('"newField":"new-value"')
            ->and($json2)->not->toContain('oldField');
    });

    it('includes all properties when no version is specified', function () {
        $data = new class
        {
            public string $name = 'John';

            #[Since('2.0')]
            public ?string $newField = 'new-value';

            #[Until('2.0')]
            public ?string $oldField = 'old-value';
        };

        // No version specified - should include all
        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"name":"John"')
            ->and($json)->toContain('"newField":"new-value"')
            ->and($json)->toContain('"oldField":"old-value"');
    });

    it('handles version ranges correctly', function () {
        $data = new class
        {
            #[Since('1.5'), Until('2.5')]
            public string $temporaryFeature = 'temp';

            public string $name = 'John';
        };

        // Version 1.0 - before Since
        $json1 = JsonSerializer::builder()->withVersion('1.0')->build()->serialize($data);
        expect($json1)->not->toContain('temporaryFeature');

        // Version 2.0 - within range
        $json2 = JsonSerializer::builder()->withVersion('2.0')->build()->serialize($data);
        expect($json2)->toContain('"temporaryFeature":"temp"');

        // Version 3.0 - after Until
        $json3 = JsonSerializer::builder()->withVersion('3.0')->build()->serialize($data);
        expect($json3)->not->toContain('temporaryFeature');
    });
});
