<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Exceptions;

/**
 * Exception thrown when stream operations fail.
 *
 * This exception is used for errors related to stream I/O operations
 * such as read failures, write failures, or invalid stream states.
 */
class StreamException extends SerializationException {}
