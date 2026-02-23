**UI/UX Design Specifications**

**Project:** Targeted Promotion & Data Management Portal

**Version:** 1.0

## **1\. Global Design System**

Before building individual screens, we define the common elements to ensure consistency.

### **1.1 Frontend Tech Stack**

- **Framework:** Vue.js 3 + Inertia.js
    - _Why:_ Provides highly reactive, SPA-like experiences (e.g., instant cascading dropdowns, dynamic form fields) while remaining a single deployment monolith.
- **Styling:** Tailwind CSS v4
    - _Why:_ Allows rapid, strict enforcement of the government-style design system using Tailwind v4's CSS-native `@theme` blocks. Custom design tokens (colors, fonts) are defined directly in `resources/css/app.css` — no external config file needed.
- **Data Visualization:** Chart.js / ApexCharts (via Vue wrappers) for analytics dashboards.

### **1.2 Core Theme**

- **Color Palette:**
    - **Primary:** Royal Blue (`#0056b3` / Tailwind `blue-700`) - Trust and Authority (Buttons, Headers).
    - **Secondary:** Slate Grey (`#6c757d` / Tailwind `slate-500`) - Secondary actions (Cancel, Back).
    - **Success:** Emerald Green (`#28a745` / Tailwind `green-600`) - Approved, Valid, Clean Data.
    - **Danger:** Crimson Red (`#dc3545` / Tailwind `red-600`) - Rejected, Errors, Delete.
    - **Background:** Off-White (`#f8f9fa` / Tailwind `gray-50`) - Reduces eye strain for data entry operators.

- **Typography:**
    - **Font Family:** Inter or Roboto (Clean, legible sans-serif - Tailwind `font-sans`).
    - **Headings:** Bold, Dark Grey (Tailwind `font-bold text-gray-800`).
    - **Data Tables:** Monospaced numbers (e.g., Roboto Mono - Tailwind `font-mono`) for easy reading of figures.

## **2\. Screen Specifications (Wireframes)**

### **Screen 1: The Login Portal**

- **Layout:** Center-aligned card on a blurred background image (e.g., Sri Lankan map or office setting).
- **Elements:**
    1.  **Logo:** Ministry/Organization logo at the top.
    2.  **Inputs:** Username & Password (with "Show/Hide" eye icon).
    3.  **Action:** "Login" Button (Full width).
    4.  **Utilities:** "Remember Me" checkbox.
    5.  **Support:** Text link: _"Forgot Password? Call IT Support at 011-XXXXXXX"_ (As per requirement, no self-service reset).

### **Screen 2: The Analytics Dashboard (Promotional Manager's View)**

- **Top Bar:** User Profile ("Welcome,"), Notification Bell, Logout.
- **Sidebar Navigation:** Dashboard, Data Entry, Review Queue, Reports.
- **Main Content Area:**
    1.  **KPI Cards (Top Row):** 4 Rectangular cards.
        - Total Registered (Number)
        - Targets vs Achieved (Percentage/Progress Bar)
        - Pending Validations (Number)

    2.  **Visualizations (Middle Row):**
        - **Left:** Pie Chart - "Sector Distribution" (Trade vs. Self-Employed).
        - **Right:** Regional Heat Map - Sri Lanka map highlighting districts with high registration density.

    3.  **Demographics (Bottom Row):**
        - Age Pyramid Bar Chart.

### **Screen 3: Data Entry & Upload Wizard (The "Agent" View)**

