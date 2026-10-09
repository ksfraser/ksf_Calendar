# legacy/ — code quarantined out of the autoload path

Nothing here is autoloaded, and nothing here is covered by the test suite.

## iCalService.php.unloadable

A 371-line iCal import/export service that **cannot be loaded at all**:

- `use Eluceo\iCal\PropertyFactory\FactoryTrait;` — that trait was removed in
  eluceo/ical 2.x. The installed version is 2.14.0, so this is 1.x API.
  A missing trait is a **compile-time fatal**, not a catchable error.
- `use Craigk5n\ICalendar\Reader;` and `use Craigk5n\ICalendar\Property;` — a
  **second** iCal library, `craigk5n/icalendar`, which is neither in
  `composer.json` nor in `composer.lock`, so it is not installed.
- Nothing references the class: not `src/`, not `tests/`, not `composer.json`,
  not `phpunit.xml`. It was invisible because nothing ever tried to load it.

It was moved here rather than deleted because iCal import/export is plausibly
wanted functionality. Porting it is real work and needs a decision that has not
been made: either rewrite against eluceo/ical 2.x, or add `craigk5n/icalendar`
as a second dependency.

`tools/check_autoload.php` exists so this cannot recur unnoticed — it flags any
file under `src/` that is not autoloadable or whose dependencies do not resolve.
