**Data Flow Diagrams**

**Project:** Targeted Promotion & Data Management Portal

**Version:** 1.0

**Reporting Flow Strategy**

**1\. Data Access Approach: Direct vs. Aggregated**

- **Live Listings (Search Results):** The system shall query the main_registry table directly using **Pagination** (fetching 50 records at a time) to prevent memory overflows when viewing lists.
- **Analytical Widgets (Charts/Heatmaps):** Instead of loading raw rows, the backend will return **Aggregated JSON** (e.g., GROUP BY district COUNT(\*)). This ensures the dashboard loads in milliseconds even with millions of records.

**2\. Query Optimization (The "Single Table" Advantage)**

- Since all data lives in main_registry with a category discriminator, reports do **not** require complex JOIN or UNION operations.
- **Indexing Strategy:** We will apply composite indexes on frequently filtered columns:
    - INDEX(category, district, field_of_work) – To speed up the primary filters.
    - INDEX(age) – For the demographic pyramid.

**3\. The Reporting Execution Flow**

1.  **User Action:** Promotion manager selects "Gampaha District" + "Construction Services" filters on the Dashboard.
2.  **Request:** Frontend sends a GET request: /api/analytics?district=Gampaha§or=Construction.
3.  **Backend Processing:**
    - The **Controller** captures parameters.
    - The **Query Builder** dynamically appends WHERE clauses (ignoring empty filters).
    - **Security Check:** Ensures is_deleted = 0 and validation_status = Approved.

4.  **Database Response:** MySQL returns only the mathematical counts (e.g., { "Gampaha": 450, "Colombo": 200 }) rather than the full user details.
5.  **Visualization:** Chart.js (frontend) renders the graphs based on this lightweight JSON.

**4\. Export Strategy (Asynchronous Processing)**

- For **Excel Exports**, downloading 100,000 rows can time out a browser.
- **Flow:**
    1.  User clicks "Export".
    2.  Server generates the Excel file in a **Streamed Response** (writing row-by-row to the output) rather than building the whole file in RAM.
    3.  This prevents server crashes during large data dumps.

### **Data Flow Diagrams (DFD)**

A DFD maps how information travels. We will create two levels:

- **Level 0 (Context Diagram):** The high-level "Bird's Eye View".
- **Level 1 (Process Diagram):** The detailed view showing the "Error Sheet" loop and "Maker-Checker" logic.

#### **DFD Level 0: Context Diagram**

This diagram shows the system as a single "Black Box" and who interacts with it.

- **External Entities:**
    - **Agent:** Provides raw Excel files.
    - **Validator:** Provides decisions (Approve/Reject).
    - **Existing DB:** Provides User Credentials (Login).
    - **Management:** Consumes Analytics

#### **DFD Level 1: Detailed Process Flow**

This breaks the "Black Box" into the actual processes. It explicitly visualizes the **"Split Logic"** (Valid rows go to DB, Invalid rows go back to User).

**Key Processes:**

1.  **Authenticate:** Checks users against the Legacy DB.
2.  **Validate & Split:** The critical logic that separates good data from bad.
3.  **Manage Review:** The "Maker-Checker" status updates.
4.  **Generate Analytics:** Live querying for the dashboard.

#### **Location API Flow (Cascading Dropdowns)**

The system provides a lightweight location data API to power cascading Province → District → DS Division dropdowns.

**Data Source:** `config/srilanka.php` stores a hierarchical mapping (Province → Districts → DS Divisions). Flat lists are auto-generated from this hierarchy for backward compatibility with validation.

**Flow:**

1. **Page Load:** Frontend calls `GET /api/locations/provinces` → populates Province dropdown.
2. **Province Selected:** Frontend calls `GET /api/locations/districts?province={name}` → populates District dropdown, resets DS Division.
3. **District Selected:** Frontend calls `GET /api/locations/ds-divisions?district={name}` → populates DS Division dropdown.

**Validation (Server-side):** `RegistryValidator` enforces that the selected district belongs to the province, and the DS division belongs to the district, using closure-based cross-field validation rules.

#### **Single Form Entry Flow (Frontend → Backend)**

The following describes the complete data flow for the Single Form Entry feature as implemented.

**Frontend (Vue.js — `DataEntry.vue`):**

1. **Page Load:** Vue component mounts and calls `GET /api/locations/provinces` to populate the Province dropdown. A loading spinner is shown while fetching.
2. **User Interaction:** User selects category (Self-Employed/Trade), fills in fields. Cascading dropdowns trigger additional API calls for districts and DS divisions. Loading indicators appear during each fetch.
3. **Pre-Submit Client-Side Validation:** On "Submit Form" click, the `validateForm()` function checks:
    - All required fields are filled (full_name, province, district, ds_division, contact_number)
    - Contact number matches regex `^0\d{9}$`
    - WhatsApp number matches format if provided
    - Email format is valid if provided
    - Category-specific required fields (e.g., `field_of_work` for Self-Employed, `contact_person` for Trade)
    - If validation fails: red borders + inline error messages appear. Page auto-scrolls to top error banner. No API call is made.
