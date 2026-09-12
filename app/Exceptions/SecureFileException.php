<?php

namespace App\Exceptions;

use RuntimeException;

class SecureFileException extends RuntimeException
{
    public static function unknownCategory(string $category): self
    {
        return new self('Unknown file category.');
    }

    public static function invalidType(): self
    {
        return new self('This file type is not allowed.');
    }

    public static function tooLarge(): self
    {
        return new self('This file is too large.');
    }

    public static function attachableNotAllowed(): self
    {
        return new self('This file cannot be attached to that record.');
    }
}
