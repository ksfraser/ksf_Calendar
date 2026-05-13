# ksf_Calendar - UAT Plan

## Document Information
- **Module**: ksf_Calendar (Calendar Management)
- **Version**: 1.0.0
- **Date**: 2026-05-13
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Introduction

### 1.1 Purpose
This UAT Plan defines user acceptance tests for the ksf_Calendar module from an end-user perspective.

### 1.2 Scope
- Calendar entry creation and management
- Multi-source event viewing
- iCal import/export
- Source configuration
- Filtering and navigation

### 1.3 Test Environment
- **PHP**: 8.0+
- **Browser**: Chrome/Firefox latest
- **Database**: MySQL 5.7+ test instance

### 1.4 Stakeholders
- End Users (all departments)
- Project Managers
- Sales Representatives
- Administrators

---

## 2. UAT Test Cases

### 2.1 Calendar Entry Management (UAT-ENT)

#### UAT-ENT-001: Create Calendar Event
**Objective**: Verify user can create calendar events

**Test Scenario**:
1. Navigate to Calendar
2. Click "New Event"
3. Enter title: "Q2 Planning Meeting"
4. Select start date: tomorrow
5. Select start time: 10:00 AM
6. Select end time: 11:30 AM
7. Enter location: "Conference Room B"
8. Click Save

**Expected Result**: Event appears on calendar

**Acceptance Criteria**:
- [ ] Event created in database
- [ ] Event visible on calendar
- [ ] Correct date/time shown
- [ ] Location displayed

---

#### UAT-ENT-002: Create All-Day Event
**Objective**: Verify user can create all-day events

**Test Scenario**:
1. Create new event
2. Check "All Day" option
3. Set date: next Monday
4. Enter title: "Company Holiday"
5. Save

**Expected Result**: All-day event appears

**Acceptance Criteria**:
- [ ] Event spans full day
- [ ] No time displayed
- [ ] Correct date shown

---

#### UAT-ENT-003: Create Entry with Reminder
**Objective**: Verify reminder functionality

**Test Scenario**:
1. Create new event
2. Enable reminder
3. Set reminder: 15 minutes before
4. Save

**Expected Result**: Reminder configured

**Acceptance Criteria**:
- [ ] Reminder flag set
- [ ] Minutes recorded
- [ ] Reminder will trigger (test later)

---

#### UAT-ENT-004: Edit Calendar Entry
**Objective**: Verify user can update entries

**Test Scenario**:
1. Find existing event
2. Click to open details
3. Click Edit
4. Change title
5. Change time
6. Save

**Expected Result**: Changes saved

**Acceptance Criteria**:
- [ ] Title updated
- [ ] Time updated
- [ ] Updated timestamp set

---

#### UAT-ENT-005: Delete Calendar Entry
**Objective**: Verify user can delete entries

**Test Scenario**:
1. Find existing event
2. Open details
3. Click Delete
4. Confirm deletion

**Expected Result**: Entry removed

**Acceptance Criteria**:
- [ ] Entry not visible
- [ ] Entry marked as inactive
- [ ] Database updated

---

#### UAT-ENT-006: View Entry Details
**Objective**: Verify entry details display

**Test Scenario**:
1. Click on calendar entry
2. View details panel

**Expected Result**: All details visible

**Acceptance Criteria**:
- [ ] Title shown
- [ ] Date/time shown
- [ ] Description shown
- [ ] Location shown
- [ ] Linked entities shown

---

### 2.2 Calendar Navigation (UAT-NAV)

#### UAT-NAV-001: View Month Calendar
**Objective**: Verify month view displays correctly

**Test Scenario**:
1. Open Calendar
2. Select Month view
3. Navigate to next month

**Expected Result**: Month calendar displayed

**Acceptance Criteria**:
- [ ] All days visible
- [ ] Events shown on correct dates
- [ ] Navigation works

---

#### UAT-NAV-002: View Week Calendar
**Objective**: Verify week view displays correctly

**Test Scenario**:
1. Open Calendar
2. Select Week view
3. View time grid

**Expected Result**: Week calendar displayed

**Acceptance Criteria**:
- [ ] All days of week visible
- [ ] Time slots displayed
- [ ] Events positioned correctly

---

#### UAT-NAV-003: View Day Calendar
**Objective**: Verify day view displays correctly

**Test Scenario**:
1. Open Calendar
2. Select Day view
3. View specific date

**Expected Result**: Day calendar displayed

