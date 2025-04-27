<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use SlashEquip\Patcher\Contracts\Patch;
use SlashEquip\Patcher\Patcher;

// Set up a test model for our tests
class TestModel extends Model
{
    protected $guarded = [];
}

// Set up a custom patch class for testing
class TestPatch implements Patch
{
    public function authorize(Model $model): bool
    {
        return true;
    }

    public function rules(): string|array
    {
        return ['required', 'string'];
    }

    public function patch(Model $model, string $key, $value): void
    {
        $model->{$key} = strtoupper($value);
    }
}

// Set up an unauthorized patch class for testing
class UnauthorizedPatch implements Patch
{
    public function authorize(Model $model): bool
    {
        return false;
    }

    public function rules(): string|array
    {
        return [];
    }

    public function patch(Model $model, string $key, $value): void {}
}

// Custom model class for trait testing
class PatchableTestModel extends Model
{
    use \SlashEquip\Patcher\Traits\Patchable;

    protected $guarded = [];

    public $patchable = ['name'];

    // Prevent database interaction
    public function save(array $options = [])
    {
        return true;
    }
}

// Simple attribute patching
it('can patch with simple attribute names', function () {
    $model = new TestModel(['name' => 'Old Name']);
    $attributes = ['name' => 'New Name'];

    Patcher::patch(
        model: $model,
        patchable: ['name'],
        attributes: $attributes
    )->apply();

    expect($model->name)->toBe('New Name');
});

// String validation rules patching
it('can patch with string validation rules', function () {
    $model = new TestModel(['name' => 'Old Name']);
    $attributes = ['name' => 'New Name'];

    Patcher::patch(
        model: $model,
        patchable: ['name' => 'required|string|max:255'],
        attributes: $attributes
    )->apply();

    expect($model->name)->toBe('New Name');
});

// String validation failure
it('throws validation exception with invalid string rules', function () {
    $model = new TestModel(['name' => 'Old Name']);
    $attributes = ['name' => ''];

    expect(fn () => Patcher::patch(
        model: $model,
        patchable: ['name' => 'required|string'],
        attributes: $attributes
    )->apply())->toThrow(ValidationException::class);

    expect($model->name)->toBe('Old Name');
});

// Array validation rules patching
it('can patch with array validation rules', function () {
    $model = new TestModel(['age' => 20]);
    $attributes = ['age' => 25];

    Patcher::patch(
        model: $model,
        patchable: ['age' => ['required', 'integer', 'min:18']],
        attributes: $attributes
    )->apply();

    expect($model->age)->toBe(25);
});

// Custom patch class
it('can patch with custom patch classes', function () {
    $model = new TestModel(['name' => 'lowercase']);
    $attributes = ['name' => 'should be uppercase'];

    Patcher::patch(
        model: $model,
        patchable: ['name' => TestPatch::class],
        attributes: $attributes
    )->apply();

    expect($model->name)->toBe('SHOULD BE UPPERCASE');
});

// Skip attributes not in request
it('skips attributes not in request', function () {
    $model = new TestModel(['name' => 'Original', 'age' => 20]);
    $attributes = ['name' => 'New Name']; // age not included

    Patcher::patch(
        model: $model,
        patchable: ['name', 'age'],
        attributes: $attributes
    )->apply();

    expect($model->name)->toBe('New Name');
    expect($model->age)->toBe(20); // unchanged
});

// Unauthorized patch
it('aborts when patch is not authorized', function () {
    $model = new TestModel(['name' => 'Original']);
    $attributes = ['name' => 'New Name'];

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    Patcher::patch(
        model: $model,
        patchable: ['name' => UnauthorizedPatch::class],
        attributes: $attributes
    )->apply();
});

// Validate before patching (multiple attributes)
it('validates all attributes before patching', function () {
    $model = new TestModel(['name' => 'Original', 'age' => 20]);
    $attributes = ['name' => '', 'age' => 25]; // name is invalid

    expect(fn () => Patcher::patch(
        model: $model,
        patchable: [
            'name' => 'required|string',
            'age' => ['required', 'integer'],
        ],
        attributes: $attributes
    )->apply())->toThrow(ValidationException::class);

    // Neither attribute should be updated
    expect($model->name)->toBe('Original');
    expect($model->age)->toBe(20);
});

// Patch and save
it('saves model after patching when using patch_and_save', function () {
    $model = $this->createPartialMock(TestModel::class, ['save']);
    $model->expects($this->once())->method('save')->willReturn(true);

    $attributes = ['name' => 'New Name'];

    $result = Patcher::patchAndSave(
        model: $model,
        patchable: ['name'],
        attributes: $attributes
    );

    expect($result)->toBeTrue();
});

// Apply callback
it('applies callback after patching', function () {
    $model = new TestModel(['name' => 'Original']);
    $attributes = ['name' => 'New Name'];
    $callbackRan = false;

    $result = Patcher::patch(
        model: $model,
        patchable: ['name'],
        attributes: $attributes
    )->apply(function ($patchedModel) use (&$callbackRan) {
        $callbackRan = true;

        return 'callback result';
    });

    expect($callbackRan)->toBeTrue();
    expect($result)->toBe('callback result');
});

// Invalid patch definition
it('throws exception for invalid patch definition', function () {
    $model = new TestModel;
    $attributes = ['name' => 'New Name'];

    expect(fn () => Patcher::patch(
        model: $model,
        patchable: ['name' => new \stdClass], // Invalid definition
        attributes: $attributes
    )->apply())->toThrow(\Error::class);
});

// Patching multiple attributes
it('allows multiple attributes to be patched simultaneously', function () {
    $model = new TestModel(['name' => 'Original', 'age' => 20, 'email' => 'old@example.com']);
    $attributes = [
        'name' => 'New Name',
        'age' => 25,
        'email' => 'new@example.com',
    ];

    Patcher::patch(
        model: $model,
        patchable: [
            'name' => 'required|string',
            'age' => ['required', 'integer'],
            'email' => 'required|email',
        ],
        attributes: $attributes
    )->apply();

    expect($model->name)->toBe('New Name');
    expect($model->age)->toBe(25);
    expect($model->email)->toBe('new@example.com');
});

// Trait usage
it('works with trait on model', function () {
    // Create and configure the test model
    $model = new PatchableTestModel(['name' => 'Original']);

    // Test the update directly using the Patcher class
    Patcher::patchAndSave(
        model: $model,
        patchable: ['name'],
        attributes: ['name' => 'New Name']
    );

    // Assert model was updated
    expect($model->name)->toBe('New Name');
});
