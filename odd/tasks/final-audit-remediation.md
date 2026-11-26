# Final audit remediation

## Objective and scope

Fix the user-approved audit findings at d2bb857: consume CAPTCHA approval per attempt; enable assigned-professional notes and owner display; normalize FullCalendar date selection; block privileged placeholder claims and seed promotion; enforce a consistent write-origin/content-type boundary; choose the connected Google identity for professional meeting entry. Preserve existing login, roles, booking rules and data.

## Tasks and route

- [x] R1 — Implement the six source corrections with focused observed RED/GREEN tests and setup guidance. Independent source, regression, build and disposable-MariaDB verification passed.
- [ ] R2 — Partial: independent verification and bounded six-public-PHP local deployment passed. Trusted UID provenance, private ZIP refresh and manual integration checks remain blocked/pending; parent assessment/review/commit remains pending.

## Constraints and acceptance

No new dependencies, production DB writes, account creation, authenticated Google/Firebase calls, token/password export, arbitrary promotion or automatic publication. Source seeds must not grant privilege by an unverified email claim. Trusted privileged Firebase UID provenance is required before a ready-to-login private seed; block that packaging step if unavailable. Preserve already-bound professional access. Do not claim a video/PDF demonstration or real browser integration has been completed.

Run PHP auth/rules/Calendar regressions, a new request-guard regression, the frontend SFC regression, changed PHP lint, frontend build and git diff --check. Use disposable fixtures only. Verify teacher archive contents/current runtime parity and owner-only archive permissions; intentional OAuth application configuration may be included privately, never passwords/account tokens/live DB dumps. Preserve local connection and OAuth settings during deployment.

## Delivery and progress

Strategy single-pr: one coherent remediation. Forecast 350–600 authored changed lines including tests, advisory only; never omit checks to fit. This writer made no commit/push/PR; later review/commit decisions remain user-owned and candidate-scoped.

- Read-only fix mapping complete; existing professional is bound, administrator is pending. Local DB state alone does not prove privileged UID ownership.
- Source/runtime baseline was d2bb857, clean except this parent-created task file. Source contains R1 changes; exactly six approved public PHP files are now deployed locally. Frontend deployment and private ZIP remain unchanged and stale.
- Full task mirror: Engram project `agente-interfaces`, observation 6383, topic `odd/final-audit-remediation/tasks`; local locator `proyectofinal/odd/tasks/final-audit-remediation.md`. Refresh and read back this entire file after progress changes.

## R1 acceptance and implementation

1. CAPTCHA approval is unset immediately after the approved gate, before JSON/Firebase validation. Invalid proof/JSON consumes the attempt; no approval means HTTP 401. Unrelated session fields remain. LoginForm already refreshes failures and was not changed.
2. Phone/video assigned-professional notes are editable, including closed records, and submit only `professional_note`; existing owner detail interpolates notes as escaped Vue text. Administrator/legacy-email status review and separate scheduling/closing remain. Existing assignment authorization and appointment preservation are regression-tested; ConsultationRules was not changed.
3. The actual FullCalendar callback retains `info.dateStr.substring(0, 10)` for all-day and offset-bearing timed strings. Date plus slot stays exactly `YYYY-MM-DDTHH:mm`; Buenos Aires interpretation is explicit. Tests use a tiny constructor/render stub in the existing SFC VM harness, not a real calendar/browser.
4. Only stored role `user` may claim a placeholder. Pending privileged identities fail generic setup-required HTTP 403 before hashing/writes/authentication mutation. Bound professional success and bound UID mismatch HTTP 409 are tested through actual endpoint execution with DB fakes. Both privileged SQL seeds use `ON DUPLICATE KEY UPDATE id = id`; the ordinary user seed is unchanged. Trusted console UID setup is documented; no real UID was read, generated or embedded.
5. One small procedural RequestGuard is shared by session POST, consultation POST/PUT, CAPTCHA POST and logout POST before session startup. Exact allowed origins are `http://localhost`, `http://127.0.0.1` and those hosts on 5174, plus the normalized public `PASOS_FRONTEND_URL` origin. Literal null, host suffixes and other ports fail HTTP 403. Missing Origin remains allowed. JSON session/consultation writes require application/json (charset allowed), otherwise HTTP 400 before authentication; CAPTCHA form encoding/bodyless logout remain. No private OAuth config, forwarded-header trust, wildcard CORS or CSRF-token claim.
6. Meet entry keeps client Firebase email. Professionals look up only their own connected Google email by prepared session-user-ID query, fetching no tokens; missing/invalid email omits authuser. URL encoding, existing query preservation, safe Meet host, ownership and early/closed/expired denial remain. The old redirect harness intercepted an obsolete expression; it now captures the actual Location `$link` and checks Google B versus Firebase A.

## Observed proof

Before corresponding behavior changes, regressions produced these actual failures:

- Request guard: foreign Origin returned CAPTCHA HTTP 401 instead of required 403.
- Firebase endpoint: failed credential proof retained CAPTCHA approval; after consumption was fixed, pending professional returned success instead of setup-required 403.
- SQL assertion: privileged collision clauses were not true no-ops.
- SFC VM: timed date click retained the full offset-bearing string; after date normalization, professional phone/video note PUT still sent status.
- Consultation endpoint: professional redirect used encoded Firebase A instead of connected Google B.

