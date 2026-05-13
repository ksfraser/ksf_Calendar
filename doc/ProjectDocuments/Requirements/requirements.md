# KSF Calendar - Requirements

## Project Overview

**Name**: ksf_Calendar (ksfraser/ksf-calendar)  
**Type**: Composer-installable PHP library  
**Purpose**: Unified calendar system that aggregates events from PM, CRM, HRM, and client data sources. iCal import/export, multi-calendar filtering, FullCalendar.js ready.

---

## Scope

### 1. Core Entities

#### CalendarEntry
Any schedulable item regardless of source:
- **PM tasks** (from ksf_ProjectManagement)
- **CRM activities** (meetings, calls, tasks from FA_CRM)
- **HRM time tracking** (from ksf_TimeTracking - future)
- **Client dates** (birthdays, anniversaries, renewals)
- **User events** (direct calendar entries)
- **iCal imports** (external calendars)

| Field | Notes |
|-------|-------|
| source | pm, crm, hrm, client, ical, user |
| source_id | ID from external system |
| source_type | event, task, call, meeting, birthday, anniversary, renewal, timetracking |
| title, description | Content |
| start_date, end_date | Date/time |
| all_day | Boolean flag |
| assigned_to, user_id | Who it's for |
| customer_id | Optional link to CRM customer |
| project_id | Optional link to PM project |
| task_id | Optional link to PM task |
| color | Display color |
| private | Visibility flag |
| recurrence_rule | iCal RRULE string |

#### CalendarSource
A calendar "view" that filters which entries to display:
- PM Tasks calendar
- CRM Activities calendar
- Client Dates calendar (birthdays, anniversaries, renewals)
- HRM Time Tracking calendar
- User's personal calendar
- External iCal feed

Each source has filters for which source_types to include.

### 2. Supported Source Types

| Type | Source | Description |
|------|--------|-------------|
| task | PM | ksf_ProjectManagement tasks |
| call | CRM | Phone calls from FA_CRM |
| meeting | CRM | Meetings from FA_CRM |
| event | User | Direct calendar entries |
| timetracking | HRM | Time tracking entries |
| birthday | Client | Customer birthdays |
| anniversary | Client | Customer anniversaries |
| renewal | Client | Contract/service renewals |

### 3. Services

#### CalendarService
- CRUD for CalendarEntry
- Query by date range, user, customer, project
- Sync from PM (ksf_ProjectManagement)
- Sync from CRM (FA_CRM communications)
- Source management

#### iCalService
- Export entries to iCal format (eluceo/ical)
- Import from URL/file/string (php-icalendar-core)
- Generate public iCal feed URLs
- Filter export by source

### 4. Events (PSR-14)

- `CalendarEntryCreatedEvent`
- `CalendarEntryUpdatedEvent`
- `CalendarEntryDeletedEvent`

### 5. Database Schema (fa_cal_ prefix)

---

## 6. Composer Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| ksfraser/exceptions | ^1.3 | Exception hierarchy (Domain, Utility, Calendar-specific) |
| ksfraser/traits | ^1.0 | Trait library (ValidatableTrait, TimestampTrait, etc.) |
| psr/event-dispatcher | ^2.0 | PSR-14 event dispatcher |
| eluceo/ical | ^2.0 | iCal generation |
| craigk5n/php-icalendar-core | ^1.0 | iCal parsing |

### 6.1 Exception Usage

All exceptions use `Ksfraser\Exceptions\Calendar\*` from the ksfraser/exceptions library:

| Exception | Extends | Description |
|-----------|---------|-------------|
| `Ksfraser\Exceptions\Calendar\CalendarException` | `RuntimeException` | Base calendar exception |
| `Ksfraser\Exceptions\Calendar\EntryNotFoundException` | `CalendarException` | Entry not found |
| `Ksfraser\Exceptions\Calendar\SourceNotFoundException` | `CalendarException` | Source not found |
| `Ksfraser\Exceptions\Calendar\InvalidDateRangeException` | `CalendarException` | Invalid date range |

---

## Integration Points

| Package | Integration |
|---------|-------------|
| ksf_ProjectManagement | Sync PM tasks as calendar entries |
| FA_CRM | Sync CRM communications (calls, meetings) |
| ksf_TimeTracking (future) | Link time entries to tasks |
| ksf_HRM (future) | Employee schedule, leave |
| Client data | Birthdays, anniversaries, renewal dates |
| ksf_Calendar_UI | FullCalendar.js frontend |
| eluceo/ical | iCal creation |
| craigk5n/php-icalendar-core | iCal parsing |

---

## Billing/Time Tracking Notes

- Time tracking entries are calendar entries (type=timetracking)
- Standard book time vs actuals configurable per task/project
- Time entries can auto-generate calendar entries
- Billing integration via FA invoicing

---

## Comparison to Reference CRMs

| Feature | KSF Calendar | SuiteCRM | vTiger | WebCalendar |
|---------|-------------|----------|--------|-------------|
| Unified view | Yes | Yes | Yes | Yes |
| PM tasks | Yes | Yes | Yes | No |
| CRM activities | Yes | Yes | Yes | No |
| Client dates | Yes | No | Limited | No |
| iCal import | Yes | Yes | Yes | Yes |
| iCal export | Yes | Yes | Yes | Yes |
| Multi-calendar | Yes | Yes | Yes | Yes |
| Recurring events | Yes | Yes | Yes | Yes |
| Book time vs actuals | Yes | No | No | No |