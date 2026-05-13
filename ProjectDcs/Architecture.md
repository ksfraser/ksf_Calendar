# ksf_Calendar - Architecture

## Document Information
- **Module**: ksf_Calendar (Calendar Management)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Architecture Overview

### 1.1 Design Principles
The ksf_Calendar module follows these architectural principles:

1. **Multi-Source Aggregation**: Unified calendar from diverse sources
2. **Interface-Based Design**: Contracts for database and project services
3. **Event-Driven**: PSR-14 event dispatching for state changes
4. **Entity-Value Objects**: Immutable-like entities with conversion methods

### 1.2 Technology Stack
- **PHP**: 8.0+ with strict typing
- **Database**: MySQL 5.7+ / MariaDB 10.0+
- **iCal Libraries**: eluceo/ical (export), php-icalendar-core (import)
- **Events**: PSR-14 EventDispatcher
- **Logging**: PSR-3 LoggerInterface

---

## 2. Directory Structure

```
ksf_Calendar/
├── composer.json
├── phpunit.xml
├── AGENTS.md
├── doc/
├── src/
│   └── Ksfraser/
│       └── Calendar/
│           ├── Contract/
│           │   ├── DatabaseAdapterInterface.php
│           │   └── ProjectServiceInterface.php
│           ├── DAO/
│           ├── DTO/
│           ├── Entity/
│           │   ├── CalendarEntry.php
│           │   └── CalendarSource.php
│           ├── Event/
│           ├── Exception/
│           ├── Repository/
│           └── Service/
│               ├── CalendarService.php
│               └── iCalService.php
├── tests/
│   ├── bootstrap.php
│   └── Unit/
├── ProjectDcs/
│   ├── Business Requirements.md
│   ├── Architecture.md
│   ├── Functional Requirements.md
│   ├── Use Case.md
│   ├── Test Plan.md
│   └── UAT Plan.md
└── doc/
```

---

## 3. Class Architecture

### 3.1 Entity Layer

#### CalendarEntry
```php
namespace Ksfraser\Calendar\Entity;

class CalendarEntry
{
    // Entry Types
    const TYPE_EVENT = 'event';
    const TYPE_TASK = 'task';
    const TYPE_CALL = 'call';
    const TYPE_MEETING = 'meeting';
    const TYPE_REMINDER = 'reminder';
    const TYPE_BIRTHDAY = 'birthday';
    const TYPE_ANNIVERSARY = 'anniversary';
    const TYPE_RENEWAL = 'renewal';
    const TYPE_TIMETRACKING = 'timetracking';
    const TYPE_BLOCKED = 'blocked';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_NO_SHOW = 'no_show';

    // Sources
    const SOURCE_PM = 'pm';
    const SOURCE_CRM = 'crm';
    const SOURCE_HRM = 'hrm';
    const SOURCE_CLIENT = 'client';
    const SOURCE_ICAL = 'ical';
    const SOURCE_USER = 'user';

    // Properties
    private ?int $id;
    private string $source;
    private string $sourceId;
    private string $sourceType;
    private string $title;
    private string $description;
    private ?DateTime $startDate;
    private ?DateTime $endDate;
    private string $allDay;
    private string $timezone;
    private string $location;
    private string $assignedTo;
    private ?string $userId;
    private ?string $customerId;
    private ?string $projectId;
    private ?string $taskId;
    private ?string $contactId;
    private string $status;
    private string $priority;
    private string $category;
    private bool $reminder;
    private ?int $reminderMinutes;
    private string $color;
    private bool $private;
    private ?string $recurrenceRule;
    private ?int $recurrenceId;
    private bool $inactive;
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    // Methods
    public function __construct(string $source, string $sourceId, string $sourceType, string $title, ?DateTime $startDate = null, ?string $id = null);
    public function getId(): ?int;
    public function getDuration(): ?int;
    public function isOverdue(): bool;
    public function isToday(): bool;
    public function isPast(): bool;
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public static function fromPMTask(Task $task): self;
    public static function fromCRMActivity(array $activity): self;
    // ... setters and fluent interface
}
```

#### CalendarSource
```php
namespace Ksfraser\Calendar\Entity;

class CalendarSource
{
    // Source Types
    const TYPE_INTERNAL = 'internal';
    const TYPE_EXTERNAL = 'external';
    const TYPE_ICAL = 'ical';
    const TYPE_GOOGLE = 'google';
    const TYPE_CALDAV = 'caldav';
    const TYPE_WEBHOOK = 'webhook';

    // Visibility
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_SHARED = 'shared';
    const VISIBILITY_PUBLIC = 'public';

    // Properties
    private ?int $id;
    private string $name;
    private string $type;
    private string $source;
    private string $url;
    private string $color;
    private bool $enabled;
    private bool $showEvents;
    private bool $showTasks;
    private bool $showCalls;
    private bool $showMeetings;
    private bool $showClientDates;
    private bool $showBirthdays;
    private bool $showAnniversaries;
    private bool $showRenewals;
    private bool $showTimeTracking;
    private string $visibility;
    private string $assignedTo;
    private ?string $userId;
    private ?string $apikey;
    private ?string $lastSync;
    private bool $inactive;
    private ?DateTime $createdAt;

    // Factory Methods
    public static function createPMCalendar(string $name = 'Project Management'): self;
    public static function createCRMTasksCalendar(string $name = 'CRM Tasks'): self;
    public static function createClientDatesCalendar(string $name = 'Client Dates'): self;
    public static function createHRMCalendar(string $name = 'HRM / Time Tracking'): self;

    // Methods
    public function getEnabledSourceTypes(): array;
    public function setFilters(array $filters): self;
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

### 3.2 Service Layer

#### CalendarService
```php
namespace Ksfraser\Calendar\Service;

