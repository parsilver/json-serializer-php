<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Specifies a custom transformer for property serialization/deserialization.
 *
 * @example
 * class User {
 *     #[Transformer(DateTimeTransformer::class, options: ['format' => 'Y-m-d'])]
 *     public DateTime $birthday;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Transformer
{
    /**
     * Create a new Transformer attribute.
     *
     * @param  class-string<\Farzai\JsonSerializer\Contracts\TransformerInterface>  $class  The transformer class
     * @param  array<string, mixed>  $options  Options to pass to the transformer
     */
    public function __construct(
        public readonly string $class,
        public readonly array $options = []
    ) {}
}
