# PASOS academic implementation proposal

## Decision summary

PASOS will be scoped as a small web application that turns an initial legal concern into a request for human orientation. The academic implementation will demonstrate the required Vue, Firebase, PHP, MySQL, REST, jQuery, form, and security boundaries without presenting the result as a production legal platform.

The project must preserve the strategic promise: **PASOS helps a person understand what is happening and decide how to continue.** It does not provide automated legal advice, guarantee an outcome, or promise representation.

## Status labels

- **Confirmed requirement:** stated in `docs/Practico Final Interfaces y Prog Web.pdf` or the project instructions.
- **Proposed project decision:** a deliberately small implementation choice for review.
- **Needs confirmation:** cannot be decided safely without the teacher or user.

## Scope

### Confirmed requirements

The practical assignment requires a front end using Vue.js, HTML5, CSS, Bootstrap, and JavaScript; at least two Vue components and two views; Firebase Authentication; and a REST connection when the project is completed by two people. The back end must use PHP, MySQL, Firebase, REST, jQuery, HTML5, CSS, and Bootstrap. It also requires a MySQL database with at least two tables and relationships, `mysqli` SQL access, correct forms and superglobals, login captcha verification on the back end, input sanitization, password hashing, REST endpoints, and delivery evidence.

### Proposed project decisions

- One user can submit a short consultation request without classifying it by legal branch.
- A simple status flow is sufficient: `received`, `under_review`, `scheduled`, and `closed`.
- The academic demo will use a small set of seeded or manually created records rather than a complete scheduling or payment platform.
- The interface will communicate that a professional reviews the request and that the result is an orientation, not a legal guarantee.

### Needs confirmation

- Whether this is a one-person or two-person submission. The assignment requests the complete Front and Backend version for two people and asks a one-person project to confirm its scope with the teacher.
- Which Firebase sign-in option is expected: social providers, email/password form, or both.
- Whether the teacher expects a local PHP password flow in addition to Firebase Authentication. Firebase normally manages authentication credentials; the assignment separately mentions password hashing, so this boundary must be confirmed before storing any local credential.
- Whether a real captcha provider, a local development substitute, or a teacher-approved demonstration is required.
- Whether the consultation evidence may contain real personal or sensitive legal information. The demo should use invented data unless permission and privacy rules are confirmed.

## User flow

1. **Landing view — Understand the service.** The user sees the PASOS purpose, limits, confidentiality notice, and a clear action to sign in or start a request.
2. **Login view — Authenticate.** The user signs in through the confirmed Firebase method. The login form sends the captcha token to the PHP boundary for server-side verification before the request is accepted.
3. **Request view — Tell us what happened.** The authenticated user submits a short description, contact preference, and optional category. The form shows basic client feedback but does not make legal conclusions.
4. **Requests view — Follow the request.** The user sees their own requests in a Bootstrap table. jQuery handles the required data submission and selection-table interaction, while the REST API supplies the records.
5. **Request detail — Review the next step.** The user sees the request status and a plain-language professional note when one exists. The page states that the application does not replace a lawyer-client relationship.

## Front-end proposal

### Views

The minimum Vue application will contain these two views:

- `LoginView`: Firebase login form, captcha boundary message, validation feedback, and link to the request flow.
- `ConsultationsView`: request form plus the authenticated user's consultation table and status summary.

If the teacher accepts a third view, `ConsultationDetailView` can be added for a single request; it is not required for the minimum two-view scope.

### Components

At least these two reusable Vue components will be demonstrated:

- `LoginForm`: renders fields, displays validation errors, and starts the confirmed Firebase sign-in flow.
- `ConsultationTable`: displays REST results, filters or selects a request, and exposes the selected record to the view.

Bootstrap supplies the basic form, table, alert, and responsive layout styles. Plain HTML5, CSS, and JavaScript remain visible in the implementation so the academic requirements can be inspected directly. No additional framework or dependency is proposed.

## Authentication, forms, and security boundaries

### Confirmed assignment boundary

- Firebase Authentication must allow users to sign in through the approved social provider or login form.
- Login input must be validated and sanitized.
- The login form must include captcha protection handled on the back end.
- PHP forms must use the required HTTP method and superglobals, with processing separated from page presentation where applicable.
- Passwords that are stored by the application must use a password-hashing function rather than plain text.

### Proposed boundary

The client will obtain the Firebase authentication result, then the PHP API will accept only the authenticated user's identity and request data needed for the operation. PHP will validate types and required fields, sanitize values at the input boundary, use prepared `mysqli` statements for request-controlled values, and return explicit HTTP status codes.

The login boundary will verify the captcha server-side before accepting the login-related request. The implementation will not claim that client-side validation or a hidden captcha field is sufficient.

