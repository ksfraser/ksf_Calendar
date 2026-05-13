# ksf_Calendar - Use Case

## Document Information
- **Module**: ksf_Calendar (Calendar Management)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Use Case Overview

### 1.1 Actors
| Actor | Description |
|-------|-------------|
| User | Creates and views calendar entries |
| Project Manager | Syncs tasks to calendar |
| Sales Rep | Views customer activities |
| Administrator | Configures calendar sources |
| System | Automatic synchronization |

### 1.2 Use Case Categories
- Entry Management
- Source Configuration
- Synchronization
- iCal Integration
- Reporting

---

## 2. Entry Management Use Cases

### UC-CAL-001: Create Calendar Entry

**Actor**: User  
**Trigger**: User creates new calendar event

**Pre-conditions**:
- User authenticated
- User has calendar access

**Steps**:
1. User clicks "New Event" or "Add Entry"
2. System displays entry form
3. User fills required fields (title, start date)
4. User optionally fills:
   - End date/time
   - Description
   - Location
   - Assigned user
   - Linked entities (customer, project)
   - Priority
   - Reminder settings
5. User clicks Save
6. System validates input
7. System creates entry in database
8. System dispatches CalendarEntryCreatedEvent
9. System redirects user to calendar view

**Post-conditions**:
- Entry created in database
- Entry appears in calendar view
- Event listeners notified

**Failure Scenarios**:
- F1: Missing title → Show validation error
- F2: Invalid date range → Show "End must be after start"
- F3: Database error → Show error, log details

---

### UC-CAL-002: View Calendar (Date Range)

**Actor**: User  
**Trigger**: User opens calendar view

**Pre-conditions**:
- User authenticated

**Steps**:
1. User navigates to calendar
2. User selects view (day/week/month)
3. User optionally sets date range filter
4. System queries entries within range
5. System applies user's source filters
6. System returns entries as CalendarEntry objects
7. UI renders entries on calendar

**Post-conditions**:
- Calendar displays all relevant entries
- Entries grouped by date
- Color-coded by source

---

### UC-CAL-003: View Entry Details

**Actor**: User  
**Trigger**: User clicks on calendar entry

**Pre-conditions**:
- Entry exists

**Steps**:
1. User clicks entry on calendar
2. System fetches entry by ID
3. System displays entry details modal/page
4. User can view all entry fields
5. User can see linked entities (customer, project)

**Post-conditions**:
- Entry details displayed
- Edit/Delete options shown (if permitted)

---

### UC-CAL-004: Update Entry

**Actor**: User  
**Trigger**: User edits existing entry

**Pre-conditions**:
- Entry exists
- User has edit permission

**Steps**:
1. User opens entry details
2. User clicks Edit
3. System displays edit form with current values
4. User modifies fields
5. User clicks Save
6. System validates changes
7. System updates database
8. System dispatches CalendarEntryUpdatedEvent
9. System refreshes calendar view

**Post-conditions**:
- Entry updated
- Changes reflected in calendar
- Event listeners notified

---

### UC-CAL-005: Delete Entry

**Actor**: User  
**Trigger**: User deletes entry

**Pre-conditions**:
- Entry exists
- User has delete permission

**Steps**:
1. User opens entry details
2. User clicks Delete
3. System shows confirmation dialog
4. User confirms deletion
5. System sets inactive = 1
6. System dispatches CalendarEntryDeletedEvent
7. Entry hidden from calendar

**Post-conditions**:
- Entry marked as inactive
- Entry not returned in queries
- Audit trail preserved

---

### UC-CAL-006: Filter Entries by Source

**Actor**: User  
**Trigger**: User toggles source visibility

**Pre-conditions**:
- Multiple sources configured

**Steps**:
1. User opens calendar settings/sidebar
2. User sees list of calendar sources
3. User checks/unchecks sources
4. System filters entries based on selection
5. Calendar re-renders with filtered entries

**Post-conditions**:
- Only selected sources' entries shown
- Filter preference saved per user

---

## 3. Source Management Use Cases

### UC-SRC-001: Configure Calendar Source

**Actor**: Administrator  
**Trigger**: Admin sets up new calendar source

**Pre-conditions**:
- Admin authenticated
- Source not already configured

