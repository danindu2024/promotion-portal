# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased] - 2026-02-17

### Added

- **Location Validation:** Implemented strict validation for `district` and `ds_division` fields.
    - Added `config/srilanka.php` containing 25 Districts and 326 DS Divisions.
    - Updated `RegistryValidator` to enforce these values using `Rule::in`.
- **Province Property:** Added `province` field to support broader filtering and analysis.
    - Updated Database Schema, Model, and Validator.
    - Added 9 provinces to `config/srilanka.php`.
- **Data Integrity:**
    - Made `contact_number` a required field in Database and Validation.
    - Added **Unique Key** on `contact_number` in `main_registry` — one phone = one person.
    - Updated `field_of_work` ENUM values to match the expanded sector list.
- **Update Data Strategy:** Documented a dedicated "Update Data" module, separate from Add Data.
    - Agents search by Name/DS Division, select a record, edit in a pre-filled form, and submit for validation.
    - Updated `SRS.md` (Sections 3.2–3.6) and `UI-UX design.md` with full specifications.
- **Duplicate Check Strategy:** Documented batch duplicate detection (in-batch + DB `WHERE IN`) and single form "user already exists" error.
    - Excel: 2 DB calls total regardless of file size. Summary shows Valid/Duplicate/Error counts.
    - Single Form: Instant error if `contact_number` already exists.

### Removed

- **GN Division:** Removed `gn_division` field from the system due to maintenance impracticality.
    - Dropped `gn_division` column from `main_registry` table.
    - Removed `gn_division` from `MainRegistry` model `$fillable`.
    - Removed `gn_division` validation rules.
    - Updated Technical Documentation (`SRS.md`, `UI-UX design.md`) to reflect this removal.

### Changed

- **Age Validation:** Made `age` field optional (nullable) for 'Self-Employed' category as data availability is inconsistent.
- **Analytics:** Removed Age-based charts (Pyramids, Demographics) from the system requirements and design, as age is no longer a reliable decision-making factor.
