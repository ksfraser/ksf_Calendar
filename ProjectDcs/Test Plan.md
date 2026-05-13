# ksf_Calendar - Test Plan

## Document Information
- **Module**: ksf_Calendar (Calendar Management)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Introduction

### 1.1 Purpose
This test plan defines the testing strategy for the ksf_Calendar module, ensuring all functional requirements are met and the module operates correctly.

### 1.2 Scope
- Unit testing of entity classes
- Unit testing of service classes
- iCal import/export testing
- Synchronization testing
- Database operation testing

### 1.3 Test Environment
- **PHP Version**: 8.0+
- **Testing Framework**: PHPUnit
- **Database**: MySQL 5.7+ (test instance)
- **Dependencies**: eluceo/ical, Craigk5n/ICalendar

---

## 2. Testing Strategy

### 2.1 Test Levels

| Level | Description | Target Coverage |
|-------|-------------|-----------------|
| Unit | Entity and service classes | 100% |
| Integration | Database operations | Core methods |
| System | End-to-end workflows | Critical paths |

### 2.2 Test Types

| Type | Description |
|------|-------------|
| Functional | Feature verification |
| Regression | No existing features broken |
| Edge Cases | Invalid inputs, boundaries |
| Performance | Response times |

---

## 3. Test Cases

### 3.1 CalendarEntry Entity (TC-ENT)

#### TC-ENT-001: Create CalendarEntry Instance
**Preconditions**: None  
**Test Steps**:
1. Create CalendarEntry with required params
2. Verify object created

**Expected Result**: Object is instance of CalendarEntry

**Priority**: High

---

#### TC-ENT-002: Set and Get Title
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set title to "Team Meeting"
2. Call getTitle()

**Expected Result**: Returns "Team Meeting"

**Priority**: High

---

#### TC-ENT-003: Set Dates and Calculate Duration
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set startDate to "2026-05-13 09:00"
2. Set endDate to "2026-05-13 10:30"
3. Call getDuration()

**Expected Result**: Returns 5400 (seconds)

**Priority**: High

---

#### TC-ENT-004: Check isOverdue
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set endDate to yesterday
2. Set status to pending
3. Call isOverdue()

**Expected Result**: Returns true

**Priority**: High

---

#### TC-ENT-005: Check isToday
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set startDate to today
2. Call isToday()

**Expected Result**: Returns true

**Priority**: Medium

---

#### TC-ENT-006: Check isPast
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set endDate to yesterday
2. Call isPast()

**Expected Result**: Returns true

**Priority**: Medium

---

#### TC-ENT-007: Set All-Day Event
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set allDay to 'yes'
2. Call isAllDay()

**Expected Result**: Returns true

**Priority**: Medium

---

#### TC-ENT-008: Convert to Array
**Preconditions**: CalendarEntry instance created  
**Test Steps**:
1. Set various properties
2. Call toArray()

**Expected Result**: Returns array with all properties

**Priority**: High

---

#### TC-ENT-009: Create from Array
**Preconditions**: None  
**Test Steps**:
1. Call fromArray() with valid data
2. Verify properties set correctly

**Expected Result**: CalendarEntry created with data

**Priority**: High

---

#### TC-ENT-010: Create from PM Task (Mock)
**Preconditions**: Mock Task object  
**Test Steps**:
1. Create mock Task
2. Call fromPMTask()

**Expected Result**: CalendarEntry created with task data

**Priority**: Medium

---

#### TC-ENT-011: Create from CRM Activity
**Preconditions**: CRM activity array  
**Test Steps**:
1. Create activity array
2. Call fromCRMActivity()

**Expected Result**: CalendarEntry created

**Priority**: Medium

---

### 3.2 CalendarSource Entity (TC-SRC)

#### TC-SRC-001: Create CalendarSource Instance
**Preconditions**: None  
**Test Steps**:
1. Create CalendarSource with required params
2. Verify object created

**Expected Result**: Object is instance of CalendarSource

**Priority**: High

---

#### TC-SRC-002: Set Filters
**Preconditions**: CalendarSource instance created  
**Test Steps**:
1. Call setFilters() with array
2. Verify filters applied

**Expected Result**: Filters set correctly

**Priority**: High

---

#### TC-SRC-003: Get Enabled Source Types
**Preconditions**: CalendarSource with filters  
**Test Steps**:
1. Set filters for PM source
2. Call getEnabledSourceTypes()

**Expected Result**: Returns ['task', 'timetracking']

**Priority**: High

---

#### TC-SRC-004: Create PM Calendar
**Preconditions**: None  
**Test Steps**:
1. Call createPMCalendar()

**Expected Result**: Source with PM defaults

**Priority**: Medium

---

#### TC-SRC-005: Create CRM Calendar
**Preconditions**: None  
**Test Steps**:
1. Call createCRMTasksCalendar()

