<?php

namespace SlashEquip\Patcher;

use Illuminate\Database\Eloquent\Model;
use SlashEquip\Patcher\Contracts\Patch;

class DumbPatch implements Patch
{
    public function __construct(
        public readonly array $rules,
    ) {}    

    public function authorize(Model $model): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function patch(Model $model, string $key, $value): void
    {
        $model->{$key} = $value;
    }
}