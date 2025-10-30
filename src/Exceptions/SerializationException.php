<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Exceptions;

use RuntimeException;

/**
 * Base exception for all serialization-related errors.
 *
 * This is the root exception class that all other serialization
 * exceptions inherit from, allowing catch blocks to handle all
 * serialization errors uniformly if needed.
 */
class SerializationException extends RuntimeException {}
