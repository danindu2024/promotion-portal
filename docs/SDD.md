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
    - **Agent / Data Entry Operator:** Provides raw Excel files and single-form entries.
    - **Validator:** Provides decisions (Approve/Reject) on staged records.
    - **Admin:** Manages users and views full analytics.
    - **Management:** Consumes analytics and reports.

#### **DFD Level 1: Detailed Process Flow**

This breaks the "Black Box" into the actual processes. It explicitly visualizes the **"Split Logic"** (Valid rows go to DB, Invalid rows go back to User).

**Key Processes:**

1.  **Authenticate:** Checks user credentials against the portal's own `users` table using BCrypt password verification.
2.  **Validate & Split:** The critical logic that separates good data from bad.
3.  **Manage Review:** The "Maker-Checker" status updates.
4.  **Generate Analytics:** Live querying for the dashboard.
5.  **Manage Users:** Admin creates, edits, and deletes user accounts via the User Management module.

## 5. Database Schema Definition

### 5.0 Users (`users`)

Stores all portal user accounts. Managed exclusively by administrators via the User Management module.

| Column | Type | Nullable | Description |
| :--- | :--- | :--- | :--- |
| `user_id` | BigInt | No | Primary Key (auto-increment) |
| `name` | String | No | Full name of the user |
| `username` | String | No | Login username (Unique) |
| `password` | String | No | BCrypt-hashed password |
| `province` | String | No | User's assigned province |
| `district` | String | No | User's assigned district |
| `ds_division` | String | No | User's assigned DS Division |
| `access_level` | String | No | `admin`, `decision maker`, `validator`, `data entry` |

### 5.1 Main Registry (`main_registry`)

The central table storing all beneficiary data.

| Column            | Type      | Nullable | Description                                                 |
| :---------------- | :-------- | :------- | :---------------------------------------------------------- |
| `id`              | BigInt    | No       | Primary Key                                                 |
| `category`        | Enum      | No       | 'Self-Employed' or 'Trade'                                  |
| `full_name`       | String    | No       | Beneficiary / organization name                             |
| `address`         | String    | Yes      | Physical address                                            |
| `province`        | String    | No       | Province (Indexed). Cascading dropdown — parent of District |
| `district`        | String    | No       | District (Indexed). Must belong to the selected Province    |
| `ds_division`     | String    | No       | DS Division. Must belong to the selected District           |
| `contact_number`  | String    | No       | Primary phone (Unique key — one number = one person)        |
| `whatsapp_number` | String    | Yes      | WhatsApp contact                                            |
| `email`           | String    | Yes      | Email address                                               |
| `age`             | Integer   | Yes      | Age (Self-Employed only)                                    |
| `field_of_work`   | Enum      | **Yes**  | Business sector — 10 categories (Self-Employed only, **required when category = Self-Employed**) |
| `employees_count` | Integer   | Yes      | Number of employees (Self-Employed only)                    |
| `contact_person`  | String    | Yes      | Contact person name (Trade only, **required when category = Trade**) |
| `members_count`   | Integer   | Yes      | Number of members (Trade only)                              |
| `approved_by`     | BigInt    | **No**   | ID of the Validator (Foreign Key -> users.user_id)          |
| `approved_at`     | Timestamp | **No**   | Time of approval                                            |
| `is_deleted`      | Boolean   | No       | Soft delete flag (Default: false)                           |
| `deleted_by`      | BigInt    | **Yes**  | ID of the user who deleted the record (NULL if active)      |
| `deleted_at`      | Timestamp | **Yes**  | Time of deletion (NULL if active)                           |
| `deletion_reason` | Text      | Yes      | Reason for deletion                                         |

**Indexes:**

- `idx_search_ds_field(category, province, district, ds_division, field_of_work)` — Composite index for full search path down to field of work
- `idx_search_district_field(category, province, district, field_of_work)` — Composite index for district-level field of work queries
- `idx_location_hierarchy(province, district, ds_division)` — Location cascade hierarchy
- `UNIQUE(contact_number)` — One phone number = one person

