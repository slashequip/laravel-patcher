<?php

namespace SlashEquip\Patchable\Exceptions;

use Exception;

class InvalidPatchDefinitionException extends Exception
{
    public static function fromValue(mixed $value): self
    {
        return new self("Invalid patch definition: {$value}");
    }
}
