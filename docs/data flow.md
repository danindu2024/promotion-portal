**Data Flow Diagrams**

**Project:** Targeted Promotion & Data Management Portal

**Version:** 1.0

**Reporting Flow Strategy**

**1\. Data Access Approach: Direct vs. Aggregated**

*   **Live Listings (Search Results):** The system shall query the main\_registry table directly using **Pagination** (fetching 50 records at a time) to prevent memory overflows when viewing lists.
    
*   **Analytical Widgets (Charts/Heatmaps):** Instead of loading raw rows, the backend will return **Aggregated JSON** (e.g., GROUP BY district COUNT(\*)). This ensures the dashboard loads in milliseconds even with millions of records.
    

**2\. Query Optimization (The "Single Table" Advantage)**

*   Since all data lives in main\_registry with a category discriminator, reports do **not** require complex JOIN or UNION operations.
    
*   **Indexing Strategy:** We will apply composite indexes on frequently filtered columns:
    
    *   INDEX(category, district, field\_of\_work) – To speed up the primary filters.
        
    *   INDEX(age) – For the demographic pyramid.
        

**3\. The Reporting Execution Flow**

1.  **User Action:** Promotion manager selects "Gampaha District" + "Construction Services" filters on the Dashboard.
    
2.  **Request:** Frontend sends a GET request: /api/analytics?district=Gampaha§or=Construction.
    
3.  **Backend Processing:**
    
    *   The **Controller** captures parameters.
        
    *   The **Query Builder** dynamically appends WHERE clauses (ignoring empty filters).
        
    *   **Security Check:** Ensures is\_deleted = 0 and validation\_status = Approved.
        
4.  **Database Response:** MySQL returns only the mathematical counts (e.g., { "Gampaha": 450, "Colombo": 200 }) rather than the full user details.
    
5.  **Visualization:** Chart.js (frontend) renders the graphs based on this lightweight JSON.
    

**4\. Export Strategy (Asynchronous Processing)**

*   For **Excel Exports**, downloading 100,000 rows can time out a browser.
    
*   **Flow:**
    
    1.  User clicks "Export".
        
    2.  Server generates the Excel file in a **Streamed Response** (writing row-by-row to the output) rather than building the whole file in RAM.
        
    3.  This prevents server crashes during large data dumps.
        

### **Data Flow Diagrams (DFD)**

A DFD maps how information travels. We will create two levels:

*   **Level 0 (Context Diagram):** The high-level "Bird's Eye View".
    
*   **Level 1 (Process Diagram):** The detailed view showing the "Error Sheet" loop and "Maker-Checker" logic.
    

#### **DFD Level 0: Context Diagram**

This diagram shows the system as a single "Black Box" and who interacts with it.

*   **External Entities:**
    
    *   **Agent:** Provides raw Excel files.
        
    *   **Validator:** Provides decisions (Approve/Reject).
        
    *   **Existing DB:** Provides User Credentials (Login).
        
    *   **Management:** Consumes Analytics
        

#### **DFD Level 1: Detailed Process Flow**

This breaks the "Black Box" into the actual processes. It explicitly visualizes the **"Split Logic"** (Valid rows go to DB, Invalid rows go back to User).

**Key Processes:**

1.  **Authenticate:** Checks users against the Legacy DB.
    
2.  **Validate & Split:** The critical logic that separates good data from bad.
    
3.  **Manage Review:** The "Maker-Checker" status updates.
    
4.  **Generate Analytics:** Live querying for the dashboard.