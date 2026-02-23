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
3. **Pre-Submit Client-Side Validation:** On "Save Registry" click, the `validateForm()` function checks:
    - All required fields are filled (full_name, province, district, ds_division, contact_number)
    - Contact number matches regex `^0\d{9}$`
    - WhatsApp number matches format if provided
    - Email format is valid if provided
    - Category-specific required fields (e.g., `field_of_work` for Self-Employed)
    - If validation fails: red borders + inline error messages appear. Page auto-scrolls to top error banner. No API call is made.
4. **Payload Construction:** Irrelevant category fields are excluded entirely from the payload (not sent as empty strings). Only filled optional fields are included.

**Backend (Laravel — `RegistryController@storeSingle`):**

5. **Initial Format Check:** Validates `category` (in: Self-Employed, Trade) and `contact_number` (regex: `^0\d{9}$`).
6. **Main Registry Duplicate Check (1 DB call):** `MainRegistry::where('contact_number', $data['contact_number'])->exists()`.
7. **Staging Duplicate Check (1 DB call):** `StagingData::where('validation_status', 'Pending')->whereJsonContains('data_payload->contact_number', ...)->exists()`.
8. **Full Category-Aware Validation:** `RegistryValidator::validate($data)` — includes hierarchical location validation.
9. **Insert to Staging (1 DB call):** `StagingData::create(...)` with `submission_type = 'NEW'` and `batch_id = 'SINGLE-{timestamp}'`.
10. **Response:** Returns `201 Created` with `staging_id`. Frontend shows green success banner, resets form and dropdown option lists, auto-scrolls to banner.

**Total DB Calls Per Submission:** 3 (main_registry check + staging check + insert).