If a local password is confirmed as necessary, PHP will store only a `password_hash()` result and verify it with `password_verify()`. If Firebase is the sole credential authority, the database will store the Firebase user identifier and no duplicate password; this decision must be approved before implementation.

## PHP/MySQL data model

### Proposed minimum schema

The SQL deliverable will contain at least these related tables:

| Table | Minimum fields | Relationship and purpose |
|---|---|---|
| `users` | `id`, `firebase_uid`, `name`, `email`, `created_at` | One application user owns many consultation requests. `firebase_uid` links the local record to Firebase without duplicating credentials. |
| `consultations` | `id`, `user_id`, `description`, `status`, `requested_at`, `professional_note` | `user_id` is a foreign key to `users.id`; stores the request and its review status. |
| `consultation_categories` | `id`, `name` | Optional category selected by the user; `consultations.category_id` may reference it if the teacher approves the extra table. |

The first two tables satisfy the minimum relationship requirement. `consultation_categories` is useful for demonstrating a selection table but can be omitted if the one-person scope must be smaller. The final `.sql` file must define keys, the foreign key, sample non-sensitive data, and the selected schema consistently with the PHP queries.

PHP will use a shared basic `mysqli` connection and explicit prepared statements for values received from forms or API requests. No ORM, migration system, framework router, or production service architecture is proposed.

## REST API outline

The API will use noun-based resource URLs, JSON responses, and HTTP methods:

| Method and endpoint | Purpose | Expected evidence |
|---|---|---|
| `GET /api/consultations` | List consultations belonging to the authenticated user. | Browser or client response shows JSON and an authorization boundary. |
| `GET /api/consultations/{id}` | Retrieve one consultation owned by the authenticated user. | Response inspection shows a single record or a clear `404`. |
| `POST /api/consultations` | Validate and create a consultation request. | Form submission creates a related MySQL record and returns a success status. |
| `PATCH /api/consultations/{id}` | Update the permitted status or professional note. | Response inspection shows the updated record and rejects invalid input. |

The exact authentication header, status-transition permissions, and whether `PATCH` is required by the teacher remain to be confirmed. The PHP route may be implemented with straightforward request-method checks; a framework router is out of scope.

## jQuery requirement

jQuery will be used only where the assignment asks for it:

- submit the consultation form or refresh its data through a clear POST/GET handler; and
- support selection, filtering, or row interaction in the consultation table.

Vue remains responsible for the declared views and components. The proposal does not introduce a second front-end framework or a broad jQuery abstraction.

## Delivery evidence

The final review should make each requirement observable:

| Requirement | Evidence to capture |
|---|---|
| Two Vue views and two components | Project tree and a short navigation demonstration. |
| Bootstrap, HTML5, CSS, and JavaScript | Visible forms, table, semantic page structure, and inspected source. |
| Firebase Authentication | Successful approved login method and rejected invalid login. |
| Captcha on the back end | Login attempt with missing or invalid captcha rejected by PHP. |
| Forms and superglobals | Form request, PHP processing page/API route, validation errors, and redirect or JSON response. |
| Sanitization and password hashing | Source inspection showing input handling and `password_hash()`/`password_verify()` if local passwords are approved; otherwise Firebase-only credential boundary documented. |
| MySQL and relationships | `.sql` file, table structure, foreign key, inserted request, and related query result. |
| `mysqli` queries | Source inspection plus a successful and an invalid database request. |
| jQuery | Form submission and selection-table interaction visible in the browser or source. |
| REST | Endpoint method, JSON response, success status, and at least one validation/error response. |
| Complete functionality | Video or PDF showing the user flow from login through request review. |
| Required packaging | Zipped Front and Backend folders plus the `.sql` database export. |

Optional diagrams may show the user flow and the `users`–`consultations` relationship, but diagrams are not required by the assignment.

## Explicit exclusions

This proposal does not include automated legal advice, legal-document generation, payment processing, real scheduling, a professional network, a public legal-content library, AI integration, deployment infrastructure, or production-grade multi-tenant architecture. Those features belong to later validation stages in the strategic PASOS document and would make the academic scope harder to verify.

## Unresolved decisions before implementation

1. Confirm one-person versus two-person scope and the required breadth of the Backend.
2. Confirm the Firebase provider or providers and the Firebase-to-PHP identity verification method permitted in the course environment.
3. Confirm whether local password storage and the `password_hash()` demonstration are required when Firebase Authentication is used.
4. Confirm the captcha provider and the exact server-side verification expected for the login form.
5. Confirm whether the optional category table and the `PATCH` endpoint are useful for grading or should be removed from the minimum implementation.
6. Confirm the data-privacy rules and use invented records for every public demonstration unless real data is explicitly authorized.

Until these points are resolved, this document is an implementation proposal, not a claim that PASOS has already been built.
