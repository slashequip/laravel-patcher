<?php

namespace SlashEquip\Patcher\Traits;

use Illuminate\Database\Eloquent\Model;
use SlashEquip\Patcher\Patcher;

/**
 * @mixin Model
 * @phpstan-ignore trait.unused
 */
trait Patchable
{
    public function patch(): bool
    {
        return Patcher::patchAndSave(
            model: $this,
            patchable: $this->patchable ?? [],
            attributes: request()->all()
        );
    }
}
