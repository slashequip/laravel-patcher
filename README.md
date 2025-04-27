# Laravel Patchable 🩹

[![Latest Version on Packagist](https://img.shields.io/packagist/v/slashequip/laravel-patcher.svg?style=flat-square)](https://packagist.org/packages/slashequip/laravel-patcher)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/slashequip/laravel-patcher/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/slashequip/laravel-patcher/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/slashequip/laravel-patcher/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/slashequip/laravel-patcher/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/slashequip/laravel-patcher.svg?style=flat-square)](https://packagist.org/packages/slashequip/laravel-patcher)

Laravel Patchable is an opinionated, declarative package to help simplify the building of PATCH routes.

It abstracts the boiler plate needed to validate and update individual Model attributes based on the request.

## Installation

You can install the package via composer:

```bash
composer require slashequip/laravel-patchable
```

## Usage

Your Model's can be made patchable by add the trait of the same name.

```php
use SlashEquip\Patchable\Traits\Patchable;

class Property extends Model
{
    use Patchable;
}
```

In your patch controllers you can simply call patch on the Model.

```php
class UpdatePropertyController
{
    public function __invoke(Property $property)
    {
        $property->patch();
    }
}
```

To configure you model you need to define your patchable attributes.

```php
class Property extends Model
{
    public $patchable = [
        'name',
        'lat',
        'lng',
    ];
}
```

To get a little more advanced you can define validation for your patchable attributes.

This can be achieved either with Laravel's string or array syntax for validation rules.

```php
class Property extends Model
{
    public $patchable = [
        'name' => 'string|max:255',
        'lat' => [
            'numeric',
            'regex:/^[-]?((([0-8]?[0-9])(\.[0-9]+)?)|90(\.0+)?)$/',
        ],
        'lng' => [
            'numeric',
            'regex:/^[-]?((([0-9]|1[0-7][0-9])(\.[0-9]+)?)|180(\.0+)?)$/',
        ],
    ];
}
```

To get even more advanced you can use Patch classes for your patchable attributes.

```php
class Property extends Model
{
    public $patchable = [
        'name' => PropertyNamePatch::class,
    ];
}
```

```php
use SlashEquip\Patchable\Contracts\Patch;

class PropertyNamePatch implements Patch
{
    public function authorize(Property $property): bool
    {
        return true;
    }

    public function rules(): string|array
    {
        return [
            'string',
            'max:255',
        ];
    }

    public function patch(Property $property, string $key, $value): void
    {
        $property->name = strtolower($value);
    }
}
```

If you want to handle patching with a more manual approach, you can use the Patcher class directly.

```php
use SlashEquip\Patchable\Patcher;

class UpdatePropertyController
{
    public function __invoke(Property $property)
    {
        Patcher::patch(
            model: $property,
            patchable: [
                'name' => PropertyNamePatch::class,
                'lat' => [
                    'numeric',
                    'regex:/^[-]?((([0-8]?[0-9])(\.[0-9]+)?)|90(\.0+)?)$/',
                ],
                'lng' => [
                    'numeric',
                    'regex:/^[-]?((([0-9]|1[0-7][0-9])(\.[0-9]+)?)|180(\.0+)?)$/',
                ],
            ],
            attributes: request()->all(),
        )->finally(function (Property $property) {
            $property->save();
        });
    }
}
```


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Sam Jones](https://github.com/slashequip)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
