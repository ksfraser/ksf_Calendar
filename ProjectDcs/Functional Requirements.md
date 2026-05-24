# ksf_Calendar - Functional Requirements

## Document Information
- **Module**: ksf_Calendar (Calendar Management)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Overview

### 1.1 Purpose
This document defines the functional requirements for the ksf_Calendar module, providing unified calendar management across the KSFII platform.

### 1.2 Scope
- Calendar entry CRUD operations
- Multi-source event aggregation
- iCal import/export
- Source configuration and filtering
- Cross-module synchronization

---

## 2. Calendar Entry Management

### 2.1 Create Entry (FR-ENT-001)
**Requirement**: The system shall create calendar entries.

**Input**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| title | string | Yes | Entry title |
| source | string | Yes | Source identifier |
| source_id | string | No | External ID |
| source_type | string | No | Entry type |
| start_date | datetime | Yes | Start date/time |
| end_date | datetime | No | End date/time |
| description | string | No | Entry description |
| location | string | No | Meeting location |
| assigned_to | string | No | Assigned user |
| customer_id | string | No | Linked customer |
| project_id | string | No | Linked project |
| task_id | string | No | Linked task |
| status | string | No | Entry status |
| priority | string | No | Priority level |
| all_day | bool | No | All-day event flag |
| private | bool | No | Private entry flag |

**Process**:
1. Validate required fields
2. Validate date logic (end >= start)
3. Generate source_id if not provided
4. Create CalendarEntry entity
5. Save to database
6. Dispatch CalendarEntryCreatedEvent

**Output**: CalendarEntry entity

**Priority**: Critical

### 2.2 Read Entry (FR-ENT-002)
**Requirement**: The system shall retrieve calendar entries.

**Methods**:
- `getEntry(int $id)` - Get single entry by ID
- `getEntriesForDateRange(DateTime $start, DateTime $end, array $filters)` - Query entries
- `getEntriesForUser(string $userId, DateTime $start, DateTime $end)` - User's entries
- `getEntriesForCustomer(string $customerId, DateTime $start, DateTime $end)` - Customer entries
- `getEntriesForProject(string $projectId, DateTime $start, DateTime $end)` - Project entries
- `getEntriesForTask(string $taskId)` - Entries for specific task

**Filters** (passed to `getEntriesForDateRange`):
- source, source_type, assigned_to, user_id, customer_id, project_id, status
- `viewable_by` (int): Enforce visibility check — returns only entries the user owns
  or is invited to (resolved via crm_persons/crm_contacts person registry)

**Priority**: Critical

### 2.3 Update Entry (FR-ENT-003)
**Requirement**: The system shall update calendar entries.

**Updatable Fields**:
- title, description
- start_date, end_date
- location, status, priority
- assigned_to, color

**Process**:
1. Fetch existing entry
2. Apply updates to entity
3. Save changes
4. Dispatch CalendarEntryUpdatedEvent

**Priority**: High

### 2.4 Delete Entry (FR-ENT-004)
**Requirement**: The system shall soft-delete calendar entries.

**Process**:
1. Set inactive = 1
2. Dispatch CalendarEntryDeletedEvent
3. Entry hidden from queries

**Note**: Hard delete not supported; preserves audit trail

**Priority**: High

---

## 3. Entry Types

### 3.1 Type Constants (FR-TYPE-001)
**Requirement**: The system shall support defined entry types.

| Constant | Value | Description |
|----------|-------|-------------|
| TYPE_EVENT | 'event' | General event |
| TYPE_TASK | 'task' | Task/due date |
| TYPE_CALL | 'call' | Phone call |
| TYPE_MEETING | 'meeting' | Meeting |
| TYPE_REMINDER | 'reminder' | Reminder |
| TYPE_BIRTHDAY | 'birthday' | Birthday |
| TYPE_ANNIVERSARY | 'anniversary' | Anniversary |
| TYPE_RENEWAL | 'renewal' | Contract renewal |
| TYPE_TIMETRACKING | 'timetracking' | Time entry |
| TYPE_BLOCKED | 'blocked' | Blocked time |

**Priority**: High

### 3.2 Status Constants (FR-STATUS-001)
**Requirement**: The system shall support defined statuses.

| Constant | Value | Description |
|----------|-------|-------------|
| STATUS_PENDING | 'pending' | Not yet started |
| STATUS_CONFIRMED | 'confirmed' | Confirmed |
| STATUS_CANCELLED | 'cancelled' | Cancelled |
| STATUS_COMPLETED | 'completed' | Completed |
| STATUS_NO_SHOW | 'no_show' | Did not show |

**Priority**: High

---

## 4. Source Management

### 4.1 Source Types (FR-SOURCE-001)
**Requirement**: The system shall identify entry sources.

| Source | Module | Description |
|--------|--------|-------------|
| SOURCE_PM | ksf_ProjectManagement | Project tasks |
| SOURCE_CRM | ksf_CRM | CRM activities |
| SOURCE_HRM | ksf_HRM | HRM entries |
| SOURCE_CLIENT | ksf_Client | Client dates |
| SOURCE_ICAL | External | iCal feeds |
| SOURCE_USER | Manual | User-created |

