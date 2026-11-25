# PASOS — Interfaces y Programación Web

PASOS is a proposed academic web project for a first-entry legal orientation service. It helps a person describe a situation, request human review, and understand a reasonable next step. It is not an automated legal-advice system, a chatbot, or a promise of legal representation.

## Repository purpose

The repository will contain the course project proposal and, after scope confirmation, a basic page-oriented front end and PHP/MySQL back end. The implementation must remain feasible to explain and reproduce by hand.

## Academic stack

- Front end: Vue.js, HTML5, CSS, Bootstrap, and JavaScript.
- Back end: PHP, MySQL, Firebase Authentication, REST, jQuery, HTML5, CSS, and Bootstrap.
- Database access: PHP `mysqli` and explicit SQL queries.

## Repository planning documents (not included in the private runtime ZIP)

- [`docs/Proyecto_PASOS.md`](docs/Proyecto_PASOS.md): strategic product concept and service boundaries.
- [`docs/propuesta.md`](docs/propuesta.md): proposed academic scope, user flow, data model, endpoints, and evidence plan.
- [`odd/tasks/pasos-project-proposal.md`](odd/tasks/pasos-project-proposal.md): documentation tasks and acceptance criteria.

## Review and verification path

1. Implement the complete Front and Backend scope required for a group submission.
2. Use the same Firebase v10 email/password and PHP-session flow documented in the first partial.
3. Review [`docs/propuesta.md`](docs/propuesta.md) against `docs/Practico Final Interfaces y Prog Web.pdf`.
4. Implement only the confirmed front-end views, PHP pages, related MySQL tables, authentication boundary, and REST endpoints.
5. Demonstrate the forms, authentication, database queries, REST responses, validation, captcha boundary, and final user flow in a video or PDF, together with the zipped Front, Backend, and `.sql` deliverables required by the assignment.

## Local development

For the private classroom ZIP, follow [Google Calendar/Meet setup](docs/google-calendar-meet-setup.md). Extract its `proyecto-intefacesweb-develop/` folder under XAMPP's `htdocs`, start Apache/MySQL, and import `database/pasos.sql` through phpMyAdmin. Use Node.js 22.12+ and npm. From the extracted project:

```bash
cd frontend
npm install
npm run dev
```

Open `http://localhost:5174/`. Vite uses strict port 5174. The current checkout proxies `/backend` to `http://localhost/proyecto-intefacesweb`, while the private ZIP instructions above use `proyecto-intefacesweb-develop`; this pre-existing deployment-path mismatch is not changed by the component migration. Confirm your installed Apache folder before testing backend requests. If the port is occupied, stop this project's previous frontend instance; do not switch ports for this delivery. Google returns through the registered Apache callback `http://localhost/proyecto-intefacesweb-develop/backend/api/google_callback.php`, then back to port 5174. `PASOS_FRONTEND_URL` can override the configured frontend return on a custom installation.

The private ZIP intentionally includes ignored `backend/config/google.local.php`; a Git checkout does not. Keep the ZIP private and outside Git/public web roots. Never serve the backend with a static server. Obtain account credentials separately, and delete sensitive archive copies when no longer needed. Browser OAuth and a real meeting still require manual verification; another installation is not guaranteed without testing.

The current MVP is implemented on the `developer` branch. The strategic proposal remains the scope reference, while runtime verification still requires XAMPP, MySQL, Firebase, and Google OAuth configuration.

### Frontend component checks

All six Vue components use `.vue` single-file components with ordinary scripts and the Options API. Vite compiles their templates through `@vitejs/plugin-vue`; `main.js`, `config.js`, and `vite.config.js` remain JavaScript.

After installing frontend dependencies, run from the repository root:

```bash
node --experimental-vm-modules tests/frontend-sfc.mjs
npm --prefix frontend run build
```

The offline regression checks SFC compilation, imports, PHP-session roles, navigation, delegated jQuery submit/selection/filter/keyboard handlers (with event stubs), late-rendered forms, cleanup, and failed-login retries. It does not run a real browser, Firebase, Google OAuth, or FullCalendar. The private ZIP is not refreshed by these changes.

### Firebase identity and password-hash boundary

