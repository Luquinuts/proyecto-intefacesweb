# Consultation scheduling and client contacts

## Objective and accepted rules

Implement the approved proposal: prevent overlapping 30-minute consultations per professional; allow PASOS meeting entry only during the scheduled 30 minutes; request and retain the authenticated client's phone once; add a nullable phone field and display it only to authorized staff; invite the consultation owner's Google email to Calendar events. Google account creation is manual and external links already copied cannot be revoked by PASOS.

## Scope and constraints

- Preserve Vue SFC Options API, jQuery form/table behavior, Firebase-authoritative authentication, PHP/mysqli REST and stable Google event identities.
- Use Buenos Aires timezone explicitly. Reject overlapping phone/video bookings atomically, including concurrent requests; exclude the current consultation on reschedule and free closed consultations.
- Phone uses an international canonical string with leading + and at most 15 digits. Existing saved phones are reused and not overwritten by submitted empty/other values. Store profile and new consultation together.
- Owner/admin/assigned professional may read appropriate phone data; deny unrelated professional item access.
- Include trusted owner email as attendee with notifications on creation/meaningful updates, not repeated link checks. Never include sensitive consultation descriptions in invitations.
- No new dependencies, automated Gmail signup, credentials/password sharing, Google authenticated test requests, unrelated login changes, push or PR.

## Tasks and routing

- [x] C1 — Source implementation and offline verification complete: coherent scheduling/contact/access/invitation behavior, additive MariaDB migration and deterministic RED/GREEN regression tests. No commit, push or deployment; isolated database evidence is recorded under C2.
- [x] C2 — Source and isolated functional verification complete: parent/independent checks, corrected three regressions, real two-session concurrency and repeatable migration/data-preservation proof. Live migration/deployment, real browser/Google checks and native assessment/review/commit evidence remain explicitly pending the parent.

## Acceptance and verification

Test phone capture/reuse and privacy; overlapping/simultaneous booking rejection and adjacent bookings; same-consultation retries/reschedules; start/end join boundaries and closed/unauthorized denial; server revalidation at click; invited owner and no repeated notifications. Use pure/helper tests plus meaningful endpoint mocks; real MySQL concurrency must be verified on a disposable test schema or honestly blocked.

Run focused PHP tests, existing Firebase/Calendar tests, extended frontend SFC tests, changed PHP lint, npm --prefix frontend run build and git diff --check. Default deterministic test-first policy: observe RED before implementation, then GREEN. No runtime/live proof may be inferred from mocks.

## Delivery

Strategy single-pr: retain this one coherent approved feature as a single delivery, with local Conventional Commit work units, no publication. Forecast approximately 700–1,100 authored additions/deletions including tests; this is advisory, not a hard cap or reason to omit checks. Native review switch is user-owned; assess the final normalized candidate and honor candidate consent. No source-mutating normalizer after review freeze.

- Final assessed implementation: 17 files, 1,230 authored additions/deletions. Native risk medium, slice_budget_reached. User explicitly selected Skip this time; exact provider decline returned action declined and consent declined_this_candidate for the assessed target. No native review record or approval is claimed; future reviews remain enabled.
- C1/C2 work-unit commit: `89a0dd7` (`developer: feat(consultations): add safe scheduling and saved contacts`). Post-commit assessment remained medium/slice_budget_reached; the implementation remains explicitly unreviewed under the candidate-scoped decline. No push, live migration/deployment, account creation or private ZIP rebuild was performed.

## Initial evidence and prerequisites

- Baseline 29887d7 is clean and synchronized. Existing scheduling lacks collision checks; joining checks only the start date; users has no phone; Calendar events lack attendees.
- Ports 80, 3306 and 5174 are not listening. The earlier password-hash migration and Apache backend deployment remain unapplied. Do not deploy a partially migrated backend or rerun full schema seeds.
- Manual Google account creation/consent and an actual invite/meeting cannot be completed by offline implementation.
- Engram observation 6316 mirrors this full document under topic `odd/consultation-scheduling-contacts/tasks`, project `agente-interfaces`; local document is the source locator.

## C1 source outcomes

- Added `backend/lib/ConsultationRules.php` for strict international phone normalization/reuse, transactional client-profile plus consultation creation, role checks, Buenos Aires date parsing and the shared 30-minute meeting window. POST ignores request-selected user IDs. Collection filtering remains scoped; unrelated professional item GET is denied before serialization.
- All status/scheduling updates use a READ COMMITTED transaction, the professional user-row mutex, then a fresh consultation lock and nonlocking overlap SELECT after acquiring the mutex, including administrator updates. Real isolated proof found the extra overlap FOR UPDATE blocked a distinct professional; the parent removed only that extra lock, preserving both row locks and READ COMMITTED. Same-slot scheduled-event checks exclude self and remain possible after expiry; closed appointments require a different future booking date. Conflicts precede Google mutation.
- Added authenticated `backend/api/join_meeting.php` for click-time role/status/window validation and an allowlisted HTTPS Meet redirect. Consultation/video lists suppress raw links outside the window and add explicit ISO start/end/access fields. All UI joins use this endpoint; occupied slots are disabled using the professional's existing scoped list. Refresh buttons update server access state, and cleaned-up UI timers expire buttons without backend polling.
- Added own-phone restoration through `backend/sesion_actual.php`, first-phone loading/error/prompt/reuse handling and an accessible phone table column. Added nullable base-schema phone and scheduling index plus repeatable `database/consultation_contacts_migration.sql`; no seeds, database or deployed copy were changed.
- Calendar synchronization invites the owner email derived from the database, preserves existing attendees, clears sensitive event descriptions and notifies only on creates/meaningful PATCHes. Stable event identity and GET/PATCH recovery remain. Calendar and refresh calls use bounded 3-second connect/5-second total timeouts; the maximum sequential outbound wait during a scheduling transaction is approximately 25 seconds, excluding local DB waits.
- README records migration/deployment prerequisites, manual Google/Firebase account use and host admission, refresh behavior, disposable concurrency instructions and official API evidence. Local close does not cancel a Google event; copied/invited URLs cannot be revoked by PASOS. Remote event creation/update followed by a local rollback can leave remote residue; stable retries reduce duplication but do not provide distributed atomicity.

## C1 RED / GREEN evidence

Before implementation, the exact consultation PHP runner failed because `ConsultationRules.php` did not exist (exit 255); extended Calendar tests failed on missing owner attendee (exit 255); extended SFC tests failed on missing `profileLoading` state (exit 1). These failures were observed, not inferred.

Foreground checks after implementation:

```bash
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/consultation-rules.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php
node --experimental-vm-modules tests/frontend-sfc.mjs
npm --prefix frontend run build
git diff --check
```

All pass. PHP syntax checks pass for every new/changed public PHP/helper/test file. Vite reports the existing large-bundle warning; the VM runner reports its experimental-module warning. `tests/consultation-rules.php` uses SQL-shaped database fakes for real transaction/control flow plus isolated execution of actual API endpoint source with external boundaries replaced. Coverage includes failed POST rollback, saved-phone reuse and spoofed owner prevention, strict dates, boundaries, overlap/adjacency/self/retries, administrator status changes, unauthorized phone denial and expired/closed/early/unsafe click denial. Calendar transport tests cover verified owner attendance, safe descriptions, retained invitees, notification-free unchanged checks, phone events and existing retry regressions. SFC tests cover first phone/load/reload/failure/send/reuse, busy slots, expiring join routing and preserved delegated jQuery behavior.
Actual candidate size including untracked source/tests/migration/task files: 935 additions and 295 deletions across 17 files (1,230 changed lines). Excluding the parent-created task document: 844 additions and 295 deletions across 16 files. The task grew from the parent's 40 lines to 91 lines. Generated `frontend/dist` and private/ignored files are excluded; this exceeds the advisory forecast without splitting the approved coherent delivery.

`/opt/lampp/bin/php tests/consultation-concurrency.php` exits 2 with `UNAVAILABLE` by default and makes no connection. Its explicit opt-in accepts only an already-provisioned disposable `pasos_test_*` schema and uses two PHP/MySQL sessions to test same-slot one-accept/one-reject, adjacency, rollback recovery and distinct-professional independence. The original writer did not enable it; the subsequent isolated parent/verifier run below supplies actual database proof. Mocks alone do not establish real database concurrency.

## C2 completion and remaining release gates

