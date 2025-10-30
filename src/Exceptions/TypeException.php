<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Exceptions;

/**
 * Exception thrown when type handling fails.
 *
 * This exception is used for errors related to type detection,
 * type converfinal sion, or when a value cannot be serialized due to
 * type-related issues.
 */
class TypeException extends SerializationException {}
