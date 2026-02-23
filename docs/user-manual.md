# Data Entry: Stakeholder User Manual

**Version:** 1.0  
**Module:** Agent Dashboard > Data Entry > Single Entry  
**Purpose:** This manual outlines the complete user experience for agents manually entering new beneficiary records (Self-Employed or Trade) into the Promotion Portal. It covers the successful entry process ("Happy Path") and all error handling scenarios.

---

## 1. Overview of the Interface

The Single Form Entry interface is designed around a clean, two-column grid (on desktop) to maximize data entry speed while minimizing cognitive load.

**Key Interface Features:**

- **Dynamic Sections:** The form reacts to the selected "Category" (Self-Employed vs. Trade), showing only relevant fields and hiding prohibited ones.
- **Cascading Geography:** Province, District, and DS Division are linked. Selecting a higher level automatically populates the options for the next level, ensuring geographic accuracy.
- **Real-time Feedback:** Invalid inputs are caught immediately before the server is contacted, saving time and bandwidth.

---

## 2. The "Happy Path": Successful Data Entry

This flow describes a flawless data entry process resulting in a new record being queued for Validator review.

**Step-by-Step Experience:**

1.  **Open Form:** The Agent clicks the "Single Entry" tab. The form loads instantly. A brief loading spinner appears in the "Province" dropdown as the system fetches the 9 provinces.
2.  **Select Category:** The Agent selects a Category radio button:
    - **Self-Employed:** The system reveals "Age", "Field of Work" (dropdown), and "Number of Employees".
    - **Trade:** The system reveals "Contact Person Name" and "Number of Members".
3.  **Enter Identity Info:** The Agent types the `Full Name` and `Address`.
4.  **Select Location (Cascading):**
    - Agent selects **Province** (e.g., "Western").
    - The **District** dropdown displays a loading spinner briefly, then populates with only Western Province districts. Agent selects "Gampaha".
    - The **DS Division** dropdown displays a spinner, then populates with only Gampaha DS divisions. Agent selects "Attanagalla".
5.  **Enter Contact Details:**
    - Agent enters a 10-digit number starting with 0 into the `Contact Number` field (e.g., `0771234567`).
    - Optional fields (`Email`, `WhatsApp`) are filled if available.
6.  **Submit:** The Agent clicks the blue **"Save Registry"** button at the bottom.
    - _Visual Feedback:_ The button text changes to "Saving...", a spinner appears inside the button, and the button becomes disabled to prevent accidental double-clicking.
7.  **Success Result:**
    - The backend validates the data and confirms the phone number is unique.
    - The page automatically smooth-scrolls to the top.
    - A **Green Success Banner** appears: _"Record queued for review! Staging ID: 1042"_.
    - The entire form (including all text inputs and dependent dropdown lists) is immediately cleared, ready for the next entry.

---

## 3. Error Flow: Client-Side Validation (Pre-Submit)

The system is designed to catch simple formatting mistakes and missing data _before_ making the user wait for a server response.

**Scenario:** The Agent attempts to submit an incomplete or incorrectly formatted form.

1.  **Action:** The Agent leaves `Full Name` blank and enters `771234567` (only 9 digits, missing the leading zero) in the `Contact Number` field. They click **"Save Registry"**.
2.  **Immediate Feedback:** The form does _not_ contact the server (the button does not say "Saving...").
3.  **Visual Indicators:**
    - The page automatically smooth-scrolls to the top.
    - A **Red Error Banner** appears at the top: _"Please correct the highlighted errors before saving."_
    - The `Full Name` field border turns red. A red message appears below it: _"Full name is required."_
    - The `Contact Number` field border turns red. A red message appears below it: _"Must be exactly 10 digits starting with 0."_
4.  **Resolution:** The Agent corrects the highlighted fields. The red borders and messages disappear upon the next submission attempt.

---

## 4. Error Flow: Server-Side Conflicts (Duplicate Detection)

Even if a form is filled out perfectly, the system must enforce business rules—most importantly, the "One person = One phone number" rule. The system checks two separate locations to guarantee data integrity.

### Scenario A: Duplicate in Master Database

**Context:** The Agent is trying to enter a person who was already approved months ago.