Final foreground checks passed:

```bash
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/request-guard.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/consultation-rules.php
/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php
node --experimental-vm-modules tests/frontend-sfc.mjs
npm --prefix frontend run build
git diff --check
```

PHP lint passed for all nine new/changed PHP source/test files. Node reports its expected experimental VM-modules warning; Vite reports a 631.95 kB minified JS chunk warning, not a build failure. No dependencies/configuration were added. The source writer performed no live account/Firebase/Google/DB/deployment/ZIP action.

Independent verifier evidence (Engram 6386/6387; private workspace `/tmp/opencode/pasos-r1-independent-1r2m2t4R/`) supersedes the earlier source-only SQL limitation: real isolated MariaDB verified three tables and three foreign-key behaviors, two seed reruns, bound identity preservation and privileged email/UID/cross-collision non-promotion. Its private server was stopped. Four PHP suites, Node SFC regression, nine PHP lints, build and diff check passed independently. Parent RequestGuard spot check passed. This was not an import into the deployed database.

## R2 bounded local deployment evidence

- Destination: `/opt/lampp/htdocs/proyecto-intefacesweb-develop`. Preflight found all five existing approved public files byte-equal to `d2bb857`; the new helper was absent. No custom drift was overwritten.
- Exact deployment: `backend/lib/RequestGuard.php` (first), `backend/crear_sesion.php`, `backend/validar_captcha.php`, `backend/cerrar_sesion.php`, `backend/api/consultations.php`, `backend/api/join_meeting.php`. Five overwrites plus one addition; atomic replacement per file, preserved public modes, rollback-on-failure implemented and not needed. All six match working-source bytes; six source plus six destination PHP lints passed. All four mutating consumers resolve the deployed helper.
- Backup and sanitized status proof: `/tmp/opencode/pasos-audit-sync-A4NfeLtW/backup/` contains only the five overwritten public files; `manifest.json` reports `deployed-verified` and all 38 HTTP results. No configuration or secret backup was made.
- Read-only local MariaDB transaction verified `users.password_hash`, `users.phone`, consultation/Google binding columns, all three related tables and an existing bound professional. Before/after table counts and identity-role fingerprint matched internally; no IDs, UIDs, email, hashes, credentials or tokens were reported. Access preservation is supported by unchanged bound identity and verified source logic, not a real sign-in claim.
- Protected connection/OAuth/local environment/settings bytes, modes, owners and extended ACL attributes matched before/after. Source file hashes and git status matched before/after deployment; the only subsequent source-side write is this authorized progress file.
- Apache and Vite 5174 each passed 19 no-cookie HTTP checks: CAPTCHA GET 200 JSON; session JSON POST 401 at CAPTCHA; consultation POST/PUT 401; invalid URL-encoded CAPTCHA 400; unauthorized consultation/videocall/session/join GET 401. Both `http://localhost:5174` and `http://127.0.0.1:5174` reached expected authentication boundaries. Foreign localhost port was blocked 403 for all four mutating consumers; text/plain session/consultation JSON writes were blocked 400 before authentication. No successful CAPTCHA, existing user cookies, Google redirect, account API calls or cookie headers were used/reported.
- No source features, SQL seeds, deployed DB rows, protected configuration, accounts, services or ZIP were changed. No service restart, commit, push or PR. Frontend date/note changes remain source-only under this six-PHP deployment scope; authenticated/browser behavior is not proven.

## Skill resolution

`skill_resolution: paths-injected` — loaded the four user-provided files directly:

- `/home/lpalavecino/Descargas/agente-interfaces/skills/interfaces-programacion-web/SKILL.md`
- `/home/lpalavecino/Descargas/agente-interfaces/skills/php-mysql-profesor/SKILL.md`
- `/home/lpalavecino/Descargas/agente-interfaces/skills/http-rest-profesor/SKILL.md`
- `/home/lpalavecino/.cache/opencode/npm/@dietrichgebert/ponytail@latest/1793617125446/node_modules/@dietrichgebert/ponytail/skills/ponytail/SKILL.md`

Read their required source maps and professor constraints; used cognitive-doc-design for local guide/progress edits. FullCalendar v6 dateClick documentation confirms dateStr differs between all-day and timed cells. This bounded deployment worker spawned no further agents.

## Candidate review and closure

- Final pre-commit native assessment: high risk, 15 paths and 450 changed lines. User explicitly selected Skip this time. Exact provider decline returned action declined and consent declined_this_candidate for the assessed target; no native review record or approval is claimed. Future reviews remain enabled.
- Parent reran the request-guard regression and source/deployed helper comparison successfully. Independent source, HTTP-fixture and isolated MariaDB seed-collision checks passed. Runtime public-code deployment was verified separately; no production identities or credentials were changed.
- R1 is ready for a local work-unit commit. R2 remains partial: trusted privileged UID provenance, final private archive and real browser/Firebase/Google demonstration are not complete. No push or PR is authorized.

## Next step

Parent must complete native assessment/review and decide any candidate-scoped commit. Resolve trusted privileged Firebase UID provenance before producing a ready-to-login private preseed; never substitute a local DB UID as ownership proof. Then refresh the allowlisted owner-only ZIP and address frontend deployment under an explicitly authorized scope. Manual real Firebase/Google/browser checks and course video/PDF demonstration remain pending, not claimed.