**Steps**:
1. Admin navigates to Calendar Settings
2. Admin clicks Add Source
3. Admin enters:
   - Name (display name)
   - Type (internal, external, ical)
   - Source identifier (pm, crm, etc.)
   - URL (for external/ical sources)
   - Color
   - Visibility (private, shared, public)
   - Entry type filters
4. Admin saves source
5. System creates source in database
6. Source appears in user's source list

**Post-conditions**:
- Source configured and enabled
- Entries from source appear (if filters allow)

---

### UC-SRC-002: Enable/Disable Source

**Actor**: User/Administrator  
**Trigger**: User toggles source enabled status

**Steps**:
1. User navigates to calendar settings
2. User finds source
3. User toggles enabled switch
4. System updates source.enabled
5. Entries from source shown/hidden accordingly

**Post-conditions**:
- Source enabled/disabled state persisted

---

### UC-SRC-003: Create Predefined Source

**Actor**: System/Administrator  
**Trigger**: Initial setup or reset

**Steps**:
1. System calls CalendarSource factory method:
   - `createPMCalendar()`
   - `createCRMTasksCalendar()`
   - `createClientDatesCalendar()`
   - `createHRMCalendar()`
2. System applies predefined filters
3. System sets default color
4. System saves to database

**Post-conditions**:
- Standard sources created with correct filters

---

## 4. Synchronization Use Cases

### UC-SYNC-001: Sync PM Tasks

**Actor**: System/Project Manager  
**Trigger**: Manual trigger or scheduled job

**Pre-conditions**:
- PM module installed
- ProjectService available

**Steps**:
1. System calls CalendarService.syncPMTasks(userId)
2. Service fetches tasks from ProjectService
3. Service queries existing entries for user (past 1 year to future 1 year)
4. For each task:
   - Check if entry exists (source=pm, sourceId=taskId)
   - If not exists: Create CalendarEntry.fromPMTask(task)
5. Service returns sync count
6. Service logs sync result

**Post-conditions**:
- All PM tasks synced as calendar entries
- Existing entries preserved
- No duplicates created

---

### UC-SYNC-002: Sync CRM Activities

**Actor**: System/Sales Rep  
**Trigger**: Manual trigger or scheduled job

**Pre-conditions**:
- CRM module installed

**Steps**:
1. System calls CalendarService.syncCRMActivities(userId)
2. Service queries fa_crm_communications for user
3. Filters to past 1 year
4. For each activity:
   - Check if entry exists
   - If not exists: Create CalendarEntry.fromCRMActivity()
5. Return sync count

**Post-conditions**:
- CRM communications synced
- Entry type set to 'call', 'meeting', etc.

---

## 5. iCal Integration Use Cases

### UC-ICAL-001: Export Calendar to iCal

**Actor**: User  
**Trigger**: User exports calendar

**Pre-conditions**:
- User has entries to export

**Steps**:
1. User navigates to calendar export
2. User selects:
   - Date range
   - Sources to include
   - Entry types
3. System queries entries matching criteria
4. System calls iCalService.exportEntries(entries)
5. Service converts each entry to iCalEvent
6. Service generates RFC 5545 compliant iCal string
7. System presents download

**Post-conditions**:
- iCal file generated with all matching entries
- File downloadable

---

### UC-ICAL-002: Import iCal from URL

**Actor**: User/Administrator  
**Trigger**: User imports external calendar

**Pre-conditions**:
- Valid iCal URL accessible

**Steps**:
1. User enters iCal URL
2. System calls iCalService.importFromUrl(url)
3. Service fetches URL content (with timeout)
4. Service parses iCal content
5. Service extracts VEVENT components
6. For each VEVENT:
   - Create CalendarEntry entity
   - Map DTSTART/DTEND/SUMMARY/etc.
7. Return array of CalendarEntry objects
8. System offers to save entries

**Post-conditions**:
- Entries parsed and ready for saving

**Failure Scenarios**:
- F1: URL inaccessible → Throw exception, log
- F2: Invalid iCal format → Skip invalid entries, continue
- F3: No VEVENT found → Return empty array

---

### UC-ICAL-003: Import iCal from File

**Actor**: User  
**Trigger**: User uploads iCal file

**Pre-conditions**:
- User has iCal file on local system

**Steps**:
1. User uploads .ics file
2. System calls iCalService.importFromFile(filePath)
3. System reads file content
4. System parses iCal content
5. System returns entries