1.  **Action:** The Agent fills out a perfect form, but uses a `Contact Number` (`0779998888`) that already belongs to an approved beneficiary in the `main_registry`.
2.  **Submit:** The Agent clicks **"Save Registry"**. The button shows "Saving...".
3.  **Conflict Result:**
    - The server detects the duplicate in the Master DB and blocks the entry.
    - The page smooth-scrolls to the top.
    - A **Red Error Banner** appears: _"A record with this contact number already exists. Use the Update Data tab to modify existing records."_
    - The form _is not cleared_. The data remains intact so the Agent doesn't lose their typing effort, allowing them to copy the number and navigate to the "Update Data" tab.

### Scenario B: Duplicate in Pending Queue (Staging)

**Context:** Agent A entered a person 10 minutes ago. The Validator hasn't approved it yet. Agent B unknowingly tries to enter the same person right now.

1.  **Action:** Agent B fills out the form using the same `Contact Number` that is currently pending.
2.  **Submit:** Agent B clicks **"Save Registry"**. The button shows "Saving...".
3.  **Conflict Result:**
    - The server successfully checks the Master DB (it's not there yet).
    - The server checks the `staging_data` (Pending queue) and finds the duplicate.
    - The page smooth-scrolls to the top.
    - A **Red Error Banner** appears: _"A record with this contact number is already pending review."_
    - The form data remains intact. Agent B knows to discard this entry as it is already being processed.

---

## 5. Agent Utilities

- **Form Reset:** At any time during data entry, the Agent can click the grey **"Reset"** button next to "Save Registry". This instantly clears all typed data, resets category selections, un-selects dropdowns, and clears any red validation errors on the screen, providing a clean slate.
- **Loading Indicators:** If internet connectivity is slow during the cascading dropdown process (e.g., selecting a Province), the subsequent dropdown will physically display "Loading..." to assure the Agent that the system is processing the request. This prevents frustration and premature clicking.

---

## 6. Excel Bulk Upload

The Excel Bulk Upload feature significantly speeds up data entry by allowing Agents to upload hundreds of records simultaneously via a `.csv`, `.xls`, or `.xlsx` file.

### 6.1 Preparing the Data

1.  **Download Template:** Navigate to the "Bulk Upload" tab and click the **"Download Template"** button in the top right.
2.  **Fill Data:** Open the downloaded `registry_upload_template.csv`. Do **NOT** change the header column names.
3.  **Smart Phone Number Handling:**
    - Excel aggressively strips leading zeros (e.g., an agent types `0771234567` but Excel saves it as `771234567`).
    - **You do not need to fix this.** The portal features a Smart Normalizer that automatically detects 9-digit numbers, `+94` prefixes, or `94` prefixes and silently converts them back to the pristine 10-digit Sri Lankan format (`07...`) during upload!

### 6.2 The Upload Process

1.  **Select File:** Drag and drop your file into the dashed drop zone, or click to browse your computer. The system supports files up to 10MB.
2.  **Verify File:** The UI will display the selected file's name and size in KB/MB.
3.  **Process:** Click the blue **"Upload & Process"** button. A spinner indicates the system is working.

### 6.3 Understanding the Results

Once processed, the system provides a three-card summary:

- **Total Processed:** The absolute number of non-empty rows found in the Excel sheet.
- **Valid Rows (Green):** How many rows passed all validations and duplicate checks. These are immediately sent to the `staging_data` queue with a unique `BATCH-` ID for the Validator to review.
- **Invalid Rows (Orange):** How many rows contained errors (e.g., missing required fields, invalid text formats, or duplicate phone numbers). **These rows are safely rejected and do not enter the system.**

### 6.4 The Error Sheet Workflow

If there are any "Invalid Rows" (even just one), you must correct them. The system makes this effortless:

1.  **Download the Error Sheet:** Click the orange **"Download Error Sheet"** button below the results.
2.  **Locate the Errors:** Open the downloaded file. It looks exactly like your original upload, but includes only the rejected rows. Crucially, a new column called **"Error Message"** is appended to the far right.
3.  **Fix:** Read the exact reason the row failed (e.g., _"Contact number already exists in the system"_ or _"Field 'Age' must be a number"_). Correct the data directly in this sheet.
4.  **Re-upload:** Save the Error Sheet and upload it back into the portal. The successfully fixed rows will now go into the Staging queue!