**Acceptance Criteria**:
- [ ] Single day shown
- [ ] Hourly slots visible
- [ ] Events at correct times

---

#### UAT-NAV-004: Navigate Between Dates
**Objective**: Verify navigation works

**Test Scenario**:
1. View May 2026
2. Click Next/Previous arrows
3. Use date picker

**Expected Result**: Calendar navigates

**Acceptance Criteria**:
- [ ] Previous/Next month works
- [ ] Date picker opens
- [ ] Jump to today works

---

### 2.3 Multi-Source Viewing (UAT-SRC)

#### UAT-SRC-001: View PM Tasks on Calendar
**Objective**: Verify PM module tasks appear

**Test Scenario**:
1. Ensure PM module installed
2. Create task in PM
3. Sync PM to calendar (if manual)
4. View calendar

**Expected Result**: Tasks appear as entries

**Acceptance Criteria**:
- [ ] PM source visible
- [ ] Tasks shown on calendar
- [ ] Correct dates/durations

---

#### UAT-SRC-002: View CRM Activities on Calendar
**Objective**: Verify CRM activities appear

**Test Scenario**:
1. Ensure CRM module installed
2. Create meeting in CRM
3. Sync to calendar
4. View calendar

**Expected Result**: Activities appear

**Acceptance Criteria**:
- [ ] CRM source visible
- [ ] Activities shown
- [ ] Linked to CRM records

---

#### UAT-SRC-003: Filter by Source
**Objective**: Verify source filtering works

**Test Scenario**:
1. View calendar with multiple sources
2. Open source filters
3. Uncheck PM source
4. View calendar

**Expected Result**: PM entries hidden

**Acceptance Criteria**:
- [ ] Filter panel opens
- [ ] PM entries hidden
- [ ] Other sources still visible

---

#### UAT-SRC-004: Toggle Source Visibility
**Objective**: Verify source toggle

**Test Scenario**:
1. Open calendar settings
2. Find PM source
3. Disable PM source
4. View calendar

**Expected Result**: PM entries hidden

**Acceptance Criteria**:
- [ ] Toggle works
- [ ] Entries hidden immediately
- [ ] Setting persists

---

### 2.4 iCal Integration (UAT-ICAL)

#### UAT-ICAL-001: Export to iCal
**Objective**: Verify iCal export works

**Test Scenario**:
1. Select date range
2. Click Export to iCal
3. Download file

**Expected Result**: iCal file downloaded

**Acceptance Criteria**:
- [ ] File downloads
- [ ] File has .ics extension
- [ ] File opens in calendar apps

---

#### UAT-ICAL-002: Export Includes All Entries
**Objective**: Verify export completeness

**Test Scenario**:
1. Create 5 events
2. Export to iCal
3. Import into external calendar app

**Expected Result**: All 5 events visible

**Acceptance Criteria**:
- [ ] All entries included
- [ ] Titles correct
- [ ] Times correct

---

#### UAT-ICAL-003: Import iCal from URL
**Objective**: Verify URL import works

**Test Scenario**:
1. Get valid iCal URL (e.g., Google calendar)
2. In Calendar, import from URL
3. Enter URL
4. Execute import

**Expected Result**: Entries imported

**Acceptance Criteria**:
- [ ] URL accepted
- [ ] Entries imported
- [ ] Entries viewable

---

#### UAT-ICAL-004: Import iCal File
**Objective**: Verify file import works

**Test Scenario**:
1. Download sample .ics file
2. Use import function
3. Select file
4. Execute import

**Expected Result**: Entries imported

**Acceptance Criteria**:
- [ ] File selection works
- [ ] Parsing succeeds
- [ ] Entries created

---

#### UAT-ICAL-005: Generate Shareable Link
**Objective**: Verify public URL generation

**Test Scenario**:
1. Open source settings
2. Click "Get iCal Link"
3. Copy generated URL

**Expected Result**: Valid URL generated

**Acceptance Criteria**:
- [ ] URL displayed
- [ ] URL contains source ID
- [ ] URL contains token

---

### 2.5 Source Configuration (UAT-CONFIG)

#### UAT-CONFIG-001: View Calendar Sources
**Objective**: Verify source list displays

**Test Scenario**:
1. Navigate to Calendar Settings
2. View Sources tab

**Expected Result**: List of sources shown

**Acceptance Criteria**:
- [ ] Sources listed
- [ ] Status shown (enabled/disabled)
- [ ] Color shown

---

#### UAT-CONFIG-002: Create iCal Source
**Objective**: Verify source creation