- **Step 1: Category Selection:** Large Radio Buttons for "Self-Employed" vs. "Trade".
- **Step 2: Download Template:** Button to "Download Excel Template" (ensures they use the right format).
- **Step 3: Upload Area:** A large dashed box saying _"Drag & Drop Excel file here or Click to Browse"_.
- **Step 4: Validation & Results (Dynamic UI):**
- **Summary Stats:** Displays counts for **Total Rows**, **Valid Rows (Green)**, **Duplicate Rows (Orange)**, and **Error Rows (Red)**.
- **Action Buttons:**
- **Submit Valid Records (Green Button):** Sends only the clean, valid rows to the Staging table.
- **Download Error Sheet (Red Button):** Downloads a specific Excel file containing rejected rows (duplicates + validation errors) with an added "Error Message" column for offline correction.
- _Optional:_ A small "View Error Sample" link to expand a preview of the first 5 errors without downloading
- **Tab B: Form Grid System:**
    - The form utilizes a **Responsive 2-Column Grid** on desktop (merging to 1-column on mobile).
    - **Section 1: Classification (Full Width)**
    - **Category:** Dropdown (Self-Employed | Trade). _Note: This selection acts as the primary filter._
    - **(Data Entry):** The "Single Entry" form creates a dynamic requirement

**Interaction:** When the user selects the "Category" dropdown:

- If **Self-Employed** is selected: Show Age, Field of Work, No. of Employees. Hide Contact Person, No. of Members.
- If **Trade** is selected: Hide Age, Field of Work, No. of Employees. Show Contact Person, No. of Members.
- **Field of Work:** Large Dropdown containing the 10 specific sectors (e.g., "Agriculture...", "IT & Modern Services").
- **Section 2: Identity & Location (Split Columns)**
    - **Left Column (Identity):**
        - Full Name: Text Input.
        - Age: Number Input (Min: 16, Max: 110).

    - **Right Column (Geography):**
        - Address: Text Area (2 rows height).
        - Province: Dropdown (9 provinces). Selecting a province triggers a fetch to `/api/locations/districts?province={name}` and populates the District dropdown.
        - District: **Dependent Dropdown** (disabled until Province is selected). Populated dynamically with districts belonging to the selected province. Selecting a district triggers a fetch to `/api/locations/ds-divisions?district={name}`.
        - DS Division: **Dependent Dropdown** (disabled until District is selected). Populated dynamically with DS divisions belonging to the selected district.

- **Section 3: Contact Details (Split Columns)**
    - Contact Number: Tel Input (Validation: exactly 10 digits starting with `0`, regex `^0\d{9}$`).
    - Whatsapp Number: Tel Input (Optional, same format validation as Contact Number).
    - Email: Email Input (Standard regex validation).

**Action Area (Footer)**

