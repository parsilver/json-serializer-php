<?php

declare(strict_types=1);

namespace Farzai\JsonSerializer\Stream;

/**
 * Represents a JSON token during streaming parsing.
 *
 * A token is a single unit of JSON data (e.g., start of object, key, value, etc.)
 * that is emitted by the pull parser as it reads through the JSON stream.
 */
enum JsonToken
{
    /**
     * Start of a JSON object: {
     */
    case START_OBJECT;

    /**
     * End of a JSON object: }
     */
    case END_OBJECT;

    /**
     * Start of a JSON array: [
     */
    case START_ARRAY;

    /**
     * End of a JSON array: ]
     */
    case END_ARRAY;

    /**
     * A field name/key in an object
     */
    case FIELD_NAME;

    /**
     * A string value
     */
    case STRING;

    /**
     * A number value (int or float)
     */
    case NUMBER;

    /**
     * A boolean value (true or false)
     */
    case BOOLEAN;

    /**
     * A null value
     */
    case NULL;

    /**
     * End of the JSON document
     */
    case END_DOCUMENT;
}
