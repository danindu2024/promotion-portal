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

## 5. Database Schema Definition

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
| `field_of_work`   | Enum      | No       | Business sector — 10 categories (Self-Employed only)        |
| `employees_count` | Integer   | Yes      | Number of employees (Self-Employed only)                    |
| `contact_person`  | String    | Yes      | Contact person name (Trade only)                            |
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
│       └── Registry/
│           └── DataEntry.vue       # Single Form Entry + Bulk Upload tabs
└── views/
    └── app.blade.php               # Root Blade template
```

### 6.4 Fulfilling Core Functionalities

- **Government Aesthetics:** **Tailwind CSS v4** with custom `@theme` tokens strictly enforces the Royal Blue (`#0056b3`) and Off-White (`#f8f9fa`) palette defined in the UI/UX specifications.
- **Data Visualization & Analytics:** Vue's reactive ecosystem allows seamless integration with reporting libraries (e.g., Chart.js, ApexCharts, Leaflet) to render the required demographic pyramids, sector pie charts, and regional heat maps.
- **Complex UI Interactions:** The cascading Location dropdowns (Province -> District -> DS Division), dynamic category-aware form fields, and client-side validation with inline error feedback are all handled through Vue's reactive state management.
- **Single Form Entry UX:** Client-side validation runs before API calls. Loading spinners appear during dropdown fetches. Double-click submission is prevented. The page auto-scrolls to success/error alerts.

### 6.5 Web Routes (Inertia Pages)

| Route             | Vue Component                  | Description                          |
| ----------------- | ------------------------------ | ------------------------------------ |
| `GET /`           | —                              | Redirects to `/data-entry`           |
| `GET /data-entry` | `Pages/Registry/DataEntry.vue` | Single Form Entry + Bulk Upload tabs |
