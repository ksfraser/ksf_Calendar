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

---

## TC-008: Add Invitees to Entry

**Preconditions**: User has CALENDAR_MANAGE permission, at least one entry exists

**Steps**:
1. Open an existing event's detail panel
2. Click "Invite" / "Add Invitee" button
3. Search for a user by name in the invitee search field
4. Select the user from results
5. Click Save

**Expected Result**:
- Selected user appears in invitees list
- User can search by name, email, contact type
- Invitee row stored in fa_cal_invitees

---

## TC-009: Remove Invitee from Entry

**Preconditions**: Entry has at least one invitee

**Steps**:
1. Open event detail panel
2. View invitees section
3. Click "Remove" on an invitee
4. Confirm removal (if prompted)
5. Click Save

**Expected Result**:
- Invitee removed from the entry
- fa_cal_invitees row deleted or marked inactive
- Other invitees unchanged

---

## TC-010: Search Invitees

**Preconditions**: Multiple person registry entries exist (users, CRM contacts)

**Steps**:
1. Open event detail panel
2. Click "Invite"
3. Type partial name in search field
4. View results

**Expected Result**:
- Results from multiple contact types (fa_user, crm_contact) shown
- Each result shows type_label (e.g. "System User", "CRM Contact")
- Results grouped or labeled by contact type
- The search calls the searchInvitees endpoint

---

## TC-011: Free/Busy Visibility

**Preconditions**: Multiple entries with invitees exist

**Steps**:
1. Open create event modal
2. Add invitees to the new event
3. System checks free/busy via getFreeBusy endpoint

**Expected Result**:
- Busy times shown for conflicting invitees
- Free times shown as available
- Only time range checked (not details of other events)
- getFreeBusy endpoint returns busy blocks per contact_id

---

## TC-012: Viewable_by Filter (RBAC Integration)

**Preconditions**: Multiple calendar entries, some user is invited to, some not

**Steps**:
1. Log in as a regular FA user
2. Navigate to calendar with `viewable_by` filter (e.g. "My Invitations" view)

**Expected Result**:
- Only entries where user is invited (via fa_cal_invitees → crm_persons → crm_contacts) are shown
- Non-invited entries are hidden
- The two-legged person-registry JOIN works correctly

---

## TC-013: My Invitations View

**Preconditions**: User has invitee entries from other users

**Steps**:
1. Navigate to "My Invitations" calendar view
2. Browse entries

**Expected Result**:
- All entries where user is an invitee are displayed
- Entries show "Invited" badge or indicator
- User can respond (Accept/Decline/Tentative) to each invitation
- Response status updates in fa_cal_invitees

---

## TC-014: Contact Type Labels in Search

**Preconditions**: Person registry has entries of multiple contact types

**Steps**:
1. Open invitee search
2. Search for a term that matches across contact types

**Expected Result**:
- Each result shows `type_label` field (e.g. "System User", "CRM Contact", "Resource")
- Labels come from server-side `r.type_label` (not hardcoded on frontend)
- Frontend falls back to `typeLabel[r.contact_type] || r.contact_type` if `r.type_label` missing
