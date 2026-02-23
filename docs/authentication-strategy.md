# Authentication & Integration Strategy

**Project:** Targeted Promotion & Data Management Portal  
**Prepared by:** Development Team  
**Date:** 2026-02-16  
**Status:** Draft — Pending SSE Review

---

## 1. Context & Background

The Promotion Portal is a **new Laravel-based system** that must integrate with an **existing PHP + MySQL application** for user authentication. The portal does not maintain its own user registry. All user accounts are managed in the legacy system's database.

### What We Know

| Aspect            | Detail                                      |
| ----------------- | ------------------------------------------- |
| Legacy Stack      | PHP + MySQL                                 |
| Auth Mechanism    | Token-based (type TBD)                      |
| Portal Framework  | Laravel 12 (PHP)                            |
| Portal Database   | MySQL (separate from legacy)                |
| User Provisioning | Users are created in the legacy system only |

### Portal Role Requirements

| Role          | Access Level | Responsibilities                                              |
| ------------- | ------------ | ------------------------------------------------------------- |
| **Agent**     | Normal       | Upload Excel sheets, single-form entry, correct rejected data |
| **Validator** | High         | Review pending data, approve/reject entries                   |
| **Admin**     | Full         | Full analytics, manage master data, soft-delete management    |

---

## 2. Proposed Architecture

### 2.1 Database Connection Strategy

Since both systems use MySQL, Laravel can connect to the legacy database **directly** as a secondary read-only connection.

```
┌─────────────────────┐       ┌──────────────────────┐
│   Promotion Portal  │       │   Legacy System DB   │
│   (Laravel + MySQL) │       │      (MySQL)         │
│                     │       │                      │
│  promotion-portal   │  ───► │  legacy_db           │
│  - main_registry    │ READ  │  - users (?)         │
│  - staging_data     │ ONLY  │  - roles (?)         │
│  - audit_logs       │       │                      │
│  - sessions         │       │                      │
└─────────────────────┘       └──────────────────────┘
```

**Implementation:**

- Define a `legacy` connection in Laravel's `config/database.php`.
- The `User` model points to the legacy database: `protected $connection = 'legacy';`
- Portal uses **read-only access** — no writes to the legacy database.

### 2.2 Authentication Flow (Proposed)

```
┌──────────┐     ┌──────────────────┐     ┌───────────────┐     ┌──────────────┐
│  Browser │────►│  Portal Backend  │────►│  Legacy MySQL │     │  Portal DB   │
│  (Login) │     │  (Laravel)       │     │  (Read User)  │     │  (Sessions)  │
└──────────┘     └──────────────────┘     └───────────────┘     └──────────────┘
     │                   │                        │                     │
     │  POST /login      │                        │                     │
     │  {user, pass}     │                        │                     │
     │──────────────────►│                        │                     │
     │                   │  SELECT * FROM users   │                     │
     │                   │  WHERE username = ?     │                     │
     │                   │───────────────────────►│                     │
     │                   │  ◄── User Record ──────│                     │
     │                   │                        │                     │
     │                   │  Verify password_hash  │                     │
     │                   │  Map role to portal    │                     │
     │                   │                        │                     │
     │                   │  Store session ────────────────────────────►│
     │                   │  Issue token/cookie    │                     │
     │  ◄── 200 OK ──────│                        │                     │
     │  + Token/Session  │                        │                     │
```

**Key Decision Point:** Should the portal issue **its own session tokens** (using Laravel Sanctum) after validating against the legacy DB, or should it **reuse the legacy system's tokens**?

### 2.3 Session Management Options

| Option                                | How It Works                                                                            | Pros                                                  | Cons                                                 |
| ------------------------------------- | --------------------------------------------------------------------------------------- | ----------------------------------------------------- | ---------------------------------------------------- |
| **A. Laravel Sanctum (Own Tokens)**   | Validate against legacy DB, then issue a Sanctum API token                              | Independent session management, good for detached SPA | User has two separate sessions, overkill for Inertia |
| **B. Laravel Sessions (Server-Side)** | Validate against legacy DB, use Laravel's built-in session (stored in `sessions` table) | Simplest to implement, native support for Inertia.js  | Cookie-based (perfect for monolith, bad for API)     |
| **C. Reuse Legacy Tokens**            | Call legacy system's auth endpoint, forward their token                                 | Single Sign-On (SSO) experience                       | Tight coupling, need their token validation logic    |

**Our Recommendation:** **Option B (Laravel Sessions)** — Since we decided on Vue + Inertia.js for the frontend, the application is technically a monolithic SPA. We do not need stateless API tokens (Sanctum/JWT). Inertia relies perfectly on Laravel's built-in session cookies, meaning we only need to validate credentials against the legacy DB once and rely on standard Laravel auth mechanisms.

