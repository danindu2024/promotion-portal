# **Software Requirements Specification (SRS)**

**Project Name:** Data Management & Analytics Portal 

**Version:** 1.0 (Draft)

## **1\. Introduction**

### **1.1 Purpose**

The purpose of this document is to define the requirements for a **Targeted Promotion & Data Management Portal**. This system is designed to cleanse, validate, and analyze demographic data to facilitate customized promotional campaigns. It allows stakeholders to filter and target specific segments, specifically distinguishing between "Self-Employed" individuals and "Trade" organizations.

### **1.2 Scope**

The system is a web-based extension of an existing infrastructure that allows:

- **Data Aggregation:** Importing data via bulk Excel uploads or manual entry.
- **Targeted Analysis:** Filtering records by geography (Province/District/DS Division) or sector to identify audiences for promotions.
- **Quality Control:** A "Maker-Checker" workflow to ensure data used for promotions is accurate and error-free.

## **2\. User Roles & Characteristics**

The system supports two primary user levels:

Role

Access Level

Responsibilities

Data Entry Operator (Agent)

Normal Level

Uploading Excel sheets, entering single forms, correcting rejected data, viewing own entries.

Validator / Admin

High Level

Reviewing pending data, approving/rejecting entries, managing master data, viewing full analytics

### **2.1 User Management Strategy**

- **R-INT-01 Legacy Integration:** The system shall not maintain a separate user registry for authentication. Instead, it must connect to the **Existing Database** to fetch and validate user credentials.
- **R-INT-02 Account Provisioning:** New user accounts are not created within this portal. All users (Agents and Validators) must exist in the source database prior to accessing this system.

**Data Entry Logic** The system shall support two distinct categories of data entry with the following specific requirements:

- **Common Fields (Mandatory for All):** Name, Address, Province, District, DS Division, Contact Number, whatsapp_number, Email
- **Geography Fields (Cascading Dropdowns):** Province → District → DS Division. The user must first select a Province, which populates the District dropdown with only districts belonging to that province. Selecting a District then populates the DS Division dropdown with only divisions within that district. The system enforces hierarchical consistency during validation.
- **Location API Endpoints:**
    - `GET /api/locations/provinces` — Returns all 9 provinces.
    - `GET /api/locations/districts?province={name}` — Returns districts for the selected province.
    - `GET /api/locations/ds-divisions?district={name}` — Returns DS divisions for the selected district.
- **Category A: Self-Employed:**
    - **Mandatory:** Age, Business field, Number of employees
    - **Not Applicable:** Contact Person Name, Number of Members.

- **Category B: Trade:**
    - **Not Applicable:** Age, Business field, Number of employees
    - **Mandatory:** Contact Person Name, Number of Members.

### **2.2 Security Standards**

- **R-SEC-01 Inherited Security:** The application shall utilize the security protocols and access controls defined in the existing database environment.
- **R-SEC-02 Data Transmission:** While relying on the DB for storage security, all web traffic for the portal must be encrypted (HTTPS) to ensure high security during data transfer.
- **R-SEC-03** _**when**_ **these logs are triggered:**

#### **A. Tracking Failed Logins (Security)**

- **Trigger:** User enters incorrect credentials.
- **Log Logic:**
    - event_type: 'AUTH_FAILURE'
    - user_id: NULL
    - description: 'Failed login attempt'
    - metadata: {"attempted_email": "admin@example.com", "error": "Invalid Password"}
    - ip_address: Captured Request IP

#### **B. Tracking Search/Filter Operations (GDPR Compliance)**

- **Trigger:** User clicks "Filter" or searches for a specific name (e.g., checking if a celebrity or neighbor is in the DB).
- **Log Logic:**
    - event_type: 'SEARCH_QUERY'
    - user_id: Current Validator ID
    - description: 'Performed demographic search'
    - metadata: {"filter_district": "Colombo", "search_term": "Ranil", "results_count": 5}
    - _Why?_ If an audit reveals a user looked up specific people without a business reason, this log proves it.

#### **C. Tracking Excel Exports (Data Leakage)**

- **Trigger:** User clicks "Download Excel" or "Export Error Sheet".
- **Log Logic:**
    - event_type: 'DATA_EXPORT'
    - user_id: Current Agent ID
    - description: 'Bulk Exported Registry Data'
    - metadata: {"file_type": "XLSX", "record_count": 1500, "filters_applied": "All Trade Category"}
    - _Why?_ If 1,500 records leak, you check this log to see who downloaded 1,500 records recently.

## **3\. Functional Requirements**

### **3.1 Promotional Targeting & Filtering**

- **R-TGT-01 Dynamic Filtering:** Users must be able to filter the dataset to create "Target Groups" based on:
    - **Category:** Self-Employed vs. Trade.
    - **Location:** Province → District → DS Division (cascading dropdowns — each level filters the next).
    - **Demographics:** Age range, Field of work.

- **R-TGT-02 Result Export:** The system shall allow users to export these filtered lists to Excel for promotional use.

### **3.2 Data Entry — Add New Records**

Excel uploads and single-form entry are used **exclusively for adding NEW records**. They do not handle updates.

#### **3.2.1 Excel Bulk Upload**