### 5.1.1 Location API (Cascading Dropdowns)

The system provides a hierarchical location data API backed by `config/srilanka.php`, which stores a Province → District → DS Division mapping. Three read-only endpoints serve this data:

| Endpoint                      | Method | Parameters            | Response                                    |
| :---------------------------- | :----- | :-------------------- | :------------------------------------------ |
| `/api/locations/provinces`    | GET    | —                     | JSON array of 9 provinces                   |
| `/api/locations/districts`    | GET    | `province` (required) | JSON array of districts in that province    |
| `/api/locations/ds-divisions` | GET    | `district` (required) | JSON array of DS divisions in that district |

**Validation:** The `RegistryValidator` enforces hierarchical consistency — a district must belong to the selected province, and a DS division must belong to the selected district. Invalid combinations are rejected with descriptive error messages.

### 5.2 Staging Data (`staging_data`)

Holding area for raw uploads before approval.

| Column              | Type   | Nullable | Description                                          |
| :------------------ | :----- | :------- | :--------------------------------------------------- |
| `id`                | BigInt | No       | Primary Key                                          |
| `batch_id`          | String | No       | Group ID for the uploaded file (Indexed)             |
| `data_payload`      | JSON   | No       | Raw row data from Excel                              |
| `validation_status` | Enum   | No       | 'Pending', 'Rejected', 'Approved' (Default: Pending) |
| `submission_type`   | Enum   | No       | 'NEW' or 'UPDATE'                                    |
| `target_record_id`  | BigInt | Yes      | FK to `main_registry.id` (Only for Updates)          |
| `rejection_reason`  | Text   | Yes      | Reason for rejection                                 |
| `uploaded_by`       | BigInt | No       | ID of the contents uploader (FK → users.user_id)     |
| `reviewed_by`       | BigInt | Yes      | ID of the reviewer (FK → users.user_id)              |

### 5.3 Audit Logs (`audit_logs`)

Security and compliance tracking.

| Column          | Type      | Nullable | Description                           |
| :-------------- | :-------- | :------- | :------------------------------------ |
| `id`            | BigInt    | No       | Primary Key                           |
| `user_id`       | BigInt    | Yes      | Actor ID (Nullable for failed logins) |
| `event_type`    | String    | No       | e.g. 'AUTH_FAILURE', 'SEARCH_QUERY'   |
| `action`        | String    | No       | Specific action name                  |
| `target_module` | String    | Yes      | Module targeted by the action         |
| `ip_address`    | String    | No       | Request IP address                    |
| `details`       | Text      | Yes      | Snapshot of old vs new values         |
| `metadata`      | JSON      | Yes      | Context (search terms, record counts) |
| `user_agent`    | String    | Yes      | Browser user agent string             |
| `created_at`    | Timestamp | No       | Timestamp (auto-set, no `updated_at`) |

## 6. Frontend Architecture (Vue + Inertia + Tailwind)

To meet the requirements for a modern, responsive user interface capable of handling dynamic cascading forms, complex interactive charts, and rich data tables, the frontend is built using the **Vue.js 3 + Inertia.js + Tailwind CSS v4** stack.

### 6.1 Unified Deployment (Virtual Machine Optimization)

The project requires deployment on a Virtual Machine (VM). Using a decoupled Single Page Application (SPA) architecture (e.g., separate Node.js frontend and PHP backend) would introduce unnecessary infrastructure overhead, requiring two servers and complex CORS management.

**Inertia.js** solves this by allowing us to build a fully modern Vue.js SPA that lives **inside** the Laravel monolith.

- **Single Server:** The entire application (frontend + backend) is served via standard PHP/Nginx on the VM. No Node.js server required in production.
- **Native Routing:** Inertia bridges Vue components directly to Laravel controllers and routing via `routes/web.php`, eliminating the need to build and maintain separate REST APIs just for page navigations.

