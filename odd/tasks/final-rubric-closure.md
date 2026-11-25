# Final assignment requirement closure

## Objective and authorization

Complete the explicit final-assignment gaps: authentic Firebase identity verification before PHP sessions, verified password hashing in MySQL, and jQuery form submission and table selection. User explicitly authorized PHP receiving the submitted password, verifying it with Firebase and storing only a local hash while keeping Firebase as the sole login authority. No local authentication fallback is permitted.

## Scope and constraints

- Preserve the selected Firebase v10 browser sign-in, CAPTCHA, ID-token, PHP session and navigation sequence. Never hash an ID token or an unchecked password.
- PHP derives user identity from Firebase verification and configured project, not client-supplied email/UID. Fail closed before SQL/session mutation when verification fails.
- Add a nullable password_hash column without changing existing IDs, roles, relationships or user rows. Populate hashes only after successful Firebase password verification.
- Never log or commit passwords, tokens or private keys. HTTPS is required outside localhost. No new dependency, framework or duplicate authentication system.
- Keep the Vue SFC Options API, existing UI, bootstrap/jQuery and REST architecture. Do not alter Google scheduling, deployment URLs or unrelated code.

## Tasks and route

- [x] A1 — Implemented dependency-free, server-configured Firebase REST identity/password proof, hash lifecycle, additive SQL migration file, session rotation and observed RED/GREEN tests. Applying the migration remains gated by A3.
- [x] A2 — Implemented namespaced delegated jQuery login/create/update submission, accessible current-record selection, filtering/cleanup, regression tests and boundary documentation.
- [ ] A3 — Local migration/deployment BLOCKED: XAMPP MySQL socket refuses connections and Apache is unavailable. Baseline deployment comparison passed for both existing endpoints; both new helper/config paths are absent and available. No migration or deployment was performed. Independent verification remains parent pending. No authenticated Google/Firebase traffic is authorized for automated tests.

## Acceptance and checks

Use an optional callable transport for offline Firebase responses. Reject invalid/expired/cross-project/disabled/revoked identities, wrong password or password identity mismatch without DB/session mutation. Retain/re-hash only Firebase-verified passwords; stored hash never authorizes login. Use password_hash/password_verify/password_needs_rehash and prepared mysqli.

Run /opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php, node --experimental-vm-modules tests/frontend-sfc.mjs, PHP lint of changed backend/tests, npm --prefix frontend run build, existing PHP Calendar regression and git diff --check. Observe deterministic RED before implementation, then GREEN. Inspect local schema and preserve row count before/after additive migration; never export live tokens/password data. Manual real Firebase/browser tests remain pending if unavailable.

## Delivery and progress

Actual pre-review implementation is 588 authored changed lines. Strategy exception-ok: the user explicitly chose one coherent delivery after seeing the size, rather than chained branches. Work-unit commits local on developer, no push/PR requested. Native review is user-owned; assess final candidate and follow consent, never toggle automatically.

- Read-only mapping completed. Existing session handler only checks a nonempty token; users has no password hash; table selection is Vue-only.
- User approved verified password receipt/hash while preserving Firebase, and no plaintext storage/logging.
- Server uses Google accounts:lookup as token authentication, then additional configured aud/iss/sub/exp/iat/auth_time and optional validSince checks; independent signInWithPassword must return the same trusted identity. SQL and session-authentication changes follow proof only. No local-hash login fallback.
- XAMPP PHP does not support Argon2id. The documented fallback hashes base64 SHA-384 with bcrypt, preserving long suffixes and whitespace; hashes are reused, replaced on verified password changes, or rehashed when required.

### Verification evidence

- RED: `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php` exited 255 because the new Firebase helper did not exist. GREEN: same command passes mocked identity/password, malformed/expired/future/cross-project/disabled/revoked/network cases, hash lifecycle, actual endpoint rejection before SQL/session mutation, and CAPTCHA POST/type tests.
- RED: `node --experimental-vm-modules tests/frontend-sfc.mjs` exited 1 at `Login needs one delegated submit` (0 versus 1). GREEN: same command passes all existing root/SFC checks plus delegated handlers, one submit, late update form, latest-row selection, keyboard/filter cleanup, failed HTTP responses and CAPTCHA refresh without duplicate authentication.
- GREEN: PHP lint for `backend/lib/FirebaseAuth.php`, `backend/config/firebase.php`, `backend/crear_sesion.php`, `backend/validar_captcha.php` and `tests/firebase-auth.php`; `npm --prefix frontend run build`; `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php`; `git diff --check`.
- Build retains the advisory chunk-size warning; no unrelated bundling change was made.
- A3 baseline: deployed `backend/crear_sesion.php` and `backend/validar_captcha.php` exactly match commit `7702d3f`. New Firebase helper/config counterparts do not exist. Migration inspection could not read baseline row counts: `/opt/lampp/bin/mysql -uroot` reports socket connection refused. Thus column creation, twice-applied stability, preserved IDs/roles/FKs/row counts, and local HTTP behavior are NOT proven. Unauthenticated HTTP GET/POST smoke requests could not connect to Apache. Deployment is intentionally withheld until schema readiness; no live records, credentials, secrets or ZIP were changed.
- Manual real Firebase token/revocation, browser and server connectivity/API-key restriction checks remain unperformed. Offline mocks do not prove integration; existing PHP sessions are not continuously revalidated.
- Independent verification found no substantiated attributable defects. The separate verifier reran both regression runners, all five PHP lint checks, frontend build, unchanged Calendar test and git diff check: every command exited 0. Parent independently reran the Firebase regression and diff check successfully.
- Native assessment classified the candidate high risk. User explicitly selected Skip this time; the exact provider decline returned action declined and consent declined_this_candidate for the assessed target. No native review record or approval is claimed; future reviews remain enabled.
- User explicitly approved one coherent delivery for the 588-line candidate. No push, PR or service startup is authorized by that size choice. Database migration/deployment and live browser checks remain blocked rather than inferred from offline proof.

### Skill resolution

`skill_resolution: paths-injected`: read `skills/interfaces-programacion-web/SKILL.md`, `skills/php-mysql-profesor/SKILL.md`, `skills/http-rest-profesor/SKILL.md`, their required source maps, the Firebase v10/professor constraints references, and the injected cached ponytail `SKILL.md`. Also loaded `cognitive-doc-design` for bounded README/proposal edits. Consulted current official Firebase REST/accounts:lookup, token-verification and session-management documentation before behavior edits. No Google secret configuration was read.

## Next step

After appropriate service-start authorization, inspect counts/identity-role/FK fingerprints, apply only `database/password_hash_migration.sql` twice and verify stability, then repeat baseline divergence checks before deploying only the four changed backend files. Obtain manual Firebase/browser evidence. Do not rerun the seed or recreate the ZIP; the existing deployed backend and private archive do not include these changes yet.
