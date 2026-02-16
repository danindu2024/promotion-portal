**Software Requirements Specification (SRS)**
=============================================

**Project Name:** Data Management & Analytics Portal 

**Version:** 1.0 (Draft)

**1\. Introduction**
--------------------

### **1.1 Purpose**

The purpose of this document is to define the requirements for a **Targeted Promotion & Data Management Portal**. This system is designed to cleanse, validate, and analyze demographic data to facilitate customized promotional campaigns. It allows stakeholders to filter and target specific segments, specifically distinguishing between "Self-Employed" individuals and "Trade" organizations.

### **1.2 Scope**

The system is a web-based extension of an existing infrastructure that allows:

*   **Data Aggregation:** Importing data via bulk Excel uploads or manual entry.
    
*   **Targeted Analysis:** Filtering records by geography (District/DS Division/GN Division), age, or sector to identify audiences for promotions.
    
*   **Quality Control:** A "Maker-Checker" workflow to ensure data used for promotions is accurate and error-free.
    

**2\. User Roles & Characteristics**
------------------------------------

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

*   **R-INT-01 Legacy Integration:** The system shall not maintain a separate user registry for authentication. Instead, it must connect to the **Existing Database** to fetch and validate user credentials.
    
*   **R-INT-02 Account Provisioning:** New user accounts are not created within this portal. All users (Agents and Validators) must exist in the source database prior to accessing this system.
    

**Data Entry Logic** The system shall support two distinct categories of data entry with the following specific requirements:

*   **Common Fields (Mandatory for All):** Name, Address, District, GN Division, Contact Number, whatsapp\_number, Email
    
*   **Category A: Self-Employed:**
    
    *   **Mandatory:** Age, Business field, Number of employees
        
    *   **Not Applicable:** Contact Person Name, Number of Members.
        
*   **Category B: Trade:**
    
    *   **Not Applicable:** Age, Business field, Number of employees
        
    *   **Mandatory:** Contact Person Name, Number of Members.
        

### **2.2 Security Standards**

*   **R-SEC-01 Inherited Security:** The application shall utilize the security protocols and access controls defined in the existing database environment.
    
*   **R-SEC-02 Data Transmission:** While relying on the DB for storage security, all web traffic for the portal must be encrypted (HTTPS) to ensure high security during data transfer.
    
*   **R-SEC-03** _**when**_ **these logs are triggered:**
    

#### **A. Tracking Failed Logins (Security)**

*   **Trigger:** User enters incorrect credentials.
    
*   **Log Logic:**
    
    *   event\_type: 'AUTH\_FAILURE'
        
    *   user\_id: NULL
        
    *   description: 'Failed login attempt'
        
    *   metadata: {"attempted\_email": "admin@example.com", "error": "Invalid Password"}
        
    *   ip\_address: Captured Request IP
        

#### **B. Tracking Search/Filter Operations (GDPR Compliance)**

*   **Trigger:** User clicks "Filter" or searches for a specific name (e.g., checking if a celebrity or neighbor is in the DB).
    
*   **Log Logic:**
    
    *   event\_type: 'SEARCH\_QUERY'
        
    *   user\_id: Current Validator ID
        
    *   description: 'Performed demographic search'
        
    *   metadata: {"filter\_district": "Colombo", "search\_term": "Ranil", "results\_count": 5}
        
    *   _Why?_ If an audit reveals a user looked up specific people without a business reason, this log proves it.
        

#### **C. Tracking Excel Exports (Data Leakage)**

*   **Trigger:** User clicks "Download Excel" or "Export Error Sheet".
    
*   **Log Logic:**
    
    *   event\_type: 'DATA\_EXPORT'
        
    *   user\_id: Current Agent ID
        
    *   description: 'Bulk Exported Registry Data'
        
    *   metadata: {"file\_type": "XLSX", "record\_count": 1500, "filters\_applied": "All Trade Category"}
        
    *   _Why?_ If 1,500 records leak, you check this log to see who downloaded 1,500 records recently.
        