**Priority**: High

### 4.2 Create Source (FR-SOURCE-002)
**Requirement**: The system shall create calendar sources.

**Input**:
- name: Source display name
- type: Source type (internal/external/ical)
- source: Source identifier
- url: Feed URL (for external)
- color: Display color
- filters: Entry type filters

**Priority**: High

### 4.3 Source Filters (FR-SOURCE-003)
**Requirement**: The system shall filter entries by source.

**Filter Types**:
- show_events
- show_tasks
- show_calls
- show_meetings
- show_client_dates
- show_birthdays
- show_anniversaries
- show_renewals
- show_time_tracking

**Priority**: High

---

## 5. iCal Integration

### 5.1 iCal Export (FR-ICAL-001)
**Requirement**: The system shall export entries to iCal format.

**Methods**:
- `exportEntries(array $entries, string $calendarName)` - Export to string
- `exportEntriesToFile(array $entries, string $filePath)` - Export to file
- `exportSource(CalendarSource $source, array $entries)` - Export source

**Output Format**: RFC 5545 compliant iCal

**Priority**: High

### 5.2 iCal Import (FR-ICAL-002)
**Requirement**: The system shall import entries from iCal.

**Methods**:
- `importFromUrl(string $url)` - Import from URL
- `importFromFile(string $filePath)` - Import from file
- `importFromString(string $content)` - Import from string

**Processing**:
1. Fetch/parse iCal content
2. Extract VEVENT components
3. Convert to CalendarEntry entities
4. Return entry collection

**Priority**: High

### 5.3 iCal Field Mapping (FR-ICAL-003)
**Requirement**: The system shall map iCal fields to CalendarEntry.

| iCal Property | CalendarEntry Field |
|---------------|---------------------|
| SUMMARY | title |
| DESCRIPTION | description |
| DTSTART | startDate |
| DTEND | endDate |
| LOCATION | location |
| UID | sourceId |
| RRULE | recurrenceRule |
| CREATED | createdAt |
| LAST-MODIFIED | updatedAt |
| CATEGORIES | category (source) |

**Priority**: High

### 5.4 Public URL Generation (FR-ICAL-004)
**Requirement**: The system shall generate shareable iCal URLs.

**Format**: `baseUrl/ical/{sourceId}/{token}/export.ics`

**Token Generation**: SHA256 hash of source ID + name + date

**Priority**: Medium

---

## 6. Synchronization

### 6.1 PM Task Sync (FR-SYNC-001)
**Requirement**: The system shall sync PM tasks to calendar.

**Process**:
1. Call ProjectService.getTasksByAssignee(userId)
2. For each task:
   - Check if entry exists (by source + sourceId)
   - If not exists: Create CalendarEntry from task
   - If exists: Skip or update
3. Return sync count

**Priority**: Medium

### 6.2 CRM Activity Sync (FR-SYNC-002)
**Requirement**: The system shall sync CRM activities to calendar.

**Process**:
1. Query fa_crm_communications for user
2. Filter by date range (1 year)
3. For each activity:
   - Check if entry exists
   - If not exists: Create CalendarEntry
4. Return sync count

**Priority**: Medium

---

## 7. Invitee Management (v1.3.0+)

### 7.1 Add Invitee (FR-INV-001)
**Requirement**: The system shall add invitees to calendar entries.

**Input**:
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| entry_id | int | Yes | Calendar entry ID |
| contact_type | string | Yes | 'user', 'crm_contact', 'resource', 'ad_hoc' |
| contact_id | int\|null | No | FK to crm_contacts.id (for person-registry types) |
| name | string | Yes | Display name |
| email | string | No | Email address |
| phone | string | No | Phone number |
| is_organizer | bool | No | Organizer flag |

**Process**:
1. Lookup entry
2. Create CalendarInvitee entity
3. If resource: check availability, auto-accept if free
4. INSERT row into fa_cal_invitees

**Priority**: High

### 7.2 Update RSVP (FR-INV-002)
**Requirement**: The system shall update invitee RSVP status.

**Statuses**: pending, accepted, declined, tentative, needs-action

**Process**:
1. Lookup invitee row
2. Set rsvp_status
3. Set responded_at = NOW()
4. Dispatch RSVP change event (future)

**Priority**: High

### 7.3 Remove Invitee (FR-INV-003)
**Requirement**: The system shall soft-delete invitees.

**Process**:
1. Set inactive = 1 on fa_cal_invitees row

**Priority**: High

### 7.4 Search Invitees (FR-INV-004)
**Requirement**: The system shall search for people/resources to invite.

**Endpoint**: GET search_invitees?q={query}&limit={max}

**Process**:
1. Query crm_persons JOIN crm_contacts JOIN crm_categories
2. Return one row per "hat" (contact_type) per matching person
3. Optionally query fa_resources for matching resources
4. Return JSON: [{contact_type, contact_id, name, email, phone, type_label}]