**Expected Result**: Source with CRM defaults

**Priority**: Medium

---

#### TC-SRC-006: Create Client Dates Calendar
**Preconditions**: None  
**Test Steps**:
1. Call createClientDatesCalendar()

**Expected Result**: Source with birthdays/anniversaries enabled

**Priority**: Medium

---

#### TC-SRC-007: Default Color by Source
**Preconditions**: None  
**Test Steps**:
1. Create PM source
2. Create CRM source
3. Check colors differ

**Expected Result**: PM is blue, CRM is green

**Priority**: Low

---

### 3.3 CalendarService (TC-SVC)

#### TC-SVC-001: Validate Entry Data - Valid
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call createEntry() with valid data
2. Verify entry created

**Expected Result**: Entry created successfully

**Priority**: High

---

#### TC-SVC-002: Validate Entry Data - Missing Title
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call createEntry() without title
2. Catch CalendarValidationException

**Expected Result**: Validation exception thrown

**Priority**: High

---

#### TC-SVC-003: Validate Entry Data - Missing Start Date
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call createEntry() without start_date
2. Catch CalendarValidationException

**Expected Result**: Validation exception thrown

**Priority**: High

---

#### TC-SVC-004: Validate Entry Data - Invalid Date Range
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call createEntry() with end before start
2. Catch CalendarValidationException

**Expected Result**: Validation exception thrown

**Priority**: High

---

#### TC-SVC-005: Get Entry - Exists
**Preconditions**: Mock DatabaseAdapter returning row  
**Test Steps**:
1. Call getEntry() with valid ID
2. Verify CalendarEntry returned

**Expected Result**: Entry returned

**Priority**: High

---

#### TC-SVC-006: Get Entry - Not Found
**Preconditions**: Mock DatabaseAdapter returning null  
**Test Steps**:
1. Call getEntry() with non-existent ID
2. Catch CalendarNotFoundException

**Expected Result**: Exception thrown

**Priority**: High

---

#### TC-SVC-007: Get Entries for Date Range
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call getEntriesForDateRange()
2. Verify entries returned

**Expected Result**: Array of CalendarEntry objects

**Priority**: High

---

#### TC-SVC-008: Get Entries with Filters
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call getEntriesForDateRange() with filters
2. Verify SQL includes filter conditions

**Expected Result**: Filtered results

**Priority**: High

---

#### TC-SVC-009: Delete Entry
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call deleteEntry() with valid ID
2. Verify UPDATE sets inactive=1

**Expected Result**: Entry soft-deleted

**Priority**: High

---

#### TC-SVC-010: Create Source
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call createSource() with data
2. Verify source created

**Expected Result**: Source saved

**Priority**: Medium

---

#### TC-SVC-011: Get Sources for User
**Preconditions**: Mock DatabaseAdapter  
**Test Steps**:
1. Call getSourcesForUser()
2. Verify sources returned

**Expected Result**: Array of CalendarSource

**Priority**: Medium

---

#### TC-SVC-012: Sync PM Tasks - ProjectService Available
**Preconditions**: Mock ProjectService returning tasks  
**Test Steps**:
1. Call syncPMTasks()
2. Verify tasks converted to entries

**Expected Result**: Entries created

**Priority**: Medium

---

#### TC-SVC-013: Sync PM Tasks - ProjectService Not Available
**Preconditions**: CalendarService without ProjectService  
**Test Steps**:
1. Call syncPMTasks()
2. Verify warning logged
3. Verify 0 returned

**Expected Result**: Graceful handling

**Priority**: Low

---

### 3.4 iCalService (TC-ICAL)

#### TC-ICAL-001: Export Single Entry
**Preconditions**: CalendarEntry instance  
**Test Steps**:
1. Call exportEntries() with one entry
2. Verify iCal string generated

**Expected Result**: Valid iCal output

**Priority**: High

---

#### TC-ICAL-002: Export Multiple Entries
**Preconditions**: Multiple CalendarEntry instances  
**Test Steps**:
1. Call exportEntries() with multiple entries
2. Verify all entries in output

**Expected Result**: Multiple VEVENTs in iCal

**Priority**: High

---

#### TC-ICAL-003: Export to File
**Preconditions**: CalendarEntry and file path  
**Test Steps**:
1. Call exportEntriesToFile()
2. Verify file created

**Expected Result**: File exists with content

**Priority**: Medium

---

#### TC-ICAL-004: Parse Valid iCal Content
**Preconditions**: Valid iCal string  
**Test Steps**:
1. Call importFromString() with valid iCal
2. Verify entries returned

**Expected Result**: CalendarEntry array

**Priority**: High

---

#### TC-ICAL-005: Parse iCal with All-Day Event
**Preconditions**: iCal with DTSTART;VALUE=DATE  
**Test Steps**:
1. Parse iCal
2. Verify entry has allDay='yes'