### 6.2 Build & Configuration

| Component           | File                            | Description                                                                                                                                                                                                      |
| ------------------- | ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Vite Config**     | `vite.config.js`                | Configures Laravel plugin, Vue plugin with asset URL transforms, and `@` alias for `resources/js/`                                                                                                               |
| **Vue Entry Point** | `resources/js/app.js`           | Bootstraps `createInertiaApp()` with page component resolution via `import.meta.glob`                                                                                                                            |
| **Blade Root**      | `resources/views/app.blade.php` | Root HTML document with `@vite()` and `@inertia` directives, Google Fonts (Inter, Roboto Mono)                                                                                                                   |
| **PostCSS Config**  | `postcss.config.cjs`            | Uses `@tailwindcss/postcss` (Tailwind v4's separated PostCSS plugin) and `autoprefixer`. File uses `.cjs` extension because `package.json` has `"type": "module"`                                                |
| **Tailwind Theme**  | `resources/css/app.css`         | Uses Tailwind v4 syntax: `@import "tailwindcss"` + `@theme { }` block. Custom design tokens (Royal Blue, Slate Grey, etc.) are defined inline via CSS custom properties. No external `tailwind.config.js` needed |

### 6.3 Frontend File Structure

```
resources/
├── css/
│   └── app.css                     # Tailwind v4 import + @theme design tokens
├── js/
│   ├── app.js                      # Vue + Inertia bootstrap
│   ├── bootstrap.js                # Axios defaults (Laravel default)
│   ├── Layouts/
│   │   └── AppLayout.vue           # Sidebar + main content wrapper (Royal Blue theme)
│   └── Pages/
│       ├── Registry/
│       │   ├── DataEntry.vue       # Single Form Entry + Bulk Upload tabs
│       │   ├── Review.vue          # Maker-Checker review queue
│       │   └── Dashboard.vue       # Analytics & filtering dashboard
│       └── Admin/
│           └── UserManagement.vue  # Admin user CRUD with location dropdowns
└── views/
    └── app.blade.php               # Root Blade template
```

### 6.4 Fulfilling Core Functionalities

- **Government Aesthetics:** **Tailwind CSS v4** with custom `@theme` tokens strictly enforces the Royal Blue (`#0056b3`) and Off-White (`#f8f9fa`) palette defined in the UI/UX specifications.
- **Data Visualization & Analytics:** Vue's reactive ecosystem allows seamless integration with reporting libraries (e.g., Chart.js, ApexCharts, Leaflet) to render the required demographic pyramids, sector pie charts, and regional heat maps.
- **Complex UI Interactions:** The cascading Location dropdowns (Province -> District -> DS Division), dynamic category-aware form fields, and client-side validation with inline error feedback are all handled through Vue's reactive state management.
- **Single Form Entry UX:** Client-side validation runs before API calls. Loading spinners appear during dropdown fetches. Double-click submission is prevented. The page auto-scrolls to success/error alerts.

### 6.5 Web Routes (Inertia Pages)

| Route | Vue Component | Description |
| --- | --- | --- |
| `GET /` | — | Redirects to `/data-entry` |
| `GET /data-entry` | `Pages/Registry/DataEntry.vue` | Single Form Entry + Bulk Upload tabs |
| `GET /review` | `Pages/Registry/Review.vue` | Maker-Checker review queue |
| `GET /dashboard` | `Pages/Registry/Dashboard.vue` | Analytics & filtering dashboard |
| `GET /admin/users` | `Pages/Admin/UserManagement.vue` | Admin user management page |

### 6.6 API Routes (JSON Endpoints)

All routes below (except `/login` / `/logout`) are protected by `auth:sanctum` middleware.

| Route | Method | Rate Limit | Controller Method | Description |
| --- | --- | --- | --- | --- |
| `/api/locations/provinces` | GET | 60/min | `LocationController@provinces` | Returns all 9 Sri Lankan provinces |
| `/api/locations/districts` | GET | 60/min | `LocationController@districts` | Returns districts for a given `province` |
| `/api/locations/ds-divisions` | GET | 60/min | `LocationController@dsDivisions` | Returns DS divisions for a given `district` |
| `/api/registry/single` | POST | 30/min | `RegistryController@storeSingle` | Validates and stages a single record |
| `/api/registry/upload` | POST | **5/min** | `RegistryController@uploadExcel` | Bulk-stages rows from an uploaded Excel/CSV file |
| `/api/registry/template` | GET | 30/min | `RegistryController@downloadTemplate` | Streams a CSV template for Agent download |
| `/instructions.xlsx` *(static)* | GET | — | — | Static Excel file with location names. Served from `public/instructions.xlsx`. |
| `/api/registry/rejected` | GET | 30/min | `RegistryController@getRejected` | Returns paginated list of current user's rejected records |
| `/api/registry/rejected/{id}/resubmit` | POST | 30/min | `RegistryController@resubmitRejected` | Resubmits a corrected record back to Pending state |
| `/api/users` | GET | — | `UserManagementController@index` | Returns all users as JSON |
| `/api/users` | POST | — | `UserManagementController@store` | Creates a new user with hashed password |
| `/api/users/{id}` | PUT | — | `UserManagementController@update` | Updates a user; re-hashes password only if provided |
| `/api/users/{id}` | DELETE | — | `UserManagementController@destroy` | Deletes a user |

### 6.7 Key Backend Service: Phone Number Normalizer

The `RegistryController::normalizePhoneNumber()` private method is applied to `contact_number` and `whatsapp_number` during every bulk Excel upload. It handles the common "Excel strips leading zeros" problem by detecting and correcting these patterns:

| Raw Input (from Excel) | Normalized Output | Transformation Applied      |
| ---------------------- | ----------------- | --------------------------- |
| `771234567`            | `0771234567`      | Prepended `0` (9-digit)     |
| `+94771234567`         | `0771234567`      | Replaced `+94` with `0`     |
| `94771234567`          | `0771234567`      | Replaced `94` with `0`      |
| `077 123-4567`         | `0771234567`      | Stripped spaces/dashes      |
| `0771234567`           | `0771234567`      | No change (already correct) |

### 6.8 Rejection Dashboard (Data Entry — Rejected Records)

The Rejection Dashboard allows Data Entry Operators to view and correct their rejected staging records from the Maker-Checker workflow. Three endpoints support this feature, all scoped to the currently authenticated user (`uploaded_by = Current::id()`) and limited to `validation_status = 'Rejected'`.

**Listing** (`GET /api/registry/rejected`): Returns a paginated list (15 records per page) of the user's rejected records, ordered by `updated_at DESC` (most recently rejected first).

**Detail** (`GET /api/registry/rejected/{id}`): Returns the full `StagingData` record (including `data_payload`, `rejection_reason`, `submission_type`, `target_record_id`) for a specific rejected record. Used to pre-fill the edit form.

**Resubmit** (`POST /api/registry/rejected/{id}/resubmit`): Accepts corrected field data. The pipeline mirrors `storeSingle`:

1. **Category field stripping:** Cross-category null fields are unset before validation.
2. **Full Category-Aware Validation:** `RegistryValidator::validate($data)` — enforces all field and location rules.
3. **Main Registry Duplicate Check:** `MainRegistry::where('contact_number', ...)`. For `UPDATE` submissions, `target_record_id` is excluded from the check.
4. **Staging Duplicate Check:** `StagingData::where('validation_status', 'Pending')->where('id', '!=', $staging->id)` — ensures no other pending record shares the contact number.
5. **Status Reset:** Updates `data_payload`, sets `validation_status = 'Pending'`, clears `rejection_reason = null`. The original `batch_id` and `submission_type` are preserved.
6. **Response:** Returns `200 OK` with `{ message, staging_id }`.
