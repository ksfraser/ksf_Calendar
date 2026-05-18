# KSF Calendar - Architecture

## Package Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                    ksf_Calendar_UI                           │
│            (FullCalendar.js standalone UI)                   │
└─────────────────────────────────────────────────────────────┘
                              │ requires
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    ksfraser/ksf-calendar                    │
│                 (Composer package - Packagist)               │
│                                                                     │
│  CalendarService │ iCalService │ CalendarEntry │ CalendarSource       │
│  DTOs │ Events │ Contracts │ Exceptions                              │
│                                                                     │
│  eluceo/ical (export) + craigk5n/php-icalendar-core (import)       │
└─────────────────────────────────────────────────────────────┘
                              │ aggregates
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                         Sources                              │
│                                                                     │
│  ksf_ProjectManagement → PM tasks                            │
│  FA_CRM              → CRM calls, meetings                  │
│  ksf_TimeTracking    → Time entries (future)                 │
│  ksf_HRM             → Employee schedules (future)          │
│  Client data         → Birthdays, anniversaries, renewals   │
│  External iCal       → Subscribed feeds                     │
└─────────────────────────────────────────────────────────────┘
```

## Directory Structure (ksf_Calendar)

```
ksf_Calendar/
├── src/Ksfraser/Calendar/
│   ├── Entity/
│   │   ├── CalendarEntry.php          # Any calendar entry
│   │   └── CalendarSource.php         # Filter/view configuration
│   ├── DTO/
│   │   └── CalendarEntryDTO.php       # FullCalendar.js ready DTO
│   ├── Event/
│   │   └── CalendarEntryEvents.php     # Created/Updated/Deleted
│   ├── Service/
│   │   ├── CalendarService.php        # Core CRUD + sync
│   │   └── iCalService.php           # Import/export
│   ├── Contract/
│   │   ├── DatabaseAdapterInterface.php
│   │   └── ProjectServiceInterface.php
│   └── Exception/
│       └── CalendarException.php
├── tests/Unit/
├── doc/ProjectDocuments/
│   ├── Requirements/
│   └── Architecture/
├── composer.json
└── phpunit.xml
```

## CalendarService Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `DEFAULT_DURATION_MINUTES` | `15` | Default entry length when `end_date` is absent or empty. Applied in `createEntry()` and `updateEntry()` via `applyDefaultDuration()`. All-day entries use ±1 day instead. |

## FullCalendar.js Integration

The `CalendarEntryDTO` has a `toFullCalendarArray()` method that produces:

```javascript
{
  id: 1,
  title: "Meeting with client",
  start: "2024-01-15T10:00:00",
  end: "2024-01-15T11:00:00",
  allDay: false,
  color: "#2196F3",
  source: "crm",
  sourceType: "meeting",
  editable: true,
  extendedProps: {
    source: "crm",
    customer_id: "CUST001",
    project_id: null,
    task_id: null,
    status: "pending"
  }
}
```

## Multi-Calendar Filter System

Users can display multiple calendars simultaneously:

1. **Enable calendars** in settings → Calendars
2. **Assign colors** to each source
3. **Filter by type** within each calendar (tasks, calls, meetings, etc.)
4. **Toggle visibility** per calendar on/off
5. **Drag-and-drop** entries between calendars (if source permits)

## iCal Integration

### Export
- Generate .ics file for any date range
- Filter by calendar source
- Public URL with token for sharing

### Import
- Subscribe to external iCal URL
- Parse VEVENT components into CalendarEntry
- Sync periodically (configurable)

## Time Tracking Flow

```
User works on Task
        │
        ├─► HRM Time Entry (actual hours)
        │           │
        │           └─► Calendar Entry (type=timetracking)
        │
        └─► PM Task update (progress %)
                    │
                    └─► Book time vs actuals rule
                                │
                                ├─► Standard book time → Billing
                                └─► Actual hours → Tracking
```

## Edit Flow (ksf_FA_Calendar)

```
User clicks event detail panel → "Edit" button
    │
    ├─► GET get_entry?id=N
    │       └─► CalendarService::getEntry(N)
    │               └─► returns {event: FC-array, invitees: [...]}
    │
    ├─► openModalForEdit(event, invitees)
    │       - Sets editId = N
    │       - Pre-populates Subject, Start, End, All Day, Description
    │       - Renders existing invitees in modal lists
    │       - Sets modal title to "Edit Calendar Entry"
    │
    └─► User submits
            └─► POST update_entry {id, title, start_date, end_date, all_day, ...}
                    └─► CalendarService::updateEntry(N, data)
                            └─► applyDefaultDuration() if end missing
```

## Database Tables

| Table | Purpose |
|-------|---------|
| fa_cal_entries | All calendar entries (unified) |
| fa_cal_sources | Calendar views/subscriptions |