Firebase remains the only login authority. After the selected Firebase v10/CAPTCHA flow, PHP submits the ID token to Google's `accounts:lookup`, checks the configured project and token timestamps/subject, and independently verifies the submitted password through `accounts:signInWithPassword` using Google's returned identity. Rejection occurs before database access or session authentication changes; the stored hash never permits offline/local login. Public `backend/config/firebase.php` must match the browser project; it contains no service-account credential.

Use HTTPS outside localhost: the login JSON now includes the password as well as the ID token. Neither is logged, returned, or stored in the PHP session. Outbound requests use fixed HTTPS Google endpoints, TLS verification and timeouts. Only verified passwords populate nullable `users.password_hash`; existing hashes are reused or updated with `password_verify`/`password_needs_rehash`. Argon2id is preferred when available. Without it, bcrypt receives base64-encoded SHA-384 of the exact password (64 bytes), preventing silent 72-byte suffix truncation; this is the consistent format for this application's bcrypt hashes, not raw-password bcrypt. Passwords are never trimmed; the server rejects passwords exceeding 4096 bytes.

For an existing MariaDB database, apply `database/password_hash_migration.sql`, not the full seed. It only adds the nullable column and can run twice. New installations use `database/pasos.sql`.

```bash
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php
```

These deterministic tests mock Google's transport, including rejected identity/password, revocation timestamp, network failures and hash changes. They do not prove real Firebase token/revocation behavior, API-key restrictions, or server connectivity. Existing PHP sessions are not continuously revalidated against Firebase. Manual approved Firebase/browser checks remain required; no real account authentication or mutation is part of the automated checks.

### Consultation scheduling and contacts

Before deploying this backend against an existing MariaDB schema, apply `database/consultation_contacts_migration.sql` in the selected database, in addition to the pending password-hash migration. It only adds nullable `users.phone VARCHAR(16)` and the `(professional_id, status, scheduled_at)` index, and is repeatable on MariaDB. Do not rerun `pasos.sql` or its demo seeds on existing data. New installations use the updated base schema. Migration checks passed on an isolated synthetic database; the live PASOS database and deployed XAMPP copy remain untouched and unapplied.

- Phone contact requests require the first international phone (`+`, 8–15 digits; spaces, hyphens and parentheses accepted). The authenticated user's row is locked, normalized phone and consultation commit together, and an existing phone is never overwritten by a request. Video requests do not require a phone. The session endpoint restores only the current user's phone; collection/item access limits phone visibility to owner, administrator or assigned professional.
- Bookings last 30 minutes in `America/Argentina/Buenos_Aires`. Phone and video appointments share a professional's schedule. `09:00`/`09:30` is allowed; `09:00`/`09:15` is rejected with HTTP 400 before Google changes. Each update uses a transaction at READ COMMITTED, locks the professional user before fresh consultation data, and checks overlaps with a nonlocking SELECT after acquiring that mutex. The mutex serializes the same professional; READ COMMITTED gives the subsequent overlap read a fresh committed view. An isolated real test found that locking the overlap query additionally blocked a distinct professional; removing only that extra lock made all four concurrency cases pass while preserving the professional/consultation locks. Closing frees the local slot. The visible occupied-slot buttons are advisory; the server is authoritative for every REST request.
- New/rescheduled bookings require strict `YYYY-MM-DDTHH:mm` future dates. A same-slot check of an already scheduled event can retrieve a pending conference even after its start/end; it does not reopen a closed appointment. A closed appointment requires a different future date to be rescheduled.
- Both listing APIs return offset-bearing `scheduled_at_iso`, `ends_at`, `can_join` and `meeting_state`, retaining `scheduled_at` for forms. Raw Meet links are omitted outside `scheduled <= server now < scheduled + 30 minutes`, including for administrators. All UI entry uses authenticated `GET backend/api/join_meeting.php?id=<id>`, which rechecks owner/admin/assigned-professional permission, scheduled status and the window at click time, then redirects only to a stored HTTPS `meet.google.com` URL. The browser clock can only affect presentation, not grant access. Use the refresh buttons at the start time; the one-second UI timer expires displayed buttons without backend polling.
- Calendar invites use the owner's email read from the consultation's database relation, never a request-selected address. Creates and meaningful changes use `sendUpdates=all`; unchanged conference checks only GET. Missing owner attendees and old private descriptions trigger a PATCH; unrelated legitimate attendees and their response state are preserved. The private `pasosOwnerEmail` event marker identifies the prior managed attendee for replacement after an owner email change; unmarked legacy invitees require manual reconciliation because their attribution is unknown. Other private/shared metadata is preserved. Invitations contain a public consultation number, never the case description or phone. Existing private descriptions are removed when that event is next synchronized, not by a bulk remote migration.

**External limits:** PASOS cannot revoke a Google URL already copied or included in a Calendar invitation, terminate a running meeting, or override Google's host admission/account settings. Closing locally does not cancel the remote Calendar event. The client manually uses or creates their own Google/Gmail account and their Firebase account; there is no Gmail signup automation. The professional still controls admission in Google Meet.

**Failure boundary:** local transactions are atomic, but MySQL and Google are not a distributed transaction. An uncertain remote insert or a remote update followed by local save/commit failure can leave a Google event/invitation without the matching local state. Retrying the same consultation/date reuses its stable event identity (GET/PATCH, no duplicate insert loop). A conflicting retry is rejected locally before Google and may require manual Calendar reconciliation of that earlier remote residue. While the professional mutex is held, each Calendar/refresh request has a 3-second connect and 5-second total timeout; at most four Calendar requests plus one refresh bound outbound waiting to approximately 25 seconds, excluding local database waits. A waiting request can still time out at the database/server boundary and must be retried.

Offline checks, from this repository root:

```bash
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/consultation-rules.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php
node --experimental-vm-modules tests/frontend-sfc.mjs
npm --prefix frontend run build
git diff --check
```

`consultation-rules.php` exercises shared SQL transaction flow with a database fake and actual endpoint code in isolated processes with database/transport boundaries replaced. It covers phone rollback/reuse, permissions, strict dates, overlap/adjacency/self exclusion, status changes and click-time redirects/denials. Neither fakes nor SFC compilation prove real MySQL concurrency, migrations, browser behavior or delivery of Google invitations.

For real concurrency, a verifier must separately provision an **empty disposable** `pasos_test_<suffix>` schema with the migrated tables. Never point this runner at live PASOS data. It only inserts/deletes its own uniquely named fixture rows and makes no Google calls:

```bash
PASOS_CONCURRENCY_ALLOW=1 PASOS_CONCURRENCY_DB=pasos_test_scheduling \
  /opt/lampp/bin/php tests/consultation-concurrency.php
```

Optional `PASOS_CONCURRENCY_HOST/PORT/USER/PASSWORD` configure that disposable connection. Without explicit opt-in and the safe database prefix, the runner exits 2 with `UNAVAILABLE` without connecting. It starts two independent PHP/MySQL sessions with a controlled barrier: same professional/time accepts one and rejects one; adjacent bookings both succeed; rollback permits recovery; a different professional completes while the first mutex remains held. A missing database/service is unavailable, not a pass.

**Isolated verification completed:** the parent/verifier ran the unchanged real concurrency runner against bundled MariaDB 10.4.32 using synthetic fixtures, a private data directory/socket and `--skip-networking`. Distinct-professional independence first failed with the extra overlap `FOR UPDATE`, then all four cases passed after that single lock was removed. `database/consultation_contacts_migration.sql` applied twice preserved baseline rows/counts/IDs/roles and foreign-key definitions/rules, with nullable `VARCHAR(16)` phone and the expected schedule index. The other two migrations also preserved baseline data when applied twice, and runner fixture cleanup preserved the baseline. Evidence: `/tmp/opencode/pasos-proof-green-A6RLo9Gc/verify.py`, `report.json` and `concurrency.log` (local temporary artifacts, not shipped). The owned synthetic server stopped with exit 0 and its socket was removed. Production XAMPP and live PASOS were not touched. This proves the isolated scenarios, not a production migration or deployment. Real-browser/timezone behavior, actual Google email delivery/admission and live integration remain unverified; native assessment/review/commit evidence remains pending the parent.

Official API evidence consulted for this change:

- [Calendar events.insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert): attendee email, `sendUpdates=all` and conference data version 1.
- [Calendar events.patch](https://developers.google.com/workspace/calendar/api/v3/reference/events/patch): patch arrays replace existing arrays; preserve legitimate attendees explicitly.
- [Meet SpaceConfig and access types](https://developers.google.com/workspace/meet/api/reference/rest/v2/spaces#SpaceConfig): meeting URI and Google-controlled admission rules, separate from PASOS entry authorization.
