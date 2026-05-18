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

## TC-004: Create Calendar Event (Timed)

**Preconditions**: User has CALENDAR_MANAGE permission

**Steps**:
1. Click "New Event"
2. Enter Subject: "Team meeting"
3. Set Start: 2026-05-18 10:00
4. Set End: 2026-05-18 11:00
5. Leave "All Day" unchecked
6. Click Save

**Expected Result**:
- Event created with start 10:00 and end 11:00
- Event appears as a timed block (not all-day) on the calendar
- Event is NOT shown in the all-day row

---

## TC-004a: Create Calendar Event — All-Day Flag

**Preconditions**: User has CALENDAR_MANAGE permission

**Steps**:
1. Click "New Event"
2. Enter Subject: "Birthday"
3. Check "All Day"
4. Set Date: 2026-05-18
5. Click Save

**Expected Result**:
- Event created with `all_day = 'yes'`
- Event appears in the all-day row in the week view
- No time is shown

---

## TC-004b: Create Calendar Event — Missing End Date Defaults to +15 min

**Preconditions**: User has CALENDAR_MANAGE permission

**Steps**:
1. Click "New Event"
2. Enter Subject: "Quick call"
3. Set Start: 2026-05-18 14:00
4. Leave End blank
5. Click Save

**Expected Result**:
- Event created with end = 14:15 (start + `DEFAULT_DURATION_MINUTES` = 15)
- Event appears as a 15-minute block on the calendar

---

## TC-005: Cross-Module Integration

**Preconditions**: FA_PM installed with tasks

**Steps**:
1. Navigate to calendar
2. View PM tasks

**Expected Result**:
- PM tasks appear as calendar events
- Clicking opens task details

---

## TC-006: Edit (Update) Existing Calendar Entry

**Preconditions**: At least one calendar entry exists; user has CALENDAR_MANAGE permission

**Steps**:
1. Open calendar
2. Click on an existing event to open the detail panel
3. Click the "Edit" button (blue)
4. Verify the modal opens pre-populated with: Subject, Start, End, All Day flag, Description
5. Change Subject to "Updated Meeting"
6. Change End time to one hour later
7. Click Save

**Expected Result**:
- Modal opens with all fields pre-populated from the existing entry
- After Save, event updates on the calendar immediately
- Subject and end time reflect the new values
- Other fields (invitees, source, type) are unchanged

---

## TC-006a: Edit — Toggle All-Day

**Preconditions**: A timed event exists (all_day = no)

**Steps**:
1. Open the event detail panel
2. Click "Edit"
3. Check the "All Day" checkbox
4. Click Save

**Expected Result**:
- Event is saved with `all_day = 'yes'`
- Event moves to the all-day row in week view

---

## TC-007: Create vs Edit Mode — Modal Title

**Preconditions**: Calendar visible

**Steps**:
1. Click "New Event" — verify modal title is "Add Calendar Entry"
2. Click an existing event's Edit button — verify modal title is "Edit Calendar Entry"

**Expected Result**:
- Correct title shown in each mode
- Submitting in create mode calls `create_entry`; edit mode calls `update_entry`
