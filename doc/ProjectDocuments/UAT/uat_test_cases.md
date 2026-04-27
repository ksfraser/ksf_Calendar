# ksf_FA_Calendar - UAT Test Cases

## Test Setup
- FA installed with: FA_PM, FA_CRM, FA_Calendar modules

---

## TC-001: View Calendar

**Preconditions**: User logged in, CALENDAR_VIEW permission

**Steps**:
1. Navigate to Calendar module
2. View monthly calendar

**Expected Result**:
- Calendar displays all events
- Events color-coded by source/type

---

## TC-002: Filter by Source

**Preconditions**: Multiple sources (PM tasks, CRM activities)

**Steps**:
1. Open calendar
2. Filter by "Project Management" only

**Expected Result**:
- Only PM tasks displayed
- Other sources hidden

---

## TC-003: Filter by Event Type

**Preconditions**: Multiple event types exist

**Steps**:
1. Open calendar  
2. Filter by "Task" type

**Expected Result**:
- Only task-type events shown

---

## TC-004: Create Calendar Event

**Preconditions**: User has CALENDAR_MANAGE permission

**Steps**:
1. Click "New Event"
2. Enter Subject: "Team meeting"
3. Set Date/Time
4. Select Source: "CRM"
5. Select Type: "Meeting"
6. Click Save

**Expected Result**:
- Event created
- Appears on calendar

---

## TC-005: Cross-Module Integration

**Preconditions**: FA_PM installed with tasks

**Steps**:
1. Navigate to calendar
2. View PM tasks

**Expected Result**:
- PM tasks appear as calendar events
- Clicking opens task details