- **Save Registry Button:**
    - **Style:** Primary Button (#0056b3), Large.
    - **Client-Side Validation (Pre-Submit):** Before contacting the server, the frontend validates all required fields. Invalid fields are highlighted with a red border and an inline error message appears below each invalid field. The page auto-scrolls to a top-level error banner.
    - **Server Interaction:** On click (if client validation passes), sends a POST to the backend API. A spinner and "Saving..." text replace the button label. The button is disabled to prevent duplicate submissions.
    - **On Success:** Displays a green success banner at the top showing the Staging ID. The entire form is reset to its initial state (including clearing dependent dropdown options). The page auto-scrolls to the success banner.
    - **On Duplicate (409):** Displays a red error banner with the server's message (e.g., _"A record with this contact number already exists."_ or _"Already pending review."_).
    - **On Validation Error (422):** Displays a red error banner listing specific field errors returned by the server.

- **Reset Button:** Ghost/Text button. Clears all form fields to their initial state, clears dependent dropdown option lists (District, DS Division), and removes all inline error messages.

### **Screen 4: The "Maker-Checker" Review (The "Validator" View)**

**4.1 Pending Queue (List View)**

- A tabular list of all submissions waiting for approval.
- **Status Indicators:** Each row is clearly tagged as either \[NEW ENTRY\] or \[UPDATE\].

**4.2 Review Interface (Adaptive Detail View)** Upon selecting an item, the interface adapts its layout based on the submission type:

- **Header:** Displays metadata (Agent Name, Timestamp, Batch ID).
- **Dynamic Content Area:**
    - **Case A (Updates):** Displays a **Side-by-Side Comparison**. The "Old Data" is on the left (Grey), and "New Data" is on the right. Changed fields are highlighted in yellow for quick scanning.

- **Case B (New Entries):** Displays a **Single "New Record" Card**. Since no prior data exists, the full record is shown in a clean, read-only form format.
- **Action Footer:** Fixed at the bottom for both views.
    - **Approve:** Commits the data to the Master Database.
    - **Reject:** Opens a mandatory "Rejection Reason" text box. The item is returned to the Agent's dashboard for correction.

### **Screen 6: Advanced Search & Filtering**

**Purpose:** A read-only interface designed for identifying target audiences for promotional campaigns.

**Layout:**

- _Geography:_ Province > District > DS Division (cascading dropdowns — each selection filters the next level).
- _Demographics:_ Gender.
- _Sector:_ Business Sector (e.g., "Agriculture", "Textile").
- _Admin Option:_ A checkbox \[ \] Show Archived/Deleted Records (Visible to Admins only).
- **Main Area (Results Table):** A clean data table displaying **only Active & Approved** records by default.
    - _Columns:_ Name, Business Category, Location, Contact Number, Last Updated.
    - _Hidden Columns:_ "Status" (Active is implied) and "Validation State" are hidden to reduce noise.

- **Action Column:** Contains a single **"View Details" (Eye Icon)** button for deep-diving into a specific profile. The "Delete" button is removed from this view to prevent accidental data loss.
- **Top Right Controls:**
    - **"Export to Excel":** Downloads the currently filtered list for campaign use.
    - **"Print Summary":** Generates a PDF snapshot of the current search results.

### **Document Structure (Text Version)**

**1.0 Public Access**

- 1.1 Login Screen
    - 1.1.1 Username/Password Form
    - 1.1.2 "Forgot Password" Modal (Static IT Support Info)

**2.0 Main Dashboard (Role-Dependent)**

- 2.1 **Overview:** KPI Cards, Quick Stats .
- 2.2 **Visuals:** Sector Pie Chart, Maps .

**3.0 Data Entry Module — Add New Records (Agent Role)**

- 3.1 **Single Entry:** Manual Form for Individual Records (NEW only).
- 3.2 **Bulk Upload:**
    - 3.2.1 File Select & Drag-and-Drop.
    - 3.2.2 **Staging Preview:** Valid vs. Invalid summary.
    - 3.2.3 **Error Handling:** Download "Error Sheet" functionality (includes duplicates).

**3.5 Update Data Module (Agent Role)**

- 3.5.1 **Search Bar:** Search by Name and/or DS Division.
- 3.5.2 **Results Table:** Matching records displayed with columns: Name, Contact Number, District, DS Division, Category.
- 3.5.3 **Edit Button:** Each row has an "Edit" button. Clicking opens a pre-filled form with all current field values.
- 3.5.4 **Edit Form:** Same layout as the Single Entry form (Section 3.1), but all fields are pre-populated from `main_registry`. Agent modifies only what needs changing.
- 3.5.5 **Submit:** Sends the complete modified record to `staging_data` for validation.

**4.0 Validation Module (Validator Role)**

- 4.1 **Pending Queue:** List of batches waiting for review.
- 4.2 **Review Interface:**
    - 4.2.1 Split View (Old vs. New Data).
    - 4.2.2 Approve Action (Commit to DB).
    - 4.2.3 Reject Action (Return to Agent).

**Trigger:** Validator clicks "Reject" on a specific record.

**System Action:**

1.  Prompt Validator for a text comment (mandatory).
2.  Update staging_data.validation_status to 'Rejected'.
3.  Save the comment into staging_data.rejection_reason.

**Agent View:** The record reappears in the Agent's "Correction Queue," displaying the rejection_reason so they know what to fix.

**5.0 Analytics & Reporting (Management Role)**

- 5.1 **Advanced Search:** Filter by Province → District → DS Division (cascading), Trade/Self-Employed.
- 5.2 **Export:** Generate Excel for specific filtered lists.
- 5.3 **Soft Delete Management:** View/Restore deleted records (Admin only).
