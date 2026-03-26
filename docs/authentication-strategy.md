# Authentication & User Management Strategy

**Project:** Targeted Promotion & Data Management Portal  
**Prepared by:** Development Team  
**Date:** 2026-03-24  
**Status:** Active — Implemented

---

## 1. Overview

The Promotion Portal maintains its own **self-contained user registry**. All user accounts are created, managed, and authenticated directly within the portal. There is no dependency on any external or legacy database system.

---

## 2. User Management Architecture

### 2.1 Database Design

User data is stored in the portal's own `users` table within the portal's MySQL database.

| Column         | Type            | Nullable | Description                                             |
| -------------- | --------------- | -------- | ------------------------------------------------------- |
| `user_id`      | BigInt          | No       | Primary Key (auto-increment)                            |
| `name`         | String          | No       | Full name of the user                                   |
| `username`     | String (Unique) | No       | Login username                                          |
| `password`     | String          | No       | BCrypt-hashed password (never stored in plain text)     |
| `province`     | String          | No       | User's assigned province                                |
| `district`     | String          | No       | User's assigned district                                |
| `ds_division`  | String          | No       | User's assigned DS Division                             |
| `access_level` | String          | No       | `admin`, `decision maker`, `validator`, or `data entry` |

### 2.2 Password Security

Passwords are hashed using **BCrypt** via Laravel's built-in `Hash::make()` function before storage. This is a one-way hash — plain text passwords are never stored and cannot be recovered. To reset a forgotten password, an administrator edits the user record and sets a new password.

The `User` model enforces this automatically via the `password` cast:

```php
protected function casts(): array {
    return ['password' => 'hashed'];
}
```

---

## 3. Access Levels

| Access Level     | Responsibilities                                                  |
| ---------------- | ----------------------------------------------------------------- |
| `admin`          | Full portal access — manages users, views all analytics           |
| `decision maker` | Views analytics and reports                                       |
| `validator`      | Reviews pending data, approves or rejects entries                 |
| `data entry`     | Uploads Excel sheets, enters single forms, corrects rejected data |

---

## 4. User Management Backend

**Controller:** `app/Http/Controllers/UserManagementController.php`

| Method    | Route                    | Action                                                         |
| --------- | ------------------------ | -------------------------------------------------------------- |
| `index`   | `GET /admin/users`       | Renders User Management page (Inertia)                         |
| `index`   | `GET /api/users`         | Returns all users as JSON                                      |
| `store`   | `POST /api/users`        | Creates a new user with hashed password                        |
| `update`  | `PUT /api/users/{id}`    | Updates user; only re-hashes password if a new one is provided |
| `destroy` | `DELETE /api/users/{id}` | Deletes a user                                                 |

**Validation (store):** `name`, `username` (unique), `password`, `province`, `district`, `ds_division`, `access_level` — all mandatory.

**Validation (update):** Same fields, but `password` is optional. If left empty or whitespace-only, the existing password is preserved.

---

## 5. User Management Frontend

**Page Component:** `resources/js/Pages/Admin/UserManagement.vue`

### Features

- **User Table:** Displays all users with Last Name, Office (District | DS Division), Access Level badge, and Edit/Delete actions.
- **Filtering:** Filter by search term, province, district, DS division, and access level using cascading dropdowns.
- **Add/Edit Modal:** A full-featured modal form with:
    - Name, Username, Password, Confirm Password fields
    - Cascading Province → District → DS Division dropdowns (fetched from `LocationController` API)
    - Access Level selector
    - Empty/whitespace-only password on edit = no password change
- **Delete Confirmation:** A confirmation dialog before deletion.

---

## 6. Development Mock Helper

Some data-entry and review workflows still use a **development helper** to simulate the authenticated user:

**File:** `app/Helpers/Current.php`

| Method            | Returns         | Purpose                       |
| ----------------- | --------------- | ----------------------------- |
| `Current::user()` | `User::find(1)` | Returns the mock user object  |
| `Current::id()`   | `1`             | Returns the hardcoded user ID |

This helper is used by `RegistryController` and `ReviewController` to set `uploaded_by` and `reviewed_by` fields. Both contain `// TODO` comments to replace with `Auth::user()` / `Auth::id()` once those controllers are wired to the authenticated session.

> **Note:** The login page is now implemented, but the helper remains in use for the upload/review attribution fields until those controllers are updated.

---

## 7. Implemented: Login Page and Session Auth

The login page and session-based authentication are implemented with the following flow:

1. `GET /login` renders the Inertia page `Auth/Login`.
2. User submits `username` + `password` via `POST /login`.
3. Laravel validates credentials using `Auth::attempt()` and regenerates the session on success.
4. Successful login redirects to the intended URL (default `/dashboard`).
5. Failed login returns a 422 response with field errors and a flash error message for the UI.
6. `POST /logout` logs out the user, invalidates the session, and regenerates the CSRF token.
