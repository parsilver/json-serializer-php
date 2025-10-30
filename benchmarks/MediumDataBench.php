<?php

declare(strict_types=1);

use Farzai\JsonSerializer\JsonSerializer;

/**
 * Medium Data Benchmarks
 *
 * Tests serialization and deserialization performance for medium data structures (1-10MB).
 * Compares JsonSerializer against native json_encode/json_decode.
 * Measures throughput in MB/s.
 */
class MediumDataBench
{
    private array $data1mb;

    private array $data5mb;

    private array $data10mb;

    private string $json1mb;

    private string $json5mb;

    private string $json10mb;

    public function __construct()
    {
        // Load medium fixtures
        $this->data1mb = json_decode(
            file_get_contents(__DIR__.'/fixtures/medium-1mb.json'),
            true
        );
        $this->data5mb = json_decode(
            file_get_contents(__DIR__.'/fixtures/medium-5mb.json'),
            true
        );
        $this->data10mb = json_decode(
            file_get_contents(__DIR__.'/fixtures/medium-10mb.json'),
            true
        );

        // Pre-encode for deserialization benchmarks
        $this->json1mb = json_encode($this->data1mb);
        $this->json5mb = json_encode($this->data5mb);
        $this->json10mb = json_encode($this->data10mb);
    }

    /**
     * @Groups({"1mb", "encode", "native"})
     */
    public function benchNativeEncode1MB(): void
    {
        json_encode($this->data1mb);
    }

    /**
     * @Groups({"1mb", "encode", "serializer"})
     */
    public function benchJsonSerializerEncode1MB(): void
    {
        JsonSerializer::encode($this->data1mb);
    }

    /**
     * @Groups({"1mb", "decode", "native"})
     */
    public function benchNativeDecode1MB(): void
    {
        json_decode($this->json1mb, true);
    }

    /**
     * @Groups({"1mb", "decode", "serializer"})
     */
    public function benchJsonSerializerDecode1MB(): void
    {
        JsonSerializer::decode($this->json1mb);
    }

    /**
     * @Groups({"5mb", "encode", "native"})
     */
    public function benchNativeEncode5MB(): void
    {
        json_encode($this->data5mb);
    }

    /**
     * @Groups({"5mb", "encode", "serializer"})
     */
    public function benchJsonSerializerEncode5MB(): void
    {
        JsonSerializer::encode($this->data5mb);
    }

    /**
     * @Groups({"5mb", "decode", "native"})
     */
    public function benchNativeDecode5MB(): void
    {
        json_decode($this->json5mb, true);
    }

    /**
     * @Groups({"5mb", "decode", "serializer"})
     */
    public function benchJsonSerializerDecode5MB(): void
    {
        JsonSerializer::decode($this->json5mb);
    }

    /**
     * @Groups({"10mb", "encode", "native"})
     */
    public function benchNativeEncode10MB(): void
    {
        json_encode($this->data10mb);
    }

    /**
     * @Groups({"10mb", "encode", "serializer"})
     */
    public function benchJsonSerializerEncode10MB(): void
    {
        JsonSerializer::encode($this->data10mb);
    }

    /**
     * @Groups({"10mb", "decode", "native"})
     */
    public function benchNativeDecode10MB(): void
    {
        json_decode($this->json10mb, true);
    }

    /**
     * @Groups({"10mb", "decode", "serializer"})
     */
    public function benchJsonSerializerDecode10MB(): void
    {
        JsonSerializer::decode($this->json10mb);
    }

    /**
     * @Groups({"1mb", "decode-class", "serializer"})
     */
    public function benchJsonSerializerDecodeToClass1MB(): void
    {
        JsonSerializer::decodeArray($this->json1mb, UserDTO::class);
    }

    /**
     * @Groups({"5mb", "decode-class", "serializer"})
     *
     * @Revs(5)
     */
    public function benchJsonSerializerDecodeToClass5MB(): void
    {
        JsonSerializer::decodeArray($this->json5mb, UserDTO::class);
    }
}

// DTO class for deserialization benchmarks
class UserDTO
{
    public int $id;

    public string $name;

    public string $email;

    public int $age;

    public bool $isActive;

    public float $balance;

    public string $registeredAt;

    public AddressDTO $address;

    public array $tags;

    public array $metadata;
}

class AddressDTO
{
    public string $street;

    public string $city;

    public string $state;

    public string $zipCode;

    public string $country;
}
