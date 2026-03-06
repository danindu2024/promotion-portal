# Security Implementation Guide

**Project:** Targeted Promotion & Data Management Portal  
**Prepared by:** Development Team  
**Date:** 2026-03-05  
**Status:** Active — Implemented

---

## 1. Overview

This document describes all security controls currently active in the Promotion Portal. The application is a **Laravel 12 + Vue/Inertia.js** monolith. Since authentication against the legacy system is not yet integrated (see `authentication-strategy.md`), the controls below protect the publicly accessible API and UI surfaces.

---

## 2. Active Security Controls

### 2.1 HTTP Security Headers

**Middleware:** `App\Http\Middleware\SecurityHeaders`  
**Applied to:** All web pages and all API responses  
**Registered in:** `bootstrap/app.php`

Every HTTP response includes the following headers:

| Header                    | Value                                      | Prevents                          |
| ------------------------- | ------------------------------------------ | --------------------------------- |
| `X-Content-Type-Options`  | `nosniff`                                  | MIME-sniffing attacks             |
| `X-Frame-Options`         | `DENY`                                     | Clickjacking via iframes          |
| `X-XSS-Protection`        | `1; mode=block`                            | Legacy browser XSS (supplemental) |
| `Referrer-Policy`         | `strict-origin-when-cross-origin`          | Referrer URL leakage              |
| `Permissions-Policy`      | `camera=(), microphone=(), geolocation=()` | Disables browser hardware APIs    |
| `Content-Security-Policy` | See below                                  | Restricts resource origins        |

**Content-Security-Policy breakdown:**

```
default-src 'self'
script-src  'self' 'unsafe-inline'                          ← Vite HMR requires unsafe-inline in dev
style-src   'self' 'unsafe-inline' https://fonts.googleapis.com
font-src    'self' https://fonts.gstatic.com
img-src     'self' data:
connect-src 'self' ws: wss:                                 ← Vite HMR websocket
frame-ancestors 'none'                                      ← Enforces no-iframe at CSP level
```

> **Note:** Once the project moves to production, `'unsafe-inline'` on `script-src` should be replaced with a nonce-based CSP. Vite's production build does not require `unsafe-inline`.

---

### 2.2 Input Sanitization

**Middleware:** `App\Http\Middleware\SanitizesInput`  
**Applied to:** All API requests  
**Registered in:** `bootstrap/app.php`

All incoming string request parameters are recursively processed through two sanitization steps before they reach any controller:

1. **`strip_tags()` + `trim()`** — removes all HTML and PHP tags, preventing stored XSS.
2. **`ltrim($value, "=+-@\t\r")`** — strips leading formula-trigger characters, preventing **CSV/Formula Injection**.

**What is Formula Injection?**  
Excel treats any cell value starting with `=`, `+`, `-`, or `@` as a formula. An attacker could enter a value like `=CMD|'/C calc'!A0` into a name field. If that data is later exported to CSV and opened in Excel, the formula executes. The `ltrim` strips these characters before the value reaches the database.

**Additionally**, the frontend `downloadErrorSheet()` function in `DataEntry.vue` runs a `sanitizeCsvCell()` helper that strips leading formula characters before writing each value into the CSV file. This provides a second layer of protection at the point of export.

**Skipped fields** (never stripped):

- `password`
- `password_confirmation`
- `_token`

---

### 2.3 API Rate Limiting

**Mechanism:** Laravel's built-in `throttle` middleware  
**Registered in:** `routes/api.php`

| Route Group        | Limit          | Rationale                                        |
| ------------------ | -------------- | ------------------------------------------------ |
| `/api/locations/*` | **60 req/min** | Read-only, used by dropdowns                     |
| `/api/registry/*`  | **30 req/min** | Write operations (entry submission, bulk upload) |
| `/api/reviews/*`   | **30 req/min** | Sensitive approval/rejection actions             |

Clients exceeding the limit receive **HTTP 429 Too Many Requests**. Laravel includes a `Retry-After` header indicating when they can retry.

---

### 2.4 Input Validation

**Service:** `App\Services\RegistryValidator`  
**Used by:** `RegistryController` (single entry + bulk Excel upload)

Server-side validation rules are enforced for every data submission. Category-aware rules prevent cross-category field injection (e.g., Trade fields on a Self-Employed record).

Key rules:

| Field                               | Rule                                                                                       |
| ----------------------------------- | ------------------------------------------------------------------------------------------ |
| `category`                          | Required, in `[Self-Employed, Trade]`                                                      |
| `contact_number`                    | Required, regex `/^0\d{9}$/`                                                               |
| `whatsapp_number`                   | Nullable, regex `/^0\d{9}$/`                                                               |
| `email`                             | Nullable, valid email                                                                      |
| `national_id_number`                | Nullable, regex — Sri Lankan NIC format: 9 digits + V/X (old type) or 12 digits (new type) |
| `province / district / ds_division` | Must match Sri Lanka hierarchy config                                                      |
| Trade-only fields                   | `prohibited` on Self-Employed records                                                      |
| Self-Employed-only fields           | `prohibited` on Trade records                                                              |