class CalendarService
{
    private const TABLE_ENTRIES = 'fa_cal_entries';
    private const TABLE_SOURCES = 'fa_cal_sources';

    public function __construct(
        private readonly DatabaseAdapterInterface $db,
        private readonly EventDispatcherInterface $events,
        private readonly LoggerInterface $logger,
        private readonly ?ProjectServiceInterface $projectService = null
    );

    // Entry Operations
    public function createEntry(array $data): CalendarEntry;
    public function getEntry(int $id): CalendarEntry;
    public function updateEntry(int $id, array $data): CalendarEntry;
    public function deleteEntry(int $id): void;

    // Query Operations
    public function getEntriesForDateRange(DateTime $start, DateTime $end, array $filters = []): array;
    public function getEntriesForUser(string $userId, DateTime $start, DateTime $end): array;
    public function getEntriesForCustomer(string $customerId, DateTime $start, DateTime $end): array;
    public function getEntriesForProject(string $projectId, DateTime $start, DateTime $end): array;
    public function getEntriesForTask(string $taskId): array;
    public function getEntryCountByDate(DateTime $date): array;

    // Source Operations
    public function createSource(array $data): CalendarSource;
    public function getSourcesForUser(string $userId): array;

    // Synchronization
    public function syncPMTasks(string $userId): int;
    public function syncCRMActivities(string $userId): int;
}
```

#### iCalService
```php
namespace Ksfraser\Calendar\Service;

class iCalService
{
    private const DEFAULT_TIMEZONE = 'UTC';

    public function __construct(
        private readonly LoggerInterface $logger
    );

    // Export
    public function exportEntries(array $entries, string $calendarName = 'KSF Calendar'): string;
    public function exportEntriesToFile(array $entries, string $filePath, string $calendarName = 'KSF Calendar'): bool;
    public function exportSource(CalendarSource $source, array $entries): string;
    public function generatePublicUrl(CalendarSource $source, string $baseUrl): string;

    // Import
    public function importFromUrl(string $url): array;
    public function importFromFile(string $filePath): array;
    public function importFromString(string $content): array;

    // Private helpers
    private function createICalEvent(CalendarEntry $entry): iCalEvent;
    private function parseICalContent(string $content, string $source): array;
    private function parseVEvent(VEvent $vevent, string $source): ?CalendarEntry;
}
```

### 3.3 Contract Layer (Interfaces)

#### DatabaseAdapterInterface
```php
namespace Ksfraser\Calendar\Contract;

interface DatabaseAdapterInterface
{
    public function fetchAssoc(string $sql, array $params = []): ?array;
    public function fetchAll(string $sql, array $params = []): array;
    public function executeUpdate(string $sql, array $params = []): int;
}
```

#### ProjectServiceInterface
```php
namespace Ksfraser\Calendar\Contract;

interface ProjectServiceInterface
{
    public function getTasksByAssignee(string $userId): array;
}
```

---

## 4. Data Flow Diagrams

### 4.1 Entry Creation Flow
```
[User/API Request]
       |
       v
[CalendarService.createEntry()]
       |
       v
[Validate Entry Data]
       |
       v
[Create CalendarEntry Entity]
       |
       v
[Save to Database]
       |
       v
[Dispatch CalendarEntryCreatedEvent]
       |
       v
[Listeners Process Event]
```

### 4.2 Multi-Source Query Flow
```
[Calendar Request (with filters)]
       |
       v
[CalendarService.getEntriesForDateRange()]
       |
       v
[Build SQL Query with Filters]
       |
       v
[Execute Query]
       |
       v
[Map Results to CalendarEntry Entities]
       |
       v
[Return Entry Collection]
```

### 4.3 PM Sync Flow
```
[Trigger: Manual/Scheduled]
       |
       v
[CalendarService.syncPMTasks()]
       |
       v
[Get Tasks from ProjectService]
       |
       v
[For Each Task:]
       |  [Check if Entry Exists]
       |       |
       |       v
       |  [If New: Create CalendarEntry]
       |       |
       |       v
       |  [If Exists: Update/Ignore]
       |
       v
[Return Sync Count]
```

### 4.4 iCal Export Flow
```
[Export Request]
       |
       v
[iCalService.exportEntries()]
       |
       v
[Create iCalCalendar Object]
       |
       v
