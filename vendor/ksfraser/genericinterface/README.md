# Ksfraser\GenericInterface

A reusable trait for database model classes, providing standardized property access, mutation, and validation hooks.

## Installation

```
composer require ksfraser/genericinterface
```

## Usage

```php
use Ksfraser\GenericInterface\GenericFaInterfaceTrait;

class MyModel {
    use GenericFaInterfaceTrait;
    public $foo;
    public function validate_field($field, $value) {
        if ($field === 'foo' && $value < 0) {
            throw new Exception('foo must be non-negative');
        }
        return true;
    }
}
```

## Testing

```
cd GenericInterface
vendor/bin/phpunit --bootstrap tests/bootstrap.php tests
```

## Documentation
See docs/requirements.md for requirements and test plan.
