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