- **R-DATA-01 Bulk Upload:** The system accepts Excel files for bulk data entry of new records only.

**Processing Pipeline (executed automatically after upload):**

1. **In-Batch Duplicate Check** (in-memory, 0 DB calls): Scan all rows for duplicate `contact_number` values within the same file. Keep the first occurrence; flag subsequent duplicates.
2. **Database Duplicate Check** (1 DB call): Collect all remaining `contact_number` values and run a single `WHERE IN` query against `main_registry`. Flag any matches as "Already exists in registry."
3. **Field Validation** (in-memory): Validate each remaining row using category-aware rules (see Section 3.2.4). Flag invalid rows with specific error messages.

**Results Summary Screen:**

- Display counts: **Valid Rows (Green)**, **Duplicate Rows (Orange)**, **Error Rows (Red)**.
- **Submit Valid Records** button: Sends all valid rows to `staging_data` with `submission_type = 'NEW'`.
- **Download Error Sheet** button: Downloads an Excel file containing all rejected rows (duplicates + validation errors) with an "Error Message" column explaining each rejection.

**Performance:** The entire pipeline uses only **2 DB calls** regardless of file size (1 for duplicate check, 1 for bulk insert to staging).

#### **3.2.2 Single Form Entry**

- Agent fills out the form and clicks Submit.
- **Duplicate Check:** System queries `main_registry` for the entered `contact_number` (1 DB call).
    - **If exists:** Display an instant error: _"A record with this contact number already exists. Use the Update Data tab to modify existing records."_ The form is not submitted.
    - **If not exists:** Insert to `staging_data` with `submission_type = 'NEW'`. Proceed to Maker-Checker workflow.

#### **3.2.3 Error Resolution**

- **R-DATA-03:** Users can download the Error Sheet, correct rejected rows offline, and re-upload the corrected file.

#### **3.2.4 Category-Aware Validation**

**Geography (Hierarchical) Validation:**

- Province must be one of the 9 valid Sri Lankan provinces.
- District must belong to the selected province.
- DS Division must belong to the selected district.
- The hierarchical mapping is defined in `config/srilanka.php` under the `hierarchy` key.

**Logic If Category = "Self-Employed":**

- **Mandatory Checks:** full_name, field_of_work, employees_count.
- **Null Checks:** Ensure contact_person and members_count are ignored or blank.

**Logic If Category = "Trade":**

- **Mandatory Checks:** full_name, contact_person, members_count.
- **Null Checks:** Ensure field_of_work and employees_count are ignored or blank.

All valid records enter the Staging table with `submission_type = 'NEW'` and proceed to the Maker-Checker workflow (Section 3.4).

### **3.3 Data Entry — Update Existing Records**

Updates are handled through a **dedicated "Update Data" module**, separate from Add. This ensures explicit intent and prevents accidental overwrites.

**Workflow:**

1. **Search:** Agent opens the "Update Data" tab and searches for a record by **Name** and/or **DS Division**.
2. **Select:** The system returns matching records in a table. The Agent identifies the correct record and clicks the **"Edit"** button.
3. **Edit:** A pre-filled form opens with all current field values loaded from `main_registry`. The Agent modifies only the fields that need updating.
4. **Submit:** The Agent submits the modified record. The system stores the updated data in `staging_data` with:
    - `submission_type = 'UPDATE'`
    - `target_record_id = main_registry.id` (set explicitly from the selected record)
    - `data_payload = JSON of the complete modified record`
5. **Validation:** The record enters the Maker-Checker queue (Section 3.4) where the Validator reviews changes in a side-by-side comparison.

**Key Rules:**

- The Agent must submit the **complete record** (all fields), not just changed fields. The form is pre-filled, so unchanged fields retain their current values.
- The `target_record_id` is set by the UI (from the search result), not by matching logic. This eliminates ambiguity.
- Phone number changes are supported: since the Agent searched and selected the record explicitly, updating `contact_number` does not break identity.

### **3.4 The "Maker-Checker" Workflow**

- **R-WORK-01 Pending Queue:** All new entries and updates enter a "Pending" state in `staging_data`.
- **R-WORK-02 Validator Controls:**
    - **For Updates:** Validators see "Old Data" vs. "New Data" side-by-side. Changed fields are highlighted.
    - **For New Entries:** Validators see a single "New Record" card.
    - **Decision:** Validators can "Approve" (commit to DB) or "Reject" (send back to Agent with reason).

- **R-WORK-03 Soft Deletion:** Deleting a record sets an is_deleted flag to TRUE. The data remains in the database for audit purposes but is hidden from the main UI.

### **3.5 Dashboard & Analytics**

- **R-REP-01 Strategic Visuals:**
    - **Sector Distribution:** Pie charts showing the split between trades and self-employed categories.
    - **Regional Heat Maps:** Visualizing the concentration of potential promotion targets across locations.

**3.6 Approval Logic**

The "Approve" button action changes based on the submission_type:

- **If Type = 'NEW':**
    - Perform standard INSERT INTO main_registry.

- **If Type = 'UPDATE':**
    - Perform UPDATE main_registry SET ... WHERE id = target_record_id.
    - _Audit Note:_ The system must archive the _previous_ values of the record into audit_logs before overwriting, ensuring you can undo the change if needed.
