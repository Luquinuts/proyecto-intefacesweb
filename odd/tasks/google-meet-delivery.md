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
- [ ] G2 — Write minimal delivery instructions and create/inspect a private ZIP with an explicit file allowlist. Delegated direct: packaging needs secret-aware inspection and a bounded command worker. Acceptance: correct archive root, installable package metadata, no tokens/history/dependencies, intentional OAuth config present.
- [ ] G3 — User completes Google consent and verifies one real meeting. Pending external/browser authorization; do not claim success from offline tests.

## Verification and delivery

Use an offline deterministic PHP test, observed failing before behavior changes, then passing. Run PHP lint, node --check for touched JavaScript, npm --prefix frontend run build, git diff --check, and local HTTP checks. Do not send Google requests or use the account without explicit browser authorization. Native review mode is on; assess each committed work unit and honor returned transitions. No push requested for this work.

Forecast: approximately 350 authored changed lines, excluding private artifacts; delivery strategy ask-on-risk. Larger coherent work is allowed, but resolve the delivery strategy before a commit exceeding the approximate 400-line slice budget. Work-unit commits remain local on developer.

## Progress

- Read-only mapping completed. Current code discards a pending event ID and overwrites the OAuth callback hash.
- G1 completed: ignored local OAuth configuration created in the repository (0600) and Apache deployment (owner-only base permissions plus daemon read-only ACL, no group/other access). Required fields and registered callback were validated without printing credentials. Frontend return is localhost:5174; Vite uses strict port 5174 and session restoration preserves callback feedback.
- Stable Calendar identity is derived from public client identity, professional, and consultation; stored event IDs take precedence. GET checks pending links, PATCH reschedules the same event, and 409 recovers the existing event. Accepted event ID/time is saved even while conference creation is pending or failed; unrelated updates do not clear valid links. Phone events omit conference creation. Removed only the redundant empty token-refresh POST.
- Observed RED: `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` exited 255 with `Offline Calendar transport seam is missing.` before implementation. The same command now passes pending-to-success with one insert, repeated/reordered retries, same-event rescheduling, 409 recovery, explicit conference failure, transport/lost-response recovery, missing link, stored legacy identity, and phone/no-Meet checks. Database persistence is structurally asserted, not an authenticated database integration test.
- GREEN: PHP lint on all seven changed PHP/config/test files; `node --check` on app.js, ConsultationsView.js, and vite.config.js; `npm --prefix frontend run build`; `git diff --check`. Build retains the existing large-chunk warning; no dependency or bundling changes were introduced.
- Deployment: compared all five changed backend distribution files with their repository baseline before copying to exact Apache counterparts; final helper refinement was diff-reviewed before copying. Existing deployment connection/configuration remained untouched. Both Apache and Vite 5174 return JSON/401 without credentials for sesion_actual.php and api/google_status.php. Safe callback `?error=access_denied` returns `http://localhost:5174#consultations?google=error` without Google traffic. Configuration field checks return booleans only; ACL metadata confirms daemon read-only access.
- Engram implementation discovery saved. No commit, push, archive, authenticated API calls, Google requests, database writes, process termination, or schema changes performed by G1. G2 archive remains unprepared; G3 browser consent and a real Calendar/Meet meeting remain pending.

## Next step

Continue with G2 private packaging. Preserve G3 as pending until a human browser test verifies consent, event creation, pending-link recovery, and rescheduling against Google.
