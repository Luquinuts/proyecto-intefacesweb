# PASOS MVP

## Objective

Implement the smallest complete group-project MVP for PASOS: Firebase v10 email/password login using the first-partial flow, PHP session creation, consultation submission/listing, an administrator status flow, MySQL persistence, and REST endpoints.

## Confirmed decisions

- The submission is a group project, so Front and Backend are both in scope.
- Firebase Authentication remains the authentication method.
- The Firebase flow must match the first partial: PHP captcha validation, `signInWithEmailAndPassword`, ID-token retrieval, JSON POST to `crear_sesion.php`, confirmed PHP session, and redirect.
- The confirmed PHP session navigates to the consultations panel through the `#consultations` hash route, which also restores the authenticated view after reload.
- MVP roles are user and administrator/professional.
- MVP functions are login, consultation creation, consultation listing, consultation detail, and administrator status updates.
- MVP tables are `users` and `consultations`; categories are deferred.
- The `password_hash()` requirement remains pending and must not introduce a second authentication system yet.

## Tasks

- [x] T1 — Add the MySQL schema and shared PHP connection for users and consultations.
- [x] T2 — Add the first-partial-compatible PHP login/captcha/session flow and protected REST resources for consultations.
- [x] T3 — Add a Vue front end with two views, two components, Firebase login, consultation form/table, and the required jQuery interactions.
- [ ] T4 — Verify the MVP structure, PHP syntax, SQL/REST contracts, and record the remaining pending requirement.

## Authorized scope

- `frontend/**`
- `frontend/views/LoginView.js`
- `backend/**`
- `database/**`
- `odd/tasks/pasos-mvp.md`

## Acceptance criteria

- The front end visibly contains at least two Vue views and two Vue components.
- Firebase authentication follows the first-partial sequence without social-provider additions.
- PHP validates the session captcha, receives the Firebase session JSON, and redirects only after confirmation.
- The database contains related `users` and `consultations` tables.
- Consultation creation/list/detail/status operations use noun-based REST endpoints and JSON responses.
- User records are scoped to the authenticated session; administrator status updates are explicitly separated.
- jQuery is used only for the required form submission and consultation-table selection/filtering.
- No local password system is added while the `password_hash()` decision is pending.

## Verification

- Run PHP lint over every PHP file.
- Validate the SQL file syntax structurally and inspect foreign keys.
- Run bounded static checks for required views, components, Firebase flow markers, jQuery usage, and REST routes.
- Use cURL or equivalent local requests if a PHP server can run; otherwise record the environmental limitation.

## Route

- Delegated direct writer: implementation spans multiple non-trivial files and requires reading the project proposal, first-partial flow, and professor PHP/REST sources together.
- Parent verification: inspect the resulting tree, run bounded checks, and commit the MVP work unit.

## Progress

- MVP scope authorized by the user.
- MVP implementation is present under `frontend/`, `backend/`, and `database/`.
- The admin role correction and Vue event propagation are complete.
- Node syntax checks and static requirement checks passed.
- PHP lint and runtime integration remain pending because PHP is unavailable; MySQL is installed but no database runtime check was executed.
