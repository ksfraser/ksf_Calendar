# ksfraser/validation

PHP 7.3+ validation helpers and traits intended to replace legacy `Origin`-style ad-hoc validation during the library split.

## Design goals

- No framework dependencies.
- Usable from DTOs, repositories, service classes.
- Prefer composition/traits over deep inheritance.
- Throw a consistent exception type (`ValidationException`).

## Usage

### Static helper

```php
use Ksfraser\Validation\Assert;

Assert::notEmptyString($bankAccountNumber, 'bankAccountNumber');
Assert::stringMaxLen($bankAccountNumber, 255, 'bankAccountNumber');
```

### Trait (thin wrapper over `Assert`)

```php
use Ksfraser\Validation\Traits\ValidatesStringTrait;

final class Example
{
    use ValidatesStringTrait;

    public function setCode($code)
    {
        $this->assertNotEmptyString($code, 'code');
        $this->assertStringMaxLen($code, 20, 'code');
    }
}
```

## Relationship to the split

This package was introduced as part of the long-running split of a legacy “ksf_modules_common” folder into multiple Composer packages.

If you are working inside that monorepo/workspace, see the local docs there:

- `LIBRARY_SPLIT_ANALYSIS.md`
- `repos/fa_classes/MIGRATION_NOTES.md`
- `repos/ksf_ModulesDAO/README.md`