**Priority**: Medium

### 7.5 Free/Busy Check (FR-INV-005)
**Requirement**: The system shall show free/busy time slots for contacts.

**Endpoint**: GET get_free_busy?contact_type=X&contact_id=Y&start=...&end=...

**Process**:
1. Find all non-declined invitee rows for this contact in date range
2. Return busy slots: [{start, end}, ...]

**Priority**: Low

### 7.6 My Invitations (FR-INV-006)
**Requirement**: The system shall show events a user is invited to.

**Process**:
1. Resolve user ID → crm_contacts.id via person registry
2. Query fa_cal_invitees + fa_cal_entries where contact_id matches
3. Return entries with RSVP status

**Priority**: Medium

---

## 8. Entity Definitions

### 8.1 CalendarEntry Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| id | int\|null | null | Primary key |
| source | string | required | Entry source |
| sourceId | string | required | External ID |
| sourceType | string | required | Entry type |
| title | string | required | Entry title |
| description | string | '' | Description |
| startDate | DateTime\|null | null | Start date |
| endDate | DateTime\|null | null | End date |
| allDay | string | 'no' | 'yes' or 'no' |
| timezone | string | system | Timezone |
| location | string | '' | Location |
| assignedTo | string | '' | Assignee |
| userId | string\|null | null | Owner user |
| customerId | string\|null | null | Customer link |
| projectId | string\|null | null | Project link |
| taskId | string\|null | null | Task link |
| contactId | int\|null | null | Contact link (FK to crm_contacts.id) |
| status | string | 'pending' | Status |
| priority | string | 'medium' | Priority |
| category | string | '' | Category |
| reminder | bool | false | Reminder flag |
| reminderMinutes | int\|null | null | Minutes before |
| color | string | '' | Display color |
| private | bool | false | Private flag |
| recurrenceRule | string\|null | null | RRULE string |
| recurrenceId | int\|null | null | Parent recurrence |
| inactive | bool | false | Soft delete flag |

### 8.2 CalendarEntry Methods

```php
class CalendarEntry
{
    // Getters
    public function getId(): ?int;
    public function getSource(): string;
    public function getSourceId(): string;
    public function getTitle(): string;
    public function getStartDate(): ?DateTime;
    public function getEndDate(): ?DateTime;
    public function getDuration(): ?int; // Seconds
    public function isOverdue(): bool;
    public function isToday(): bool;
    public function isPast(): bool;
    public function isAllDay(): bool;
    public function hasReminder(): bool;

    // Converters
    public function toArray(): array;
    public static function fromArray(array $data): self;
    public static function fromPMTask(Task $task): self;
    public static function fromCRMActivity(array $activity): self;
}
```

### 8.3 CalendarSource Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| id | int\|null | null | Primary key |
| name | string | required | Display name |
| type | string | required | Source type |
| source | string | required | Source identifier |
| url | string | '' | Feed URL |
| color | string | auto | Display color |
| enabled | bool | true | Is enabled |
| visibility | string | 'private' | Visibility level |

### 8.4 CalendarSource Factory Methods

```php
class CalendarSource
{
    public static function createPMCalendar(string $name = 'Project Management'): self;
    public static function createCRMTasksCalendar(string $name = 'CRM Tasks'): self;
    public static function createClientDatesCalendar(string $name = 'Client Dates'): self;
    public static function createHRMCalendar(string $name = 'HRM / Time Tracking'): self;
}
```

---

## 8. Filtering & Queries

### 8.1 Filter Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| source | string | Filter by source |
| source_type | string\|array | Filter by entry type |
| assigned_to | string | Filter by assignee |
| user_id | string | Filter by owner |
| customer_id | string | Filter by customer |
| project_id | string | Filter by project |
| status | string | Filter by status |

### 8.2 Query Date Logic
Entries are returned if they overlap the query range:
```sql
(start_date BETWEEN ? AND ?) OR
(end_date BETWEEN ? AND ?) OR
(start_date <= ? AND end_date >= ?)
```

---

## 9. Error Handling

### 9.1 Validation Rules
| Field | Rule | Error |
|-------|------|-------|
| title | Required | "Title is required" |
| start_date | Required | "Start date is required" |
| end_date | If provided, >= start | "End date cannot be before start date" |

### 9.2 Exception Usage
- CalendarException: Base exception
- CalendarValidationException: Validation failures
- CalendarNotFoundException: Entry not found
- CalendarSyncException: Sync failures

---

## 10. Non-Functional Requirements

### 10.1 Performance
- Date range query (1000 entries): < 500ms
- Entry create: < 100ms
- iCal export (100 entries): < 1s
- iCal import (100 entries): < 2s

### 10.2 Scalability
- Support 10,000+ entries per user
- Paginated queries for large result sets
- Efficient indexes on common queries

### 10.3 Compatibility
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.0+
- iCal RFC 5545 compliant

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*