[For Each CalendarEntry:]
       |  [Convert to iCalEvent]
       |       |
       |       v
       |  [Add to Calendar]
       |
       v
[Return iCal String]
```

---

## 5. Database Schema

### 5.1 fa_cal_entries Table
```sql
CREATE TABLE fa_cal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(20) NOT NULL,
    source_id VARCHAR(100) NOT NULL,
    source_type VARCHAR(30) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    all_day VARCHAR(3) DEFAULT 'no',
    timezone VARCHAR(50) DEFAULT 'UTC',
    location VARCHAR(255),
    assigned_to VARCHAR(100),
    user_id VARCHAR(50),
    customer_id VARCHAR(50),
    project_id VARCHAR(50),
    task_id VARCHAR(50),
    contact_id VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',
    priority VARCHAR(20) DEFAULT 'medium',
    category VARCHAR(50),
    reminder TINYINT(1) DEFAULT 0,
    reminder_minutes INT,
    color VARCHAR(7),
    private TINYINT(1) DEFAULT 0,
    recurrence_rule VARCHAR(255),
    recurrence_id INT,
    inactive TINYINT(1) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY uk_source (source, source_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_user (user_id),
    INDEX idx_customer (customer_id),
    INDEX idx_project (project_id),
    INDEX idx_status (status)
);
```

### 5.2 fa_cal_sources Table
```sql
CREATE TABLE fa_cal_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL,
    source VARCHAR(20) NOT NULL,
    url VARCHAR(500),
    color VARCHAR(7),
    enabled TINYINT(1) DEFAULT 1,
    show_events TINYINT(1) DEFAULT 1,
    show_tasks TINYINT(1) DEFAULT 1,
    show_calls TINYINT(1) DEFAULT 1,
    show_meetings TINYINT(1) DEFAULT 1,
    show_client_dates TINYINT(1) DEFAULT 0,
    show_birthdays TINYINT(1) DEFAULT 0,
    show_anniversaries TINYINT(1) DEFAULT 0,
    show_renewals TINYINT(1) DEFAULT 0,
    show_time_tracking TINYINT(1) DEFAULT 1,
    visibility VARCHAR(20) DEFAULT 'private',
    assigned_to VARCHAR(100),
    user_id VARCHAR(50),
    apikey VARCHAR(255),
    inactive TINYINT(1) DEFAULT 0,
    created_at DATETIME,
    INDEX idx_user (user_id),
    INDEX idx_source (source)
);
```

---

## 6. Event System

### 6.1 Event Classes
```php
class CalendarEntryCreatedEvent
{
    private CalendarEntry $entry;
    public function __construct(CalendarEntry $entry);
    public function getEntry(): CalendarEntry;
}

class CalendarEntryUpdatedEvent
{
    private CalendarEntry $entry;
    public function __construct(CalendarEntry $entry);
    public function getEntry(): CalendarEntry;
}

class CalendarEntryDeletedEvent
{
    private CalendarEntry $entry;
    public function __construct(CalendarEntry $entry);
    public function getEntry(): CalendarEntry;
}
```

### 6.2 Event Usage
```php
// Dispatch events
$this->events->dispatch(new CalendarEntryCreatedEvent($entry));
$this->events->dispatch(new CalendarEntryUpdatedEvent($entry));
$this->events->dispatch(new CalendarEntryDeletedEvent($entry));

// Listen to events
$dispatcher->addListener(CalendarEntryCreatedEvent::class, function($event) {
    // Handle new entry
});
```

---

## 7. Error Handling

### 7.1 Exception Hierarchy
```php
class CalendarException extends \RuntimeException
class CalendarValidationException extends CalendarException
class CalendarNotFoundException extends CalendarException
class CalendarSyncException extends CalendarException
class iCalParseException extends CalendarException
```

### 7.2 Error Handling Strategy
- Validation errors: Throw CalendarValidationException with field details
- Not found: Throw CalendarNotFoundException with ID
- Sync errors: Log and continue, return partial results
- iCal parse errors: Log and skip invalid entries

---

## 8. Extension Points

### 8.1 Custom Source Adapters
```php
interface CalendarSourceAdapterInterface
{
    public function fetch(DateTime $start, DateTime $end): array;
    public function push(CalendarEntry $entry): bool;
}

class GoogleCalendarAdapter implements CalendarSourceAdapterInterface { }
class OutlookCalendarAdapter implements CalendarSourceAdapterInterface { }
```

### 8.2 Custom Entry Converters
```php
interface EntryConverterInterface
{
    public function canConvert(mixed $source): bool;
    public function toCalendarEntry(mixed $source): CalendarEntry;
}

class MyTaskConverter implements EntryConverterInterface { }
```

---

## 9. Testing Strategy

### 9.1 Unit Tests
- CalendarEntry entity tests
- CalendarSource entity tests
- CalendarService business logic
- iCalService export/import

### 9.2 Integration Tests
- Database operations
- Event dispatching
- Service interactions

### 9.3 Fixtures
- Sample CalendarEntry data
- Sample iCal content
- Mock ProjectService

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*