**Test Scenario**:
1. Click Add Source
2. Enter name: "External Calendar"
3. Select type: iCal
4. Enter URL
5. Set color: blue
6. Save

**Expected Result**: Source created

**Acceptance Criteria**:
- [ ] Source saved
- [ ] Source appears in list
- [ ] URL verified

---

#### UAT-CONFIG-003: Configure Source Filters
**Objective**: Verify filter configuration

**Test Scenario**:
1. Edit existing source
2. Uncheck "Events"
3. Check "Tasks"
4. Save

**Expected Result**: Filters updated

**Acceptance Criteria**:
- [ ] Filters saved
- [ ] Behavior changes
- [ ] Only selected types shown

---

### 2.6 Filtering and Search (UAT-FILTER)

#### UAT-FILTER-001: Filter by Entry Type
**Objective**: Verify type filtering

**Test Scenario**:
1. Open filters
2. Select "Meetings only"
3. Apply filter

**Expected Result**: Only meetings shown

**Acceptance Criteria**:
- [ ] Filter applies
- [ ] Other types hidden
- [ ] Results correct

---

#### UAT-FILTER-002: Filter by Assignee
**Objective**: Verify assignee filtering

**Test Scenario**:
1. Open filters
2. Select specific user
3. Apply filter

**Expected Result**: User's entries shown

**Acceptance Criteria**:
- [ ] Filter applies
- [ ] Correct entries shown
- [ ] Others hidden

---

#### UAT-FILTER-003: Filter by Date Range
**Objective**: Verify date filtering

**Test Scenario**:
1. Open date filter
2. Select custom range
3. Apply filter

**Expected Result**: Entries in range shown

**Acceptance Criteria**:
- [ ] Date picker works
- [ ] Results match range
- [ ] Clear filter works

---

### 2.7 Mobile Responsiveness (UAT-MOBILE)

#### UAT-MOBILE-001: View Calendar on Mobile
**Objective**: Verify mobile view

**Test Scenario**:
1. Open calendar on mobile device
2. Navigate through views

**Expected Result**: Mobile-optimized display

**Acceptance Criteria**:
- [ ] View renders correctly
- [ ] Touch navigation works
- [ ] Scrollable

---

#### UAT-MOBILE-002: Create Entry on Mobile
**Objective**: Verify mobile entry creation

**Test Scenario**:
1. On mobile, tap to add event
2. Fill form
3. Save

**Expected Result**: Event created

**Acceptance Criteria**:
- [ ] Form usable on mobile
- [ ] Date picker works
- [ ] Save completes

---

## 3. Sign-Off Criteria

### 3.1 Test Completion Metrics
- **Total UAT Test Cases**: 25+
- **Passed**: [ ]
- **Failed**: [ ]
- **Blocked**: [ ]
- **Pass Rate**: [ ]%

### 3.2 Critical Path Tests (Must Pass)
- [ ] Create calendar event
- [ ] View calendar (month/week)
- [ ] Edit calendar entry
- [ ] Export to iCal
- [ ] Import from iCal
- [ ] Filter entries

### 3.3 Sign-Off Table
| Test Area | Tester | Date | Result |
|-----------|--------|------|--------|
| Entry Management | | | Pass/Fail |
| Calendar Navigation | | | Pass/Fail |
| Multi-Source Viewing | | | Pass/Fail |
| iCal Integration | | | Pass/Fail |
| Source Configuration | | | Pass/Fail |
| Filtering | | | Pass/Fail |
| Mobile | | | Pass/Fail |

---

## 4. Defect Reporting

### 4.1 Severity Levels
- **Critical**: Calendar unusable, data loss
- **High**: Feature not working as specified
- **Medium**: Feature partially working
- **Low**: Cosmetic issue

### 4.2 Defect Report Template
```
ID: [Number]
Test Case: [UAT-XXX-###]
Environment: [Details]
Expected: [What should happen]
Actual: [What happened]
Severity: [Critical/High/Medium/Low]
Tester: [Name]
Date: [Date]
Screenshots: [Attach if applicable]
```

---

## 5. Success Criteria

### 5.1 Go/No-Go Decision
Module passes UAT when:
1. 100% critical test cases pass
2. 90% overall test cases pass
3. No Critical defects open
4. Business sign-off obtained

### 5.2 Issue Resolution
| Severity | Resolution |
|----------|------------|
| Critical | Must fix before release |
| High | Should fix before release |
| Medium | Release OK with known issues |
| Low | Can defer to next release |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-13*