---

## 3. Open Questions for SSE

### 🔌 A. Database Access

| #   | Question                                                                                           | Why We Need This                                        |
| --- | -------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| A1  | Can we get **direct read-only MySQL access** to the legacy database?                               | Determines if we connect directly or need an API        |
| A2  | What is the **users table name** and its **column structure**? (column names, types)               | We need to map our Laravel `User` model to their schema |
| A3  | What **credentials** (host, port, database name, read-only user) should we use for the connection? | Required for our `.env` configuration                   |

### 🔑 B. Authentication

| #   | Question                                                                                                     | Why We Need This                                                           |
| --- | ------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------- |
| B1  | **How are passwords hashed?** (`bcrypt`, `password_hash()`, `md5`, `sha256`?)                                | Laravel uses bcrypt by default. If theirs differs, we need a custom hasher |
| B2  | What **type of tokens** does the current system use? (JWT, Sanctum, custom `api_token` column, PHP session?) | Determines if we can/should reuse their token system                       |
| B3  | Should our portal **issue its own tokens**, or **reuse the legacy system's tokens**?                         | Core architectural decision — affects session independence                 |
| B4  | Is there an **existing login API endpoint** we can call? (e.g., `POST /api/login`)                           | Alternative to direct DB queries, if direct access isn't possible          |

### 👥 C. Roles & Access Control

| #   | Question                                                                                                    | Why We Need This                                              |
| --- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| C1  | **How are roles stored** in the legacy DB? (Column on users table? Separate `roles` table? Numeric levels?) | We need to map their roles to our `Agent / Validator / Admin` |
| C2  | What are the **possible role values** in their system?                                                      | We need a mapping table (e.g., their `level_2` = our `Agent`) |
| C3  | Can a user have **multiple roles**?                                                                         | Affects our authorization middleware design                   |
| C4  | Are there users who should access the portal but **don't exist in the legacy system yet**?                  | Determines if we need a provisioning workflow                 |

### 🔒 D. Security & Policy

| #   | Question                                                                                             | Why We Need This                                      |
| --- | ---------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| D1  | Is there an **account lockout policy** after failed login attempts?                                  | Our `audit_logs` already tracks `AUTH_FAILURE` events |
| D2  | What is the expected **session timeout**? (e.g., 30 min inactivity)                                  | Configures our session lifetime                       |
| D3  | Should a user be **logged out from other devices** when they log in from a new one? (single-session) | Determines if we enforce single-session               |
| D4  | Are there any **IP restrictions or VPN requirements** for accessing the portal?                      | Affects our deployment and middleware                 |

---

## 4. Current Portal Database Schema (For Reference)

The following tables are already implemented and ready:

```
users              → user_id, username, password_hash, role (Agent/Validator/Admin)
main_registry      → Core beneficiary data (Self-Employed / Trade categories)
staging_data       → Maker-Checker workflow (Pending → Approved / Rejected)
audit_logs         → Security tracking (AUTH_FAILURE, SEARCH_QUERY, DATA_EXPORT)
sessions           → Laravel session storage
```

> **Note:** The `users` table in our portal is currently a **mock/placeholder**. Once the legacy integration is confirmed, we will either:
>
> - **Point the User model to the legacy table directly**, or
> - **Sync user data from the legacy DB** into our local `users` table on login.

### 4.1 Development Mock: `Current.php` Helper

Until authentication is integrated, a mock helper class is used to simulate the authenticated user:

**File:** `app/Helpers/Current.php`

| Method            | Returns         | Purpose                       |
| ----------------- | --------------- | ----------------------------- |
| `Current::user()` | `User::find(1)` | Returns the mock user object  |
| `Current::id()`   | `1`             | Returns the hardcoded user ID |

This helper is used by `RegistryController` (to set `uploaded_by`) and `ReviewController` (to set `reviewed_by` and `approved_by`). Both methods contain `// TODO` comments to replace with `Auth::user()` / `Auth::id()` once real authentication is implemented. API routes currently bypass CSRF protection (using the `api` middleware group), which is appropriate for development but must be reviewed during authentication integration.

---

## 5. Next Steps After SSE Meeting

| If SSE Confirms...  | We Will...                                                 |
| ------------------- | ---------------------------------------------------------- |
| Direct DB access    | Configure `legacy` connection in `.env` and `database.php` |
| API-based access    | Build an HTTP client service to call their auth endpoint   |
| Own token issuance  | Install and configure Laravel Sanctum                      |
| Reuse their tokens  | Build custom token validation middleware                   |
| Role column mapping | Create a role mapping config and middleware guards         |