**Expected Result**: All-day flag set

**Priority**: Medium

---

#### TC-ICAL-006: Parse iCal with RRULE
**Preconditions**: iCal with recurrence rule  
**Test Steps**:
1. Parse iCal
2. Verify recurrenceRule set

**Expected Result**: RRULE stored

**Priority**: Medium

---

#### TC-ICAL-007: Map iCal Fields Correctly
**Preconditions**: iCal with various fields  
**Test Steps**:
1. Parse iCal
2. Verify SUMMARY→title, DTSTART→startDate, etc.

**Expected Result**: Correct field mapping

**Priority**: High

---

#### TC-ICAL-008: Handle Missing DTSTART
**Preconditions**: iCal without DTSTART  
**Test Steps**:
1. Parse iCal
2. Verify null returned for entry

**Expected Result**: Entry skipped or created without start

**Priority**: Low

---

#### TC-ICAL-009: Generate Public URL
**Preconditions**: CalendarSource instance  
**Test Steps**:
1. Call generatePublicUrl() with source and baseUrl
2. Verify URL format correct

**Expected Result**: URL with token generated

**Priority**: Medium

---

#### TC-ICAL-010: Export Source Filtered
**Preconditions**: CalendarSource and entries  
**Test Steps**:
1. Call exportSource() with PM source
2. Verify only task/timetracking entries included

**Expected Result**: Filtered export

**Priority**: Medium

---

### 3.5 Date/Time Handling (TC-DATE)

#### TC-DATE-001: Handle UTC Timezone
**Preconditions**: Entry with UTC dates  
**Test Steps**:
1. Create entry with UTC dates
2. Export to iCal
3. Verify timezone in output

**Expected Result**: UTC preserved

**Priority**: Medium

---

#### TC-DATE-002: Handle Different Timezones
**Preconditions**: Entry with non-UTC timezone  
**Test Steps**:
1. Create entry with America/New_York timezone
2. Verify timezone stored

**Expected Result**: Timezone preserved

**Priority**: Low

---

## 4. Performance Tests

### 4.1 Query Performance
| Test | Entries | Target Time |
|------|---------|-------------|
| Date range query | 1000 | < 500ms |
| Date range query | 10000 | < 2s |
| User filter | 1000 | < 300ms |

### 4.2 iCal Performance
| Test | Entries | Target Time |
|------|---------|-------------|
| Export | 100 | < 1s |
| Export | 1000 | < 5s |
| Import | 100 | < 2s |

---

## 5. Security Tests

### 5.1 Input Validation
| Test | Input | Expected |
|------|-------|----------|
| XSS in title | `<script>alert(1)</script>` | Escaped on display |
| SQL injection in filter | `1 OR 1=1` | Parameterized query |
| Invalid date format | `not-a-date` | Validation error |

### 5.2 Access Control
| Test | Scenario | Expected |
|------|----------|----------|
| Private entry visibility | User queries others' private | Hidden |
| Source visibility | Shared vs private source | Filtered |

---

## 6. Test Data

### 6.1 Sample CalendarEntry Data
```php
$sampleEntry = [
    'source' => CalendarEntry::SOURCE_USER,
    'source_id' => 'entry_001',
    'source_type' => CalendarEntry::TYPE_MEETING,
    'title' => 'Weekly Team Sync',
    'description' => 'Regular team meeting',
    'start_date' => '2026-05-13T09:00:00',
    'end_date' => '2026-05-13T10:00:00',
    'all_day' => 'no',
    'timezone' => 'UTC',
    'location' => 'Conference Room A',
    'assigned_to' => 'user_123',
    'status' => CalendarEntry::STATUS_CONFIRMED,
    'priority' => 'medium',
];
```

### 6.2 Sample iCal Content
```ical
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//KSFII//Calendar//EN
BEGIN:VEVENT
UID:event_001@ksfii.org
DTSTART:20260513T090000Z
DTEND:20260513T100000Z
SUMMARY:Team Meeting
DESCRIPTION:Weekly team sync
LOCATION:Conference Room A
END:VEVENT
END:VCALENDAR
```

---

## 7. Test Execution

### 7.1 Run Commands
```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test class
./vendor/bin/phpunit tests/Unit/CalendarEntryTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### 7.2 Pass/Fail Criteria
| Test Type | Required Pass Rate |
|-----------|-------------------|
| Unit Tests | 100% |
| Integration Tests | 95% |
| Critical Path | 100% |

---

## 8. Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| iCal library compatibility | Low | High | Version pinning |
| Timezone edge cases | Medium | Medium | Comprehensive testing |
| Large dataset performance | Medium | Medium | Query optimization |
| Sync conflicts | Low | Low | Idempotent operations |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*