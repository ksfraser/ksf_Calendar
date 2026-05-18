# ksf_Calendar / ksf_FA_Calendar - RTM (Requirements Traceability Matrix)

## Test Coverage — ksf_Calendar (133 tests, 381 assertions)

| Test Class | File | Tests |
|------------|------|-------|
| CalendarEntry | `CalendarEntryTest.php` | 44 |
| CalendarService | `CalendarServiceTest.php` | 29 |
| CalendarSource | `CalendarSourceTest.php` | 24 |
| CalendarInvitee | `CalendarInviteeTest.php` | 18 |
| CalendarEntryDTO | `CalendarEntryDTOTest.php` | 12 |
| CalendarEntryEvents | `CalendarEntryEventsTest.php` | 6 |
| **Total** | | **133** |

## Test Coverage — ksf_FA_Calendar (101 tests, 139 assertions)

| Test Class | File | Tests |
|------------|------|-------|
| FA CalendarService | `CalendarServiceTest.php` | 101 |
| **Total** | | **101** |

## Requirements to Test Cases

| Req ID | Requirement | Test Class | Test Case |
|--------|-------------|------------|-----------|
| CAL-001 | View calendar | FA CalendarService | TC-001 |
| CAL-002 | Filter by source | CalendarSource | TC-002 |
| CAL-003 | Filter by type | CalendarSource | TC-003 |
| CAL-004 | Create entry with correct end_date | CalendarService | TC-004, `testCreateEntryWithExplicitEndDate` |
| CAL-004a | Create entry defaults end_date via DEFAULT_DURATION_MINUTES | CalendarService | `testCreateEntryDefaultsEndDateFromDuration` |
| CAL-004b | Create entry all_day stored as 'yes'/'no' string strictly | CalendarService | `testCreateEntryAllDayStrictComparison` |
| CAL-005 | Cross-module integration (PM tasks) | FA CalendarService | TC-005 |
| CAL-006 | User preferences | CalendarSource | Pref tests |
| CAL-007 | Edit (update) existing entry | CalendarService | TC-006, `testUpdateEntryAllDay` |
| CAL-007a | Update entry preserves all_day strict comparison | CalendarService | `testUpdateEntryAllDayStrictComparison` |
| CAL-008 | AJAX CRUD (create / update / delete) | FA CalendarService | CRUD tests |
| CAL-009 | Invitees on entry | CalendarInvitee | Invitee tests |

## Bug Fixes Traced

| Bug | Root Cause | Fix Location | Regression Test |
|-----|-----------|-------------|-----------------|
| end_date always set to now | `isset()` truthy for empty string | `CalendarService::createEntry()` | `testCreateEntryWithEmptyEndDate` |
| All events appear all-day | `'no'` string is truthy in PHP | `CalendarService::createEntry()` | `testCreateEntryAllDayStrictComparison` |
| Update ignores all_day field | Missing all_day branch in updateEntry | `CalendarService::updateEntry()` | `testUpdateEntryAllDayStrictComparison` |
