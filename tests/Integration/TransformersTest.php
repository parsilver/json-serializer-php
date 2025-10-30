<?php

use Farzai\JsonSerializer\Attributes\DateFormat;
use Farzai\JsonSerializer\Attributes\Transformer;
use Farzai\JsonSerializer\JsonSerializer;
use Farzai\JsonSerializer\Transformers\DateTimeTransformer;

describe('Transformer Integration Tests', function () {
    it('can use DateFormat attribute for DateTime', function () {
        $data = new class
        {
            #[DateFormat('Y-m-d')]
            public DateTime $birthDate;

            public function __construct()
            {
                $this->birthDate = new DateTime('1990-05-15');
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"birthDate":"1990-05-15"')
            ->and($json)->not->toContain('T');
    });

    it('can use DateFormat with custom format', function () {
        $data = new class
        {
            #[DateFormat('d/m/Y H:i')]
            public DateTime $createdAt;

            public function __construct()
            {
                $this->createdAt = new DateTime('2024-01-15 14:30:00');
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"createdAt":"15/01/2024 14:30"');
    });

    it('can use ISO8601 date format constant', function () {
        $data = new class
        {
            #[DateFormat(DateFormat::ISO8601)]
            public DateTime $timestamp;

            public function __construct()
            {
                $this->timestamp = new DateTime('2024-01-15 14:30:00', new DateTimeZone('UTC'));
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"timestamp"')
            ->and($json)->toContain('2024-01-15T14:30:00');
    });

    it('can use Transformer attribute explicitly', function () {
        $data = new class
        {
            #[Transformer(DateTimeTransformer::class, options: ['format' => 'Y-m-d'])]
            public DateTime $date;

            public function __construct()
            {
                $this->date = new DateTime('2024-01-15');
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"date":"2024-01-15"');
    });

    it('handles DateTime without attributes using default format', function () {
        $data = new class
        {
            public DateTime $timestamp;

            public function __construct()
            {
                $this->timestamp = new DateTime('2024-01-15 14:30:00', new DateTimeZone('UTC'));
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"timestamp"')
            ->and($json)->toContain('2024-01-15T14:30:00');
    });

    it('can handle DateTimeImmutable', function () {
        $data = new class
        {
            #[DateFormat('Y-m-d')]
            public DateTimeImmutable $date;

            public function __construct()
            {
                $this->date = new DateTimeImmutable('2024-01-15');
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"date":"2024-01-15"');
    });

    it('can serialize PHP 8.1+ BackedEnum', function () {
        enum Status: string
        {
            case ACTIVE = 'active';
            case INACTIVE = 'inactive';
            case PENDING = 'pending';
        }

        $data = new class
        {
            public Status $status;

            public function __construct()
            {
                $this->status = Status::ACTIVE;
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"status":"active"');
    });

    it('can serialize PHP 8.1+ BackedEnum with int values', function () {
        enum Priority: int
        {
            case LOW = 1;
            case MEDIUM = 5;
            case HIGH = 10;
        }

        $data = new class
        {
            public Priority $priority;

            public function __construct()
            {
                $this->priority = Priority::HIGH;
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"priority":10');
    });

    it('can serialize PHP 8.1+ UnitEnum', function () {
        enum Color
        {
            case RED;
            case GREEN;
            case BLUE;
        }

        $data = new class
        {
            public Color $color;

            public function __construct()
            {
                $this->color = Color::RED;
            }
        };

        $json = JsonSerializer::encode($data);

        expect($json)->toContain('"color":"RED"');
    });
});
