<?php

namespace SlashEquip\Patcher\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Patch
{
    public function authorize(Model $model): bool;

    public function rules(): string|array;

    public function patch(Model $model, string $key, $value): void;
}