<?php

namespace SlashEquip\Patchable\Traits;

use Illuminate\Database\Eloquent\Model;
use SlashEquip\Patchable\Patcher;

/**
 * @mixin Model
 *
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
