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
- Full CRUD (Create, Read, Update, Delete) for CalendarEntry
- Edit (update) existing entries by ID
- Query by date range, user, customer, project
- Sync from PM (ksf_ProjectManagement)
- Sync from CRM (FA_CRM communications)
- Source management
- `DEFAULT_DURATION_MINUTES = 15`: if `end_date` is absent/empty when creating or updating an entry, the end is set to `start + DEFAULT_DURATION_MINUTES`. For all-day entries the default is ±1 day instead.
- `all_day` stored strictly as the string `'yes'` or `'no'`. The string `'no'` must **not** be treated as truthy (PHP non-empty-string bug). Comparison must be `=== 'yes'`.

#### iCalService
- Export entries to iCal format (eluceo/ical)
- Import from URL/file/string (php-icalendar-core)
- Generate public iCal feed URLs
- Filter export by source

### 3a. ksf_FA_Calendar AJAX Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `create_entry` | POST (JSON) | Create new entry; returns `{id}` |
| `get_entry` | GET `?id=N` | Fetch one entry as FC-array + invitees |
| `update_entry` | POST (JSON) | Update existing entry by `id` field |
| `delete_entry` | POST (JSON) | Delete entry by `id` |
| `add_invitee` | POST (JSON) | Add invitee to entry |
| `remove_invitee` | POST (JSON) | Remove invitee from entry |
| `get_entries` | GET | Query entries by date range |
| `search_invitees` | GET `?q=term&contact_type=crm_contact,fa_user` | Search persons for invitee dropdown |
| `get_free_busy` | GET `?ids=1,2,3&start=...&end=...` | Check invitee availability |

All POST handlers read from `php://input` (JSON body), not `$_POST`.

### 3b. CalendarInvitee Entity

#### CalendarInvitee

Represents a person invited to a calendar entry:

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key (fa_cal_invitees.id) |
| entry_id | int | FK to fa_cal_entries.id |
| contact_id | int | FK to 0_crm_contacts.id (person registry) |
| contact_type | string | 'fa_user', 'crm_contact', 'hrm_employee', 'resource' |
| response_status | string | 'pending', 'accepted', 'declined', 'tentative' |
| created_at | datetime | Auto |
| updated_at | datetime | Auto |

#### Contact Type Registry

The `contact_type` field maps to 0_crm_categories.type:

| invitee contact_type | crm_categories.type | Description |
|---------------------|---------------------|-------------|
| fa_user | user | FA system user (0_users) |
| crm_contact | crm_contact | CRM contact (contacts) |
| hrm_employee | employee | HRM employee |

Each FA module seeds its own crm_categories row on install (ksf_FA_RBAC seeds 'user', ksf_FA_CRM seeds 'customer'/'family', ksf_FA_HRM seeds 'employee').

#### Person Registry

Invitees are resolved through the FA person registry:

```
fa_cal_invitees.contact_id → 0_crm_contacts.id
0_crm_contacts.entity_id → 0_crm_persons.id
0_crm_contacts.type → 0_crm_categories.type
```

Every FA user MUST have a person registry entry (crm_persons + crm_contacts with type='user') for `viewable_by` filtering to work correctly. This is provisioned by ksf_FA_RBAC module.

### 4. Events (PSR-14)

- `CalendarEntryCreatedEvent`
- `CalendarEntryUpdatedEvent`
- `CalendarEntryDeletedEvent`

### 5. Database Schema (fa_cal_ prefix)

Tables: fa_cal_entries, fa_cal_invitees

```sql
CREATE TABLE fa_cal_invitees (
    id              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entry_id        INT(11) UNSIGNED NOT NULL,
    contact_id      INT(11) DEFAULT NULL,
    contact_type    VARCHAR(50) NOT NULL DEFAULT 'fa_user',
    response_status ENUM('pending','accepted','declined','tentative') NOT NULL DEFAULT 'pending',
    created_at      DATETIME DEFAULT NULL,
    updated_at      DATETIME DEFAULT NULL,
    INDEX idx_entry (entry_id),
    INDEX idx_contact (contact_id, contact_type),
    CONSTRAINT fk_invitee_entry FOREIGN KEY (entry_id) REFERENCES fa_cal_entries(id) ON DELETE CASCADE
);
```

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

---

## RBAC Integration

### Viewable_by SQL Filter

Calendar entries are filtered by invitee status using the `viewable_by` parameter:

```sql
-- viewable_by = 'invited' or viewable_by = current user ID
-- Two-legged JOIN through person registry:

-- Leg 1: FA user direct match (when viewer is an FA user)
SELECT e.* FROM fa_cal_entries e
JOIN fa_cal_invitees i ON i.entry_id = e.id
WHERE i.contact_id = :viewerContactId
  AND i.contact_type = 'fa_user'

UNION

-- Leg 2: All other contact types via crm_contacts EXISTS
SELECT e.* FROM fa_cal_entries e
WHERE EXISTS (
    SELECT 1 FROM fa_cal_invitees i
    JOIN 0_crm_contacts c ON c.id = i.contact_id
    WHERE i.entry_id = e.id
      AND c.entity_id = :viewerPersonId
      AND c.type IN (:contactTypes)
)
```

### ksfraser/rbac Integration

When ksfraser/rbac is active, calendar entry visibility is additionally gated by the RBAC `0_rbac_record_access` JOIN (per the standard SQL enforcement pattern). The RBAC check is applied ON TOP of the viewable_by filter — both must pass.

### Module Registration

Calendar registers with RBAC:
- record_type: 'entry'
- projections: 'public' (title, start, end, all_day), 'full' (all fields including private flag, description)
- allow_invite: true