4. **Payload Construction:** Irrelevant category fields are excluded entirely from the payload (not sent as empty strings). Only filled optional fields are included.

**Backend (Laravel — `RegistryController@storeSingle`):**

5. **Trim & Normalize:** Leading/trailing whitespace is stripped from all string fields. `normalizeNationalId()` is applied to `national_id_number` (uppercases `v`/`x`, handles scientific notation from Excel paste). Phone numbers are **not** normalized here — the form enforces strict format (`^0\d{9}$`) via client-side and server-side validation, so raw input is always canonical.
6. **Initial Format Check:** Validates `category` (in: Self-Employed, Trade) and `contact_number` (regex: `^0\d{9}$`) before any expensive DB operations.
7. **Main Registry Duplicate Check (1 DB call):** `MainRegistry::where('contact_number', ...)->where('category', ...)->exists()` — category-aware.
8. **Staging Duplicate Check (1 DB call):** `StagingData::where('validation_status', 'Pending')->where('data_payload->contact_number', ...)->where('data_payload->category', ...)->exists()` — uses JSON path arrow syntax (correct for scalar string values).
9. **Cross-Category Field Stripping:** Irrelevant fields are `unset()` before validation to prevent the `prohibited` rule from firing on empty fields passed from the form.
10. **Full Category-Aware Validation:** `RegistryValidator::validate($data)` — includes hierarchical location validation.
11. **Insert to Staging (1 DB call):** `StagingData::create(...)` with `submission_type = 'NEW'` and `batch_id = 'SINGLE-{timestamp}'`.
12. **Response:** Returns `201 Created` with `staging_id`. Frontend shows green success banner, resets form and dropdown option lists, auto-scrolls to banner.

**Total DB Calls Per Submission:** 3 (main_registry check + staging check + insert).

#### **Excel Bulk Upload Flow (Frontend → Backend)**

The following describes the complete data flow for the Excel Bulk Upload feature as implemented.

**Frontend (Vue.js — `DataEntry.vue`, Bulk Upload tab):**

1. **Template Download:** Agent clicks the "Download Template" button. Frontend navigates to `GET /api/registry/template` which streams a CSV file with all 14 required column headers and two example rows.
2. **File Selection:** Agent drags-and-drops a file onto the drop zone or uses the file browser. The frontend validates the extension (`.csv`, `.xls`, `.xlsx`) and size (≤10MB) before allowing submission. The file name and size (in KB or MB) are displayed.
3. **Submit:** Agent clicks "Upload & Process". Frontend constructs a `FormData` object appending the file and sends it via `POST /api/registry/upload`. A loading spinner is displayed.

**Backend (Laravel — `RegistryController@uploadExcel`):**

4. **File Validation:** Extension + real MIME type validation rejects files that are not `.csv`, `.xls`, or `.xlsx`. Size limit enforced at 10MB. MIME check prevents renamed malicious files from bypassing extension validation.
5. **Parsing — Multi-Sheet with `WithHeadingRow`:** `RegistryImport` (implements `WithMultipleSheets`) delegates to `SelfEmployedSheetImport` and `TradeSheetImport`. Both implement `WithHeadingRow` — the header row (row 1) is automatically consumed as the column key map. Rows are read in chunks of **500** via `WithChunkReading`.
6. **Column Mapping (by Header Name):** Each sheet maps columns by the **Excel header name** (lowercased, spaces → underscores) rather than by numeric index. This makes the mapping resilient to column reordering.
    - Self-Employed headers: `full_name`, `national_id_number`, `contact_number`, `province`, `district`, `ds_division`, `field_of_work`, `age`, `address`, `whatsapp_number`, `email`, `employees_count`.
    - Trade headers: `trade_name`, `national_id_number_of_contact_person`, `contact_number`, `province`, `district`, `ds_division`, `address`, `whatsapp_number`, `email`, `contact_person_name`, `members_count`.
7. **Empty Row Filtering:** Completely empty rows are skipped and not counted toward `total_processed`.
8. **Per-Row Normalization (in sheet importers):**
    - `normalizePhoneNumber()` applied to `contact_number` and `whatsapp_number` — strips non-digits, converts `94xxxxxxxxx` → `0xxxxxxxxx`, prepends `0` to 9-digit numbers.
    - `normalizeNationalId()` applied to `national_id_number` — converts Excel scientific notation to string, uppercases `v`/`x` suffix.
9. **In-File Duplicate Check (0 DB calls):** `RegistryImport` maintains a shared hash map (`contactNumbersInFile`) across all sheets and chunks. Each unique key is `"{contact_number}:{category}"`. Duplicates within the file are rejected immediately without a DB call.
10. **DB Duplicate Check — Hash Map Approach (2 DB calls per chunk):**
    - `MainRegistry::whereIn('contact_number', $chunk)->get(['contact_number','category'])->mapWithKeys(fn($r) => ["{$r->contact_number}:{$r->category}" => true])` — uses `toBase()` pattern for low-memory stdClass objects.
    - `StagingData::where('validation_status', 'Pending')->whereIn('data_payload->contact_number', $chunk)->pluck('data_payload')->mapWithKeys(...)` — uses `pluck()` to avoid hydrating full Eloquent models.
    - Both results are merged with the `+` array union operator (faster than `array_merge` for associative arrays). Duplicate check uses `isset()` — O(1) hash map lookup vs. O(n) `in_array()`.
11. **Cross-Category Field Stripping:** `unset()` removes irrelevant fields per category before running `RegistryValidator`, preventing the `prohibited` validation rule from failing on legitimately absent fields.
12. **Category-Aware Validation (per row, 0 DB calls):** `RegistryValidator::validate($data)` enforces all field, format, and hierarchical location rules. Failing rows are collected in `$importer->invalidRows` with concatenated error messages.
13. **Bulk Insert (1 DB call per chunk):** All valid rows in a chunk are batch-inserted into `staging_data` in a single `INSERT` statement. Wrapped in `DB::transaction()` — if a DB error occurs, the entire chunk is rolled back atomically.
14. **Response:** Returns JSON with `summary` (total_processed, valid_count, invalid_count), `invalid_rows` array (with error messages), and `batch_id`.

**Frontend (Post-Processing):**

15. **Results UI:** Displays the 3-card summary (total processed, sent for review, requires correction). A success banner confirms records were submitted.
16. **Error Sheet Generation (client-side, 0 network calls):** If `invalid_count > 0`, the "Download Error Sheet" button is shown. Clicking it builds a CSV in-browser from `invalid_rows`, appends an "Error Message" column, and triggers a download via a temporary object URL.

**Total DB Calls Per Upload:** Variable — per 500-row chunk: 2 reads (main_registry + staging_data) + 1 bulk insert.

#### **Rejection Dashboard Flow (Frontend → Backend)**

The following describes the complete data flow for the Rejection Dashboard feature, allowing Data Entry Operators to correct and resubmit records that were rejected by a Validator.

**Frontend (Vue.js — Rejection Dashboard page):**

1.  **Page Load:** Frontend calls `GET /api/registry/rejected` → receives a paginated list (15/page) of the current user's rejected staging records, ordered by most-recently-rejected first.
2.  **User Selects a Record:** User clicks a rejected record in the list.
3.  **Load Detail for Editing:** Frontend calls `GET /api/registry/rejected/{id}` → receives the full `StagingData` record including `data_payload` and `rejection_reason`. The edit form is pre-filled with the data. The rejection reason is displayed prominently so the Agent understands what to fix.
4.  **User Corrects Fields and Submits:** Frontend sends `POST /api/registry/rejected/{id}/resubmit` with the corrected payload.

**Backend (Laravel — `RegistryController@resubmitRejected`):**

5.  **Ownership + Status Guard:** Queries `staging_data` with `uploaded_by = Current::id()` AND `validation_status = 'Rejected'` + `findOrFail($id)`. Returns 404 if not found or not owned.
6.  **Category Field Stripping:** Cross-category null fields are unset before validation (same logic as `storeSingle`).
7.  **Full Category-Aware Validation:** `RegistryValidator::validate($data)` — enforces all field and location rules. Returns 422 on failure.
8.  **Main Registry Duplicate Check (1 DB call):** `MainRegistry::where('contact_number', ...)->where('category', ...)`. If `submission_type = 'UPDATE'`, the `target_record_id` row is excluded from the check.
9.  **Staging Pending Duplicate Check (1 DB call):** `StagingData::where('validation_status', 'Pending')->where('id', '!=', $staging->id)->where('data_payload->contact_number', ...)->where('data_payload->category', ...)`. Uses JSON path arrow syntax (not `whereJsonContains`). The `id !=` guard ensures the current record (which is `Rejected`, not `Pending`) is not accidentally matched.
10. **Status Reset (1 DB call):** Updates `data_payload = $data`, `validation_status = 'Pending'`, `rejection_reason = null`. Original `batch_id` and `submission_type` are preserved.
11. **Response:** Returns `200 OK` with `{ message: 'Record resubmitted successfully.', staging_id }`.

**Frontend (Post-Processing):**

12. **Success:** Display confirmation and redirect user to the rejections list (record should no longer appear there).

**Total DB Calls Per Resubmission:** 3 (main_registry check + staging_data check + update).