**Post-conditions**:
- File parsed, entries ready

---

### UC-ICAL-004: Generate Shareable iCal URL

**Actor**: User  
**Trigger**: User wants to share calendar

**Pre-conditions**:
- Source configured

**Steps**:
1. User selects calendar source
2. User clicks "Get iCal Link"
3. System calls iCalService.generatePublicUrl(source, baseUrl)
4. System generates URL with token
5. User copies URL

**URL Format**: `baseUrl/ical/{sourceId}/{token}/export.ics`

**Post-conditions**:
- Unique, time-limited URL generated
- URL can be imported by external calendars

---

### UC-ICAL-005: Import from String

**Actor**: System/Integration  
**Trigger**: API receives iCal content

**Steps**:
1. System receives iCal content as string
2. System calls iCalService.importFromString(content)
3. System parses and returns entries

**Post-conditions**:
- Entries ready for processing

---

## 6. Reporting Use Cases

### UC-REPORT-001: Get Entry Count by Date

**Actor**: User/Administrator  
**Trigger**: Dashboard or statistics view

**Pre-conditions**: None

**Steps**:
1. System calls CalendarService.getEntryCountByDate(date)
2. Service queries entries for that date
3. Service groups by sourceType
4. Returns count by type and total

**Output**:
```php
[
    'event' => 5,
    'meeting' => 3,
    'task' => 10,
    'total' => 18
]
```

**Post-conditions**:
- Counts returned for display

---

### UC-REPORT-002: Get Entries for Customer

**Actor**: Sales Rep  
**Trigger**: Viewing customer timeline

**Pre-conditions**:
- Customer exists

**Steps**:
1. User opens customer record
2. User navigates to Calendar/Timeline
3. System calls getEntriesForCustomer(customerId, start, end)
4. System returns chronological entries
5. UI displays timeline

**Post-conditions**:
- Customer's calendar entries displayed

---

## 7. Use Case Summary

| UC ID | Use Case | Actor | Priority |
|-------|----------|-------|----------|
| UC-CAL-001 | Create Calendar Entry | User | Critical |
| UC-CAL-002 | View Calendar | User | Critical |
| UC-CAL-003 | View Entry Details | User | High |
| UC-CAL-004 | Update Entry | User | High |
| UC-CAL-005 | Delete Entry | User | High |
| UC-CAL-006 | Filter Entries | User | Medium |
| UC-SRC-001 | Configure Source | Admin | High |
| UC-SRC-002 | Enable/Disable Source | User | Medium |
| UC-SRC-003 | Create Predefined Source | System | High |
| UC-SYNC-001 | Sync PM Tasks | System | Medium |
| UC-SYNC-002 | Sync CRM Activities | System | Medium |
| UC-ICAL-001 | Export to iCal | User | High |
| UC-ICAL-002 | Import from URL | User | High |
| UC-ICAL-003 | Import from File | User | Medium |
| UC-ICAL-004 | Generate Shareable URL | User | Medium |
| UC-ICAL-005 | Import from String | System | Medium |
| UC-REPORT-001 | Entry Count by Date | User | Low |
| UC-REPORT-002 | Entries for Customer | User | Medium |

---

## 8. Sequence Diagrams

### 8.1 Create Entry Sequence
```
User           CalendarService      Database      EventDispatcher
  |                  |                 |                |
  |--Create Entry-->|                  |                |
  |                 |--Validate------->|                |
  |                 |<--OK-------------|                |
  |                 |--INSERT--------->|                |
  |                 |<--Success--------|                |
  |                 |                  |----Dispatch---->|
  |                 |<--Event Sent-----|                |
  |<--Return Entry--|                  |                |
```

### 8.2 PM Sync Sequence
```
Scheduler      CalendarService      ProjectService     Database
   |                  |                    |               |
   |--Trigger Sync-->|                    |               |
   |                 |--getTasksByAssignee->               |
   |                 |<--Task[]----------|                |
   |                 |                    |               |
   |                 |--Check Existing-->|               |
   |                 |<--Existing[]-------|               |
   |                 |                    |               |
   |                 | For each task:     |               |
   |                 |  |--Not Found------|               |
   |                 |  |--INSERT Entry-->|               |
   |                 |  |<--Created--------|               |
   |<--Return Count--|                    |               |
```

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*