<?php

namespace SlashEquip\Patchable\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Patch
{
    public function authorize(Model $model): bool;

    public function rules(): string|array;

    public function patch(Model $model, string $key, $value): void;
}
