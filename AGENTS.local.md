<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.local.md — ksf_Calendar
## Repository Architecture
### Business Logic Module
```
ksf_Calendar/                   # Business logic (framework-agnostic)
├── src/Ksfraser/Calendar/
│   ├── Exception/             # Module exceptions (using shared library)
│   ├── Service/               # Business logic services
│   ├── Entity/               # Domain entities (Event, Calendar)
│   ├── Repository/            # Data access abstraction
│   └── Event/                # Event handlers
├── tests/                      # Unit tests
└── doc/                        # Project documents
```
## Legacy Migration: Inheritance → Traits
### The Problem
Legacy modules used deep inheritance with magic methods for type validation and event notifications.
### The Solution
Replace inheritance with trait composition:
```php
// OLD: Inheritance with magic methods
class CalendarEvent extends BaseEntity {
    public function __set($k, $v) {
        validate_type($k, $v);
        $this->$k = $v;
        $this->notify("NOTIFY_SET_{$k}", $v);
    }
}
// NEW: Trait composition
class CalendarEvent {
    use ValidatableTrait;      // Type validation
    use EventEmitterTrait;     // Event notifications
    use EntityStateTrait;      // State tracking
    use TimestampTrait;       // Timestamps
    private ?string $title = null;
    public function setTitle(string $title): self
    {
        $this->assertNotEmptyString($title, 'title');
        $this->title = $title;
        $this->markModified();
        $this->emit('event.title.changed', $title);
        return $this;
    }
}
```
## Dependency Management
### Required Libraries
```json
{
    "require": {
        "ksfraser/exceptions": "^1.3",
        "ksfraser/traits": "^1.0",
        "ksfraser/validation": "^1.0",
        "eluceo/ical": "^2.0"
    }
}
```
### Repositories
```json
{
    "repositories": [
        {"type": "vcs", "url": "https://github.com/ksfraser/Exceptions"},
        {"type": "vcs", "url": "https://github.com/ksfraser/Traits"},
        {"type": "vcs", "url": "https://github.com/ksfraser/Validation"}
    ]
}
```
## Exception Handling
### Use Shared Library
```php
use Ksfraser\Exceptions\Calendar\CalendarException;
use Ksfraser\Exceptions\Calendar\CalendarEventNotFoundException;
use Ksfraser\Exceptions\Utility\ParsingFailedException;
```
### Module-Specific Exceptions
Local exceptions in `Exception/` extend library classes:
```php
use Ksfraser\Exceptions\Calendar\CalendarException as BaseCalendarException;
class CalendarException extends BaseCalendarException
{
    // Module-specific extension
}
```
## iCal Integration
### Standards
- Support RFC 5545 (iCalendar)
- Handle timezone conversions properly
- Parse and generate valid iCal strings
## Development Workflow
All development is done in the **devel tree** (`~/Documents/ksf_Calendar`). Do **not** edit files in the UAT bind point directly.
### Workflow Steps
1. **Develop** in this repo (feature branches preferred)
2. **Test**: run repo-appropriate tests
3. **Lint**: `php -l` on modified PHP files (no syntax errors)
4. **Commit** and **Push** branch to GitHub
5. **Merge** to `master` when ready
6. **Push** `master` to GitHub
7. **Deploy** to UAT by pulling in the Infrastructure bind point:
   ```
   cd ~/ksf_Infrastructure/fa_modules/ksf_Calendar
   git stash -u
   git pull origin master
   git stash pop
   ```
### UAT Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_Calendar` | Devel tree — all development, testing, commits |
| `~/ksf_Infrastructure/fa_modules/ksf_Calendar` | UAT bind point — deployment target, integration testing (if mirrored) |

## Why `require php` is `>=7.4` and must NOT be lowered to 7.3

The shared guidance says PHP 7.3 is the cross-module compatibility floor. **This
module is not on 7.3, deliberately.**

`src/` contains **84 typed properties** (`private ?int $id;` and friends), which
is PHP 7.4+ syntax and a **parse error** on 7.3. Declaring `>=7.3` would be a
false promise: the module would fatal on 7.3 at parse time, before any
autoloader ran.

The runtime this module actually targets is the FA container, which is
**PHP 7.4.33**:

    podman exec ksfii_app-fa php -v   ->  PHP 7.4.33

So:

| Field | Value | Why |
|---|---|---|
| `require.php` | `>=7.4` | 7.4 syntax in src/ |
| `config.platform.php` | `7.4.33` | what the container actually runs |

`config.platform` must stay pinned. The host runs PHP 8.1, so without the pin the
lock resolves for the wrong PHP and the module breaks on the container — the
failure mode described in `ksf_Infrastructure/AGENTS_APPENDIX.md`.
`tests/Unit/SourceTreeTest::testPhpFloorIsHonestAndPlatformPinMatchesTheContainer`
guards both values, so a well-meaning "fix" to 7.3 now fails the build instead of
the container.

If 7.3 is ever genuinely required, that is a refactor of all 84 properties — not a
one-line version change.

## Source-tree guards

`tools/check_autoload.php` reports any class under `src/` that PSR-4 cannot
reach, or whose dependencies do not resolve. It exists because a green test suite
does **not** prove the tree is sound: a file nothing references is never loaded by
the tests and fatals the first time something tries.

It found `legacy/iCalService.php.unloadable`, a 371-line service written against
eluceo/ical **1.x** (`PropertyFactory\FactoryTrait`, removed in 2.x — the
installed version is 2.14.0) plus a second iCal library, `craigk5n/icalendar`,
that is in neither `composer.json` nor `composer.lock`. A missing trait is a
compile-time fatal, not a catchable error, so it could only be found statically.

`tests/Unit/SourceTreeTest` wraps that plus the PSR-4 and namespace rules. Each
guard has been verified to fail on an injected regression.

## DEPLOY TRAP: a namespace rename silently breaks the container

`vendor/` is excluded from the `fa_modules` rsync, which is correct — you do not
want to push a whole vendor tree. But **`vendor/composer/autoload_*.php` is
generated from `composer.json`, and it is part of `vendor/`**.

So renaming a namespace and moving its directory silently leaves the deployed tree
with an autoloader that still points at the old path:

    deployed vendor/composer/autoload_psr4.php
      'Ksfraser\\' => array($baseDir . '/src/Ksfraser')      <- stale

In the container that produced **no output at all**, not an error:

    class_exists('ksfraser\Calendar\Entity\CalendarEntry')  ->  false
    class_exists('Eluceo\iCal\Domain\Entity\Event')          ->  true

The host was green (the host's own `vendor` had been regenerated), and the vendor
classes still worked — so the only symptom was a customer-facing feature silently
producing nothing.

**After any namespace or PSR-4 change, regenerate the autoloader in the DEPLOYED
tree, not just the source repo:**

    cd ksf_Infrastructure/fa_modules/ksf_Calendar && composer dump-autoload

Then verify from inside the container that a module class actually loads:

    podman exec ksfii_app-fa php -r \
      'require "/var/www/html/modules/ksf_Calendar/vendor/autoload.php";
       var_dump(class_exists("ksfraser\\Calendar\\Service\\ICalService"));'

This is the same family of problem as the stale single-file binds recorded in
`ksf_Infrastructure/AGENTS_APPENDIX.md`: the deploy looks correct, `inspect` and
the file paths all agree, and only behaviour inside the container reveals it.
