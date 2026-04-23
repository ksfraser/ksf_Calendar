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

## Database Tables

| Table | Purpose |
|-------|---------|
| fa_cal_entries | All calendar entries (unified) |
| fa_cal_sources | Calendar views/subscriptions |