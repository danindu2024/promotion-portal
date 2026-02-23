# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased] - 2026-02-23

### Added

- **Frontend Architecture:** Replaced Vanilla JS strategy with a **Vue.js 3 + Inertia.js + Tailwind CSS v4** stack to handle complex interactive UI while keeping a monolithic deployment.
- **Single Form Data Entry:** Implemented the UI (`DataEntry.vue`) and backend logic for manual record creation.
    - Cascading dropdowns for Province → District → DS Division.
    - Dynamic fields based on "Self-Employed" vs "Trade" categories.
    - Comprehensive client-side validation with inline error feedback.
- **Excel Bulk Upload:** Implemented a full-featured bulk data entry pipeline:
    - **Frontend (`DataEntry.vue`):** Drag-and-drop file zone; displays selected file name and size (in KB/MB). Upload & Process button with spinner. Post-processing 3-card results summary (Total, Valid, Invalid). Client-side CSV "Error Sheet" generation appending exact backend error messages to rejected rows.
    - **Backend (`RegistryController@uploadExcel`):** Extension-based file validation (accepts `.csv`, `.xls`, `.xlsx`) bypassing unreliable MIME type detection. Phone Number Smart Normalizer (`normalizePhoneNumber()`) silently fixes Excel-stripped leading zeros, `+94` and `94` country codes before validation. In-batch deduplication (0 DB calls). DB duplicate check against `main_registry` AND `staging_data` (2 DB calls). Full `RegistryValidator` category-aware validation per row. Bulk insert of valid rows in a single DB call.
    - **Template Download (`GET /api/registry/template`):** Streams a CSV with all 14 required column headers and two commented example rows (one Self-Employed, one Trade) for Agent reference.
- **Backend APIs:** Created supporting endpoints for form submissions (`POST /api/registry/single`) and location fetching.
    - Enforced robust dual-layer duplicate checking against `main_registry` and `staging_data`.
    - Automatically routes valid submissions to the Staging area for Maker-Checker review.
- **User Manual (`docs/user-manual.md`):** Renamed and consolidated the stakeholder user manual, adding a comprehensive Section 6 covering the complete Excel Bulk Upload workflow (template, normalizer, upload, results, and error sheet).

### Fixed

- **RegistryValidator:** Fixed critical bug where `getRules()` closures couldn't access `$data`. Now passes full `$data` array to `getRules()` and extracts `$category` internally.
- **MainRegistry Model:** Added missing `BelongsTo` import for `approver()` and `deleter()` relationships.
- **VerifyBackendLogic:** Removed dead `markAsValid()` call (method no longer exists in `StagingData`). Removed duplicate `address` and `district` keys in test data array.

### Changed

- **Documentation Sync:** Updated `SDD.md`, `SRS.md`, `authentication-strategy.md`, and `UI-UX design.md` to match actual implementation:
    - Corrected `staging_data` statuses from 5 to 3 (`Pending`, `Rejected`, `Approved`).
    - Updated indexes to match actual composite indexes in migration.
    - Fixed `address` nullability, `audit_logs` missing columns, validation rules, and age range.

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
