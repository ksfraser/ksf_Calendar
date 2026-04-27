# Legacy API mapping

This document maps deprecated legacy `class.*` APIs in `src/Ksfraser/FileLegacy/` to the PSR-4 namespaced equivalents under `src/Ksfraser/File/`.

## `ksf_file` → `Ksfraser\\File\\KsfFile` / `Ksfraser\\File\\WriteFile`

Legacy file: `src/Ksfraser/FileLegacy/class.ksf_file.php`

Covered by `Ksfraser\\File\\KsfFile`:
- `open()`
- `open_for_write()`
- `close()`
- `make_path()` / `pathExists()`
- `fileExists()`
- `get_all_contents()`
- `getFileContents()`
- `fread()`
- `write_chunk()` / `write_line()`
- `unlink()` / `delete()`
- `getNumberOfLinesInfile()`
- `uploadFileName()` (kept for compatibility; uses `$_FILES['files']`)

Notes:
- The legacy class extends `fa_origin` (eventloop + `set/get`). The namespaced `KsfFile` is intentionally independent from FA/eventloop.

## `ksf_file_csv` → `Ksfraser\\File\\KsfFileCsv` / `Ksfraser\\File\\FileIO`

Legacy file: `src/Ksfraser/FileLegacy/class.ksf_file_csv.php`

Covered by `Ksfraser\\File\\KsfFileCsv`:
- `readcsv_line()`
- `readcsv_entire()`
- `write_array_to_csv()`

Also available via the format-aware API:
- `Ksfraser\\File\\FileIO::fget()` / `fput()` with `.csv` paths.

Notes:
- Legacy had additional internal counters/`set('fieldcount', ...)` patterns; namespaced code exposes data via getters.

## `file_download` → `Ksfraser\\File\\Legacy\\FileDownload` / `Ksfraser\\File\\ResourceReader`

Legacy file: `src/Ksfraser/FileLegacy/class.file_download.php`

Covered:
- `Ksfraser\\File\\Legacy\\FileDownload` is a deprecated shim keeping the legacy eventloop-driven configuration methods.
- For new code, prefer reading URLs via `Ksfraser\\File\\ResourceReader` + `FileIO` formats.

## `ksf_file_upload` → (partially) `Ksfraser\\File\\Legacy\\KsfFileUpload`

Legacy file: `src/Ksfraser/FileLegacy/class.ksf_file_upload.php`

Status:
- Upload + UI rendering is framework-specific (FrontAccounting helpers, `ksf_ui_class`, global UI functions).
- A deprecated shim `Ksfraser\\File\\Legacy\\KsfFileUpload` exists for basic single-file CSV parsing and a minimal HTML form.

Not fully migrated yet:
- `process_files()`, `copy_file()`, `file_put_contents()` behavior and the FA UI helpers used by `upload_form()`.

If you want full coverage here, the next step is to split upload handling into SRP services (request parsing, validation, storage, format parse) and provide adapter(s) for the FA UI layer.
