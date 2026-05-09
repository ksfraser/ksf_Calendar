# AGENTS.md - ksf_Calendar

> **DO NOT MODIFY THIS FILE.** Create `AGENTS.local.md` for project-specific overrides.

## Core Philosophy

This project follows enterprise-grade software engineering principles. Every decision should align with: **SOLID**, **DRY**, **SRP**, **DI**, and **TDD**.

---

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

### Platform Adapters (separate repos)
- `ksf_FA_Calendar/` → FrontAccounting adapter
- `ksf_UI_Calendar/` → UI components

---

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

---

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

---

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

---

## Coding Standards

### PHP Compatibility
- **Target**: PHP 8.0+
- Always use `declare(strict_types=1);`

### DocBlock Standards
```php
/**
 * Create a calendar event.
 *
 * @param array $data Event data
 * @return Event The created event
 * @throws CalendarValidationException If data is invalid
 *
 * @since 1.0.0
 * @see CalendarRepository::update()
 */
public function createEvent(array $data): Event
```

### Required tags: `@param`, `@return`, `@throws`, `@since`

---

## iCal Integration

### Standards
- Support RFC 5545 (iCalendar)
- Handle timezone conversions properly
- Parse and generate valid iCal strings

---

## Testing Standards

### TDD Workflow
1. **RED**: Write failing test
2. **GREEN**: Write minimal code to pass
3. **REFACTOR**: Improve while keeping tests green

### Coverage Requirements
- **Target**: 100% code coverage
- Skipped tests = failed tests (treat as incomplete)

---

## .gitignore

```
/vendor/
/composer.lock
.phpunit.cache/
.phpunit.result.cache
.idea/
.vscode/
```

**Never track vendor/ or composer.lock** - each developer runs `composer install`.

---

## Documentation

### Project Documents (`doc/ProjectDocuments/`)
```
doc/ProjectDocuments/
├── ProjectDcs/
│   ├── Architecture.md
│   ├── Functional Requirements.md
│   ├── Test Plan.md
│   └── UAT Plan.md
├── BABOK/
├── UML/
└── RTM/
```

### Code Documentation
- Include `@UML` reference for architecture diagrams
- Include `@BABOK` reference for requirements alignment

---

## SOLID Principles Checklist

| Principle | Description |
|-----------|-------------|
| **S**ingle Responsibility | One class, one purpose |
| **O**pen/Closed | Open for extension, closed for modification |
| **L**iskov Substitution | Subtypes substitutable for base types |
| **I**nterface Segregation | Small, focused interfaces |
| **D**ependency Inversion | Depend on abstractions |

---

## Code Review Checklist

- [ ] All new code has tests (100% coverage target)
- [ ] PHPDoc complete with `@param`, `@return`, `@throws`, `@since`
- [ ] No hardcoded values (use constants/config)
- [ ] No duplicate code (use shared libraries)
- [ ] Dependencies injected, not instantiated
- [ ] `.gitignore` excludes vendor/ and composer.lock

---

## Local Overrides

Create `AGENTS.local.md` for project-specific overrides:

```markdown
# AGENTS.local.md
# Project-specific overrides

[Your overrides here]
```

**Note**: Core principles (SOLID, DRY, TDD) cannot be overridden.