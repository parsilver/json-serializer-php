<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Contracts;

use Farzai\JsonSerializer\Engine\SerializationContext;

/**
 * Contract for value transformers.
 *
 * Transformers convert values during serialization and deserialization.
 */
interface TransformerInterface
{
    /**
     * Transform a value during serialization.
     *
     * @param  mixed  $value  The value to transform
     * @param  SerializationContext  $context  The serialization context
     * @param  array<string, mixed>  $options  Optional transformer options
     * @return mixed The transformed value
     */
    public function serialize(mixed $value, SerializationContext $context, array $options = []): mixed;

    /**
     * Transform a value during deserialization.
     *
     * @param  mixed  $value  The value to transform
     * @param  array<string, mixed>  $options  Optional transformer options
     * @return mixed The transformed value
     */
    public function deserialize(mixed $value, array $options = []): mixed;
}
