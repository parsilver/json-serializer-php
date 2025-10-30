<?php

use Farzai\JsonSerializer\Attributes\DateFormat;
use Farzai\JsonSerializer\Attributes\SerializedName;
use Farzai\JsonSerializer\Attributes\Type;
use Farzai\JsonSerializer\JsonSerializer;

describe('Deserialization Integration Tests', function () {
    it('can deserialize simple JSON to PHP value', function () {
        $json = '{"name":"John","age":30}';

        $result = JsonSerializer::decode($json);

        expect($result)->toBeArray()
            ->and($result['name'])->toBe('John')
            ->and($result['age'])->toBe(30);
    });

    it('can deserialize JSON to a class', function () {
        class SimpleUser
        {
            public string $name;

            public int $age;
        }

        $json = '{"name":"John","age":30}';

        $user = JsonSerializer::decodeToClass($json, SimpleUser::class);

        expect($user)->toBeInstanceOf(SimpleUser::class)
            ->and($user->name)->toBe('John')
            ->and($user->age)->toBe(30);
    });

    it('can deserialize JSON with SerializedName attribute', function () {
        class UserWithAlias
        {
            #[SerializedName('user_id')]
            public int $id;

            #[SerializedName('user_name')]
            public string $name;
        }

        $json = '{"user_id":123,"user_name":"John"}';

        $user = JsonSerializer::decodeToClass($json, UserWithAlias::class);

        expect($user)->toBeInstanceOf(UserWithAlias::class)
            ->and($user->id)->toBe(123)
            ->and($user->name)->toBe('John');
    });

    it('can deserialize JSON with DateTime using DateFormat', function () {
        class EventWithDate
        {
            public string $name;

            #[DateFormat('Y-m-d')]
            public DateTime $date;
        }

        $json = '{"name":"Conference","date":"2024-05-15"}';

        $event = JsonSerializer::decodeToClass($json, EventWithDate::class);

        expect($event)->toBeInstanceOf(EventWithDate::class)
            ->and($event->name)->toBe('Conference')
            ->and($event->date)->toBeInstanceOf(DateTime::class)
            ->and($event->date->format('Y-m-d'))->toBe('2024-05-15');
    });

    it('can deserialize JSON array to array of objects', function () {
        class Product
        {
            public string $name;

            public float $price;
        }

        $json = '[{"name":"Apple","price":1.50},{"name":"Banana","price":0.75}]';

        $products = JsonSerializer::decodeArray($json, Product::class);

        expect($products)->toBeArray()
            ->and($products)->toHaveCount(2)
            ->and($products[0])->toBeInstanceOf(Product::class)
            ->and($products[0]->name)->toBe('Apple')
            ->and($products[0]->price)->toBe(1.50)
            ->and($products[1]->name)->toBe('Banana')
            ->and($products[1]->price)->toBe(0.75);
    });

    it('can round-trip serialize and deserialize', function () {
        class RoundTripUser
        {
            public function __construct(
                public string $name = '',
                public int $age = 0,
                public string $email = ''
            ) {
            }
        }

        $original = new RoundTripUser('John Doe', 30, 'john@example.com');

        // Serialize
        $json = JsonSerializer::encode($original);

        // Deserialize
        $restored = JsonSerializer::decodeToClass($json, RoundTripUser::class);

        expect($restored)->toBeInstanceOf(RoundTripUser::class)
            ->and($restored->name)->toBe($original->name)
            ->and($restored->age)->toBe($original->age)
            ->and($restored->email)->toBe($original->email);
    });

    it('can deserialize nested objects', function () {
        class Address
        {
            public string $street;

            public string $city;
        }

        class UserWithAddress
        {
            public string $name;

            public Address $address;
        }

        $json = '{"name":"John","address":{"street":"123 Main St","city":"NYC"}}';

        $user = JsonSerializer::decodeToClass($json, UserWithAddress::class);

        expect($user)->toBeInstanceOf(UserWithAddress::class)
            ->and($user->name)->toBe('John')
            ->and($user->address)->toBeInstanceOf(Address::class)
            ->and($user->address->street)->toBe('123 Main St')
            ->and($user->address->city)->toBe('NYC');
    });

    it('handles missing optional properties gracefully', function () {
        class UserWithOptional
        {
            public string $name;

            public ?string $middleName = null;

            public ?int $age = null;
        }

        $json = '{"name":"John"}';

        $user = JsonSerializer::decodeToClass($json, UserWithOptional::class);

        expect($user)->toBeInstanceOf(UserWithOptional::class)
            ->and($user->name)->toBe('John')
            ->and($user->middleName)->toBeNull()
            ->and($user->age)->toBeNull();
    });

    it('can deserialize scalar types correctly', function () {
        class TypedData
        {
            public string $text;

            public int $number;

            public float $decimal;

            public bool $flag;
        }

        $json = '{"text":"hello","number":42,"decimal":3.14,"flag":true}';

        $data = JsonSerializer::decodeToClass($json, TypedData::class);

        expect($data)->toBeInstanceOf(TypedData::class)
            ->and($data->text)->toBe('hello')
            ->and($data->number)->toBe(42)
            ->and($data->decimal)->toBe(3.14)
            ->and($data->flag)->toBeTrue();
    });

    it('can deserialize arrays of primitive types', function () {
        class DataWithArrays
        {
            public string $name;

            public array $tags;

            public array $numbers;
        }

        $json = '{"name":"Item","tags":["new","sale","featured"],"numbers":[1,2,3,4,5]}';

        $data = JsonSerializer::decodeToClass($json, DataWithArrays::class);

        expect($data)->toBeInstanceOf(DataWithArrays::class)
            ->and($data->name)->toBe('Item')
            ->and($data->tags)->toBe(['new', 'sale', 'featured'])
            ->and($data->numbers)->toBe([1, 2, 3, 4, 5]);
    });

    it('can deserialize array<Type> collections', function () {
        class Author
        {
            public string $name;

            public string $email;
        }

        class Book
        {
            public string $title;

            #[Type('array<Author>')]
            public array $authors;
        }

        $json = '{"title":"PHP Guide","authors":[{"name":"John","email":"john@example.com"},{"name":"Jane","email":"jane@example.com"}]}';

        $book = JsonSerializer::decodeToClass($json, Book::class);

        expect($book)->toBeInstanceOf(Book::class)
            ->and($book->title)->toBe('PHP Guide')
            ->and($book->authors)->toBeArray()
            ->and($book->authors)->toHaveCount(2)
            ->and($book->authors[0])->toBeInstanceOf(Author::class)
            ->and($book->authors[0]->name)->toBe('John')
            ->and($book->authors[0]->email)->toBe('john@example.com')
            ->and($book->authors[1])->toBeInstanceOf(Author::class)
            ->and($book->authors[1]->name)->toBe('Jane');
    });

    it('can deserialize deeply nested objects', function () {
        class Country
        {
            public string $name;
        }

        class City
        {
            public string $name;

            public Country $country;
        }

        class Address
        {
            public string $street;

            public City $city;
        }

        class Company
        {
            public string $name;

            public Address $address;
        }

        $json = '{"name":"Acme Corp","address":{"street":"123 Main","city":{"name":"NYC","country":{"name":"USA"}}}}';

        $company = JsonSerializer::decodeToClass($json, Company::class);

        expect($company)->toBeInstanceOf(Company::class)
            ->and($company->name)->toBe('Acme Corp')
            ->and($company->address)->toBeInstanceOf(Address::class)
            ->and($company->address->street)->toBe('123 Main')
            ->and($company->address->city)->toBeInstanceOf(City::class)
            ->and($company->address->city->name)->toBe('NYC')
            ->and($company->address->city->country)->toBeInstanceOf(Country::class)
            ->and($company->address->city->country->name)->toBe('USA');
    });

    it('can deserialize nullable nested objects', function () {
        class Profile
        {
            public string $bio;
        }

        class User
        {
            public string $name;

            public ?Profile $profile;
        }

        // Test with null profile
        $json1 = '{"name":"John","profile":null}';
        $user1 = JsonSerializer::decodeToClass($json1, User::class);

        expect($user1)->toBeInstanceOf(User::class)
            ->and($user1->name)->toBe('John')
            ->and($user1->profile)->toBeNull();

        // Test with present profile
        $json2 = '{"name":"Jane","profile":{"bio":"Software Engineer"}}';
        $user2 = JsonSerializer::decodeToClass($json2, User::class);

        expect($user2)->toBeInstanceOf(User::class)
            ->and($user2->name)->toBe('Jane')
            ->and($user2->profile)->toBeInstanceOf(Profile::class)
            ->and($user2->profile->bio)->toBe('Software Engineer');
    });

    it('can deserialize union types', function () {
        class DataWithUnion
        {
            public string|int $id;

            public string|null $description;
        }

        // Test with string id
        $json1 = '{"id":"abc123","description":"Test"}';
        $data1 = JsonSerializer::decodeToClass($json1, DataWithUnion::class);

        expect($data1)->toBeInstanceOf(DataWithUnion::class)
            ->and($data1->id)->toBe('abc123')
            ->and($data1->description)->toBe('Test');

        // Test with int id and null description
        $json2 = '{"id":42,"description":null}';
        $data2 = JsonSerializer::decodeToClass($json2, DataWithUnion::class);

        expect($data2)->toBeInstanceOf(DataWithUnion::class)
            ->and($data2->id)->toBe(42)
            ->and($data2->description)->toBeNull();
    });

    it('can deserialize mixed nested objects and collections', function () {
        class Tag
        {
            public string $name;
        }

        class Comment
        {
            public string $text;

            public string $author;
        }

        class Post
        {
            public string $title;

            #[Type('array<Tag>')]
            public array $tags;

            #[Type('array<Comment>')]
            public array $comments;
        }

        $json = '{
            "title":"My Post",
            "tags":[{"name":"php"},{"name":"json"}],
            "comments":[
                {"text":"Great!","author":"John"},
                {"text":"Thanks","author":"Jane"}
            ]
        }';

        $post = JsonSerializer::decodeToClass($json, Post::class);

        expect($post)->toBeInstanceOf(Post::class)
            ->and($post->title)->toBe('My Post')
            ->and($post->tags)->toHaveCount(2)
            ->and($post->tags[0])->toBeInstanceOf(Tag::class)
            ->and($post->tags[0]->name)->toBe('php')
            ->and($post->tags[1]->name)->toBe('json')
            ->and($post->comments)->toHaveCount(2)
            ->and($post->comments[0])->toBeInstanceOf(Comment::class)
            ->and($post->comments[0]->text)->toBe('Great!')
            ->and($post->comments[0]->author)->toBe('John');
    });
});
