<?php

namespace SlashEquip\Patcher;

use Illuminate\Database\Eloquent\Model;
use Closure;
use SlashEquip\Patcher\Exceptions\InvalidPatchDefinitionException;
use SlashEquip\Patcher\Contracts\Patch;
class Patcher
{
    public static function patch(Model $model, array $patchable, array $attributes): self
    {
        return new static($model, $patchable, $attributes);
    }

    public static function patchAndSave(Model $model, array $patchable, array $attributes): bool
    {
        return (new static($model, $patchable, $attributes))
            ->apply(fn (Model $model) => $model->save());
    }

    public function __construct(
        public readonly Model $model,
        public readonly array $patchable,
        public readonly array $attributes,
    ) {}

    protected function handle(): void
    {
        // Map patchable definitions to patches and filter out patches not present.
        $patchable = collect($this->patchable)
            ->mapWithKeys(fn ($value, $key) => $this->mapPatchable($value, $key))
            ->filter(fn ($value, $key) => array_key_exists($key, $this->attributes));

        // Authorize relevant patches.
        $patchable
            ->each(function (Patch $patch, $key) {
                if (!$patch->authorize($this->model)) {
                    abort(403);
                }
            });

        // Validate relevant patches.
        $rules = $patchable
            ->mapWithKeys(fn (Patch $patch, $key) => [$key => $patch->rules()])
            ->all();

        validator(
            $this->attributes,
            $rules
        )->validate();

        // Apply relevant patches.
        $patchable->each(function (Patch $patch, $key) {
            $patch->patch($this->model, $key, $this->attributes[$key]);
        });
    }

    protected function mapPatchable(mixed $value, mixed $key): array
    {
        if (is_numeric($key)) {
            return [$value => new DumbPatch([])];
        }

        if (is_array($value)) {
            return [$key => new DumbPatch($value)];
        }

        if (is_string($value) && !class_exists($value)) {
            return [$key => new DumbPatch(explode('|', $value))];
        }

        if (is_string($value) && class_exists($value) && is_subclass_of($value, Patch::class)) {
            return [$key => resolve($value)];
        }

        throw InvalidPatchDefinitionException::fromValue($value);
    }

    public function apply(?Closure $callback = null): mixed
    {
        $this->handle();

        if (is_null($callback)) {
            return null;
        }

        return $callback($this->model);
    }
}
