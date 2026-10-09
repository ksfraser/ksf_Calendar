# legacy/ — retired code kept for reference

Nothing here is autoloaded and nothing here is tested.

## iCalService (removed 2026-10)

The original `iCalService` was written against eluceo/ical **1.x** and could not be
loaded at all — `PropertyFactory\FactoryTrait` and `Parameter\Value\ValueDateTime`
were both removed in 2.x. Its import half also referenced `craigk5n/icalendar`,
which is in neither `composer.json` nor `composer.lock`.

It was quarantined here rather than deleted because **.ical export is how KSF
sends event invites to customers and employees**, so the export half was worth
keeping. It has since been ported and lives at
`src/ksfraser/Calendar/Service/ICalService.php`.

Recovery: `git show 24cdef9:legacy/iCalService.php.unloadable`
