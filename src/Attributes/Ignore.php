<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Attributes;

use Attribute;

/**
 * Marks a property to be ignored during serialization.
 *
 * @example
 * class User {
 *     #[Ignore]
 *     public string $password;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Ignore {}