---

### 2.5 File Upload Hardening

**Controller:** `RegistryController::uploadExcel()`  
**Endpoint:** `POST /api/registry/upload`

The Excel bulk upload is protected by a **dual-layer file check**:

1. **Extension check** — rejects anything not `.csv`, `.xls`, or `.xlsx`.
2. **MIME type check** — reads the actual file content from the OS (not the client-supplied filename). Allowed MIME types:
    - `text/csv`
    - `text/plain`
    - `application/csv`
    - `application/vnd.ms-excel`
    - `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

This prevents attackers from renaming a PHP script (or any malicious file) to `.csv` and uploading it.

Additional constraints:

- Maximum file size: **10 MB**
- Processing model: in-memory array (not stored on disk), so uploaded files are never written to the filesystem.

---

### 2.6 SQL Injection Protection

**Mechanism:** Laravel Eloquent ORM (built-in)

All database queries throughout the application use **Eloquent models and the Query Builder with parameterized bindings**. There are no raw SQL queries (`DB::statement()`, `whereRaw()`, etc.) in the codebase. This provides inherent protection against SQL injection.

---

### 2.7 Mass Assignment Protection

**Mechanism:** Laravel `$fillable` on all models

`MainRegistry` and `StagingData` use explicit `$fillable` arrays. Fields not in the whitelist are silently ignored even if passed in a request, preventing mass assignment attacks.

---

### 2.8 CSRF Protection

**Mechanism:** Laravel's built-in CSRF middleware (web group)

All web routes (`/data-entry`, `/review`) are protected by Laravel's CSRF middleware. API routes use the `api` middleware group (stateless), which is the standard Laravel approach — API routes do not need CSRF tokens because they are called via Axios with JSON headers, not via browser form submissions.

---

## 3. Duplicate Record Detection

While not strictly a security control, duplicate detection prevents data integrity attacks:

- **Before staging:** Checks `main_registry` and pending `staging_data` for existing `contact_number`.
- **Before approval:** A final race-condition guard checks `main_registry` again at the moment of approval to prevent two validators from approving the same record concurrently.

---

## 3.1 Maker-Checker Batch Workflow

The Validation Module groups all staging records by `batch_id` into **Upload Events** so a bulk upload of 500 rows appears as a single reviewable item in the queue.

### Queue (Aggregated View)

- `GET /api/reviews/pending` — Returns one row per unique `batch_id` with representative `category`, `district`, `ds_division`, and a `record_count`.
- Internally selects `MIN(id) as representative_id` per group and fetches all representative records in a single `whereIn` query (prevents N+1 queries).
- Single entries (`storeSingle`) generate a unique `SINGLE-<uniqid>` batch ID so they appear as individual queue rows.

### Batch Detail & Partial Approval

- `GET /api/reviews/batch/{batchId}` — Returns all pending rows in a batch. Returns **404** if the batch does not exist or has no pending records.
- Validators can **reject individual rows** inline. Each rejected row is stamped: `validation_status = Rejected`, `reviewed_by = <validator_id>`, and a mandatory `rejection_reason`.
- `POST /api/reviews/batch/{batchId}/approve` — Approves all remaining **pending** rows in a single database transaction.
    - Rows that fail the final duplicate guard are **auto-rejected** (not left as Pending) with a system-generated reason.
    - UPDATE rows with a missing/stale `target_record_id` are also **auto-rejected**.
    - Returns **207 Multi-Status** if any rows were skipped, along with an `errors` array listing affected rows and reasons.

---

## 4. Planned Controls (Pending Authentication Integration)

The following controls are documented in `authentication-strategy.md` and will be implemented once the legacy system integration is confirmed:

| Control                          | Status     | Depends On                             |
| -------------------------------- | ---------- | -------------------------------------- |
| Role-based access control (RBAC) | ⏳ Pending | Legacy user roles mapping              |
| Auth middleware on all routes    | ⏳ Pending | Legacy authentication integration      |
| Session timeout                  | ⏳ Pending | SSE session policy confirmation        |
| Login attempt lockout            | ⏳ Pending | Auth + audit_logs integration          |
| Single-session enforcement       | ⏳ Pending | SSE policy confirmation                |
| Stricter CSP (nonce-based)       | ⏳ Pending | Production deployment                  |
| API authentication (Sanctum)     | ⏳ Pending | Architecture decision from SSE meeting |

---

## 5. Key Files Reference

| File                                          | Purpose                            |
| --------------------------------------------- | ---------------------------------- |
| `app/Http/Middleware/SecurityHeaders.php`     | HTTP security headers middleware   |
| `app/Http/Middleware/SanitizesInput.php`      | Input sanitization middleware      |
| `bootstrap/app.php`                           | Middleware registration            |
| `routes/api.php`                              | Rate limiting per route group      |
| `app/Services/RegistryValidator.php`          | Category-aware input validation    |
| `app/Http/Controllers/RegistryController.php` | File upload MIME validation        |
| `app/Http/Controllers/ReviewController.php`   | Maker-Checker batch approval logic |