**3\. Functional Requirements**
-------------------------------

### **3.1 Promotional Targeting & Filtering**

*   **R-TGT-01 Dynamic Filtering:** Users must be able to filter the dataset to create "Target Groups" based on:
    
    *   **Category:** Self-Employed vs. Trade.
        
    *   **Location:** District → DS Division → GN Division.
        
    *   **Demographics:** Age range, Field of work.
        
*   **R-TGT-02 Result Export:** The system shall allow users to export these filtered lists to Excel for promotional use.
    

### **3.2 Data Ingestion (Staging Workflow)**

*   **R-DATA-01 Bulk Upload:** The system accepts Excel files for bulk data entry.
    
*   **R-DATA-02 Validation Logic:**
    
    *   **Structure Check:** Verifies required columns (Name, Age, Address, Contact Info).
        
    *   **Data Check:** Flags incorrect formats (e.g., invalid phone numbers) and identifies redundant data.
        
*   **R-DATA-03 Error Resolution:** Users can download a specific "Error Sheet" containing only rejected rows, correct them, and re-upload.
    
*   **R-DATA-04 Duplicate Logic**
    

**Matching Criteria (The "Composite Key"):** Since there is no National ID (NIC) field listed in your mandatory fields, we must use a **Strict Composite Match**:

*   **Match Rule:** A record is considered an "UPDATE" if **BOTH** contact\_number AND full\_name match an existing record in main\_registry.
    

**Workflow Logic:**

1.  **System Action:** When a row is uploaded, query main\_registry for this specific Name + Phone combination.
    
2.  **If Match Found:**
    
    *   Set staging\_data.submission\_type = 'UPDATE'.
        
    *   Set staging\_data.target\_record\_id = main\_registry.id (of the found record).
        
    *   _UI Effect:_ This triggers the "Side-by-Side Comparison" view.
        
3.  **If No Match:**
    
    *   Set staging\_data.submission\_type = 'NEW'.
        
    *   Set staging\_data.target\_record\_id = NULL.
        
    *   _UI Effect:_ This triggers the "New Record Card" view.
        

The validation logic must now be context-aware based on the Category column in the Excel file

**Logic If Category = "Self-Employed":**

*   **Mandatory Checks:** full\_name, age, field\_of\_work, employees\_count.
    
*   **Null Checks:** Ensure contact\_person and members\_count are ignored or blank.
    

**Logic If Category = "Trade":**

*   **Mandatory Checks:** full\_name, contact\_person, members\_count.
    
*   **Null Checks:** Ensure age and field\_of\_work are ignored or blank.
    

### **3.3 The "Maker-Checker" Workflow**

*   **R-WORK-01 Pending Queue:** All uploads and edits enter a "Pending" state.
    
*   **R-WORK-02 Validator Controls:**
    
    *   **Comparison View:** Validators see "Old Data" vs. "New Data" side-by-side for edits.
        
    *   **Decision:** Validators can "Approve" (commit to DB) or "Reject" (send back to Agent).
        
*   **R-WORK-03 Soft Deletion:** Deleting a record sets an is\_deleted flag to TRUE. The data remains in the database for audit purposes but is hidden from the main UI.
    

### **3.4 Dashboard & Analytics**

*   **R-REP-01 Strategic Visuals:**
    
    *   **Sector Distribution:** Pie charts showing the split between trades and self-employed categories.
        
    *   **Regional Heat Maps:** Visualizing the concentration of potential promotion targets across locations.
        
    *   **Demographic Spread:** Age pyramids to understand the audience maturity.
        

**3.5 Approval Logic**

The "Approve" button action now changes based on the submission\_type:

*   **If Type = 'NEW':**
    
    *   Perform standard INSERT INTO main\_registry.
        
*   **If Type = 'UPDATE':**
    
    *   Perform UPDATE main\_registry SET ... WHERE id = target\_record\_id.
        
    *   _Audit Note:_ The system must archive the _previous_ values of the record into audit\_logs before overwriting, ensuring you can undo the change if needed.