- The original independent verifier rechecked all three P2 corrections below and confirmed them fixed; all three PHP test runners and the Node SFC runner pass. Parent pure-rule/assert checks, PHP helper lint and diff checks also pass. Native assessment, review/approval and work-unit commit evidence are still pending the parent; none is inferred from successful tests.
- Migration idempotence/data preservation and all four real concurrency cases passed on the isolated synthetic database below. Applying migrations to live PASOS and deployment remain unapplied; isolated proof is not live acceptance.
- Verify browser UI/calendar behavior and timezone presentation; manually verify an actual invitation, reschedule/no-repeat notification, Google account choice and host admission when authorized services/accounts are available.
- Resolve the earlier backend deployment/password migration prerequisites before any live rollout. Only the owned synthetic MariaDB instance was started and stopped for proof; production Apache/MySQL, XAMPP copies, private ZIPs and live database were untouched. No Google/Firebase authenticated request was sent.

## Bounded independent-verification corrections

- Calendar owner changes now use `extendedProperties.private.pasosOwnerEmail` to identify and remove only the previously managed invitee, retaining unrelated guests and other private/shared metadata. New events and synchronization PATCHes carry the marker; unchanged marked events only GET and do not notify again. Unmarked legacy invitees cannot safely be attributed to PASOS and are preserved for manual reconciliation; adding a marker does not retroactively identify those earlier invitees. This does not revoke copied URLs or erase notifications already delivered. Official metadata support: https://developers.google.com/workspace/calendar/api/guides/extended-properties (public documentation consulted, no authenticated call).
- Successful collection refresh now reconciles the selected consultation by ID, clearing it when absent, without invoking selection/form initialization or overwriting draft note/status/date values. Removed redundant post-refresh reconciliation from update/schedule/close callers to avoid dereferencing a cleared selection.
- Date/future-time/overlap requirements now apply only to phone/video appointments. Legacy email received-to-scheduled and scheduled note-only changes with no date remain valid, while attempting a Google booking for email is still rejected. Professional mutex, permissions and phone/video requirements remain intact.
- Observed correction RED: Calendar retained the prior marked owner (exit 255); legacy email scheduling threw the appointment future-date error (exit 255); selected collection state retained `can_join=false` after refreshed `true` (SFC exit 1). After the bounded fixes, the same exact consultation/Calendar/Firebase/SFC commands above pass, all nine original changed/new PHP files pass lint, frontend build succeeds and `git diff --check` passes. Existing bundle-size/experimental-VM warnings remain. Tests also cover first/stable markers, metadata preservation, unrelated/legacy invitees, no duplicate notifications, retained draft fields, missing selection and strict phone/video behavior. No schema or other non-authorized source files changed in this correction.
- That bounded correction used no database connection, live API request, service start, commit, push, ZIP or deployment. Subsequent isolated database proof is recorded below; real-browser and Google invitation/admission checks remain pending.

## Isolated MariaDB proof and minimal lock correction

- Evidence locator: `/tmp/opencode/pasos-proof-green-A6RLo9Gc/verify.py`, `report.json` and `concurrency.log`. Bundled MariaDB 10.4.32 ran with synthetic fixtures, a private data directory/socket and `--skip-networking`; no production XAMPP/PASOS data or services were used. These local temporary artifacts are not shipped or permanent CI receipts.
- The unchanged real two-session runner first produced RED for distinct-professional independence. Read-only diagnosis identified the overlap SELECT FOR UPDATE as the extra blocking lock. The parent removed only that clause from the overlap query; the professional mutex, consultation row lock and READ COMMITTED transaction remain. GREEN output: `PASS REAL MYSQL: same`, `adjacent`, `rollback`, `distinct`.
- Contacts migration pass 1 and pass 2 both preserve baseline counts, user/consultation rows and IDs, roles, foreign-key definitions and foreign-key rules. Reported phone type/nullability/default: `varchar(16) / YES / NULL`; existing phones remain null. Index: `idx_consultation_schedule (professional_id,status,scheduled_at)`. Password-hash and Google Calendar migrations also preserve baseline when applied twice; fixture cleanup preserves baseline rows.
- The isolated server stopped in cleanup with exit 0 and its private socket was removed; the report records success. This bookkeeping turn made documentation changes only and did not run database/server commands. No live migration/deploy, distributed MySQL/Google atomicity, copied-link revocation or browser/email delivery is claimed. Unmarked legacy invitees still require manual reconciliation.

## Next step

Parent records native assessment/review consent and any eventual work-unit commit, then separately authorizes live migration/deployment and real-browser/Google integration checks. C1/C2 source plus isolated functional verification are closed, not release approval. Keep the single-pr strategy, no push; do not infer live acceptance from isolated results.
