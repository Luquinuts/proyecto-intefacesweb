# Google Meet private delivery

## Objective and scope

Configure the existing Calendar-backed Meet integration and prepare a private classroom ZIP. Keep Firebase login, PHP/MySQL, the professional role, and the registered Apache callback unchanged. The teacher starts Apache/MySQL, imports the provided SQL, and runs npm install and npm run dev in frontend.

## Constraints

- Never commit OAuth secrets, passwords, access tokens, refresh tokens, or downloaded credential JSON.
- Include only the OAuth application configuration intentionally in the private ZIP, not live session/token database contents.
- Keep the callback http://localhost/proyecto-intefacesweb-develop/backend/api/google_callback.php and standardize the frontend on localhost:5174.
- No new dependencies or unrelated authentication changes. Browser consent remains a manual user action.

## Tasks and routing

- [x] G1 — Configure local credentials and frontend return, preserve OAuth result, and handle pending conferences and retries without duplicate events. Delegated direct: multi-file implementation and preparation require one writer. Functional acceptance: offline conference tests, PHP lint, frontend syntax/build, and local API smoke checks.
- [x] G2 — Write minimal delivery instructions and create/inspect a private ZIP with an explicit file allowlist. Delegated direct: packaging needs secret-aware inspection and a bounded command worker. Acceptance: correct archive root, installable package metadata, no tokens/history/dependencies, intentional OAuth config present.
- [ ] G3 — User completes Google consent and verifies one real meeting. Pending external/browser authorization; do not claim success from offline tests.

## Verification and delivery

Use an offline deterministic PHP test, observed failing before behavior changes, then passing. Run PHP lint, node --check for touched JavaScript, npm --prefix frontend run build, git diff --check, and local HTTP checks. Do not send Google requests or use the account without explicit browser authorization. Native review is unavailable: the parent ran assess with agent opencode and then STATUS, which returned immutable_review_transport_unsupported with mutation not_started. Do not retry native review, toggle its mode, or create a report. No push requested for this work.

Forecast: approximately 350 authored changed lines, excluding private artifacts; actual handoff 421 changed lines. Delivery strategy exception-ok: the user explicitly selected one coherent delivery after the size forecast exceeded the advisory budget. Work-unit commits remain local on developer; no push or PR is authorized.

## Progress

- Read-only mapping identified discarded pending event IDs and overwritten OAuth callback feedback; G1 fixes both. Parent committed G1 as `1cd2183`.
- G1 completed: ignored local OAuth configuration created in the repository (0600) and Apache deployment (owner-only base permissions plus daemon read-only ACL, no group/other access). Required fields and registered callback were validated without printing credentials. Frontend return is localhost:5174; Vite uses strict port 5174 and session restoration preserves callback feedback.
- Stable Calendar identity is derived from public client identity, professional, and consultation; stored event IDs take precedence. GET checks pending links, PATCH reschedules the same event, and 409 recovers the existing event. Accepted event ID/time is saved even while conference creation is pending or failed; unrelated updates do not clear valid links. Phone events omit conference creation. Removed only the redundant empty token-refresh POST.
- Observed RED: `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` exited 255 with `Offline Calendar transport seam is missing.` before implementation. The same command now passes pending-to-success with one insert, repeated/reordered retries, same-event rescheduling, 409 recovery, explicit conference failure, transport/lost-response recovery, missing link, stored legacy identity, and phone/no-Meet checks. Database persistence is structurally asserted, not an authenticated database integration test.
- GREEN: PHP lint on all seven changed PHP/config/test files; `node --check` on app.js, ConsultationsView.js, and vite.config.js; `npm --prefix frontend run build`; `git diff --check`. Build retains the existing large-chunk warning; no dependency or bundling changes were introduced.
- Deployment: compared all five changed backend distribution files with their repository baseline before copying to exact Apache counterparts; final helper refinement was diff-reviewed before copying. Existing deployment connection/configuration remained untouched. Both Apache and Vite 5174 return JSON/401 without credentials for sesion_actual.php and api/google_status.php. Safe callback `?error=access_denied` returns `http://localhost:5174#consultations?google=error` without Google traffic. Configuration field checks return booleans only; ACL metadata confirms daemon read-only access.
- Independent verification identified one attributable timezone defect: comparing timestamp substrings treated 09:00 UTC as 09:00 Buenos Aires. Follow-up RED: the exact offline command above exited 255 with `Identical clock times with different offsets must reschedule.` before the fix. GREEN: DateTimeImmutable now compares start/end instants using the requested timezone; tests also prove equivalent UTC/Buenos Aires instants do not cause a PATCH. Only the helper was deployed after confirming its Apache counterpart matched G1 commit `1cd2183`.
- G2 completed: README and setup guide describe standard XAMPP extraction/import, Node 22.12+, npm install/run dev, strict localhost:5174, the registered Apache callback, separate account credentials, testing-mode authorization expiry, pending-link checks, and private-distribution/deletion warnings. No local usernames or absolute deployment paths are included in delivery docs.
- Private archive: `../../PASOS-entrega-privada.zip` relative to the project root (Downloads, outside Git and public web roots), with root `proyecto-intefacesweb-develop/`. Explicit Python zipfile allowlist contains 32 maintained backend/frontend/SQL/docs/test files, including ignored local OAuth config exactly once. No node_modules, dist, .env files, Git/odd history, raw Cloud JSON, logs, cookies, sessions, live database exports, or user authorization data are included. All local frontend imports were checked against included files; third-party code is installed from package.json/package-lock.json rather than copied assets.
- Archive inspection passes CRC, expected root, exact allowlist/exclusions, source equality, matching package/lock metadata, PHP syntax, and boolean-only credential-field checks. Archive permission is 0600; PHP/runtime entry permissions are 0644 for standard XAMPP portability without transferring local ACLs. Reassembled after final source/doc changes. Source offline tests, helper lint, frontend build, and git diff --check pass; the existing large-chunk build warning remains.
- Cumulative delivery exceeds the advisory 400 changed-line slice versus `4ec6d8c`; the parent was notified before any further commit. Final additions/deletions are reported in the worker handoff. Full current task document is mirrored to Engram topic `odd/google-meet-delivery/tasks` and read back for verification.
- No commit/push, authenticated API calls, Google requests, database writes, process termination, credential-file changes, or schema changes were performed by this follow-up worker. G3 browser consent, authenticated MySQL persistence, and a real Calendar/Meet meeting remain unverified; no historical orphan events were reconciled.

## Next step

Close the bounded follow-up as a local work-unit commit under the user-approved size exception, then coordinate a human G3 browser test for consent, event creation, pending-link recovery, and same-event rescheduling. Do not mark G3 complete from offline/archive checks.
