# Portable development startup

## Objective and authorized scope

Start Vite and PHP together with `npm run dev`, from a folder outside Apache htdocs. Preserve PHP, Firebase v10, Vue Options API, existing sessions and REST behavior. MySQL remains a separately started prerequisite. User approved this implementation and the localhost OAuth callback adjustment; Google Cloud registration remains a manual owner action.

## Plan and delivery

- [x] P1 — Implement the minimal owned-process launcher and loopback proxy, update callback defaults/private redirect only, test outside htdocs, and refresh both delivery guides/archives without changing SQL/grants.

Route: delegated direct with one writer and independent technical verification; native review is unavailable in this runtime. Original forecast: approximately 250–400 authored changed lines, excluding archives. Delivery strategy: exception-ok, one coherent local work-unit commit on developer over baseline 0ab498d. The user explicitly chose "Una entrega (Recommended)" to approve the >400-line exception: lifecycle behavior, regression tests, safety guidance and delivery payload must stay together. No PR or push is authorized.

## Constraints and acceptance

- No new dependencies, frameworks, Node backend, database changes or source code comments. Use existing Node/Vite with PHP CLI; never start/stop MySQL or alter Apache deployment.
- PHP must serve only backend, on loopback; Vite stays strict localhost port 5174. Preserve item/query routing, cookie path and origin enforcement.
- Discover PHP via explicit PHP_BINARY, PATH, then normal Windows/Linux XAMPP locations. Fail clearly on invalid explicit override or occupied ports. Clean up only owned child processes on startup failure, interruption and normal exit; never kill listeners by port/name.
- Browser callback: http://localhost:5174/backend/api/google_callback.php. Update only the redirect in ignored local configuration/private package; preserve client ID/secret and all other private values. Never perform remote Google/Firebase calls or refresh tokens.
- Preserve both archive SQL payloads byte-for-byte, especially private account authorization. Keep exact frontend/backend/pasos.sql roots; never put private SQL/ZIP under a webroot, in Git, logs or loose exports.
- Default test-first policy applies: observe a failing deterministic launcher test before implementation, then green/refactor. Existing package tests, PHP syntax/assertions, Vue checks/build and archive secret separation must pass.

## Checks

`node tests/portable_dev.test.mjs`; existing four PHP assertion suites; `node --experimental-vm-modules tests/frontend-sfc.mjs`; `npm --prefix frontend run build`; `git diff --check`. Test launcher from unrelated cwd/path containing spaces, PHP/Vite occupied ports, shutdown and PHP failure, CAPTCHA/session JSON with cookie, unauthenticated REST item dispatch. No live database mutation or OAuth callback HTTP request in smoke tests.

## Progress and next step

P1 observed acceptance checks and independent functional verification are complete, including the bounded npm-wrapper contract correction and parent rerun. Local commit locator: `git log -1 -- odd/tasks/portable-dev-startup.md`; its actual hash is recorded in the full Engram task mirror and a separate recovery observation after commit, avoiding a second tracking-only commit. No push is authorized. Rollback boundary: the listed launcher/package/proxy/callback defaults, startup guides, focused test and archive refresh; no application, identity, origin or database rules changed.

### Test-first and lifecycle evidence

- RED: `node tests/portable_dev.test.mjs` failed with ENOENT for missing `frontend/dev.mjs`, then the focused command assertion failed (`vite` versus `node dev.mjs`) before implementation.
- GREEN: the final runner passes PHP fallback/PATH discovery, invalid override, bounded probe timeout, spawn error, occupied PHP/Vite sentinels, unrelated cwd/project paths containing spaces, prefix boundary/query/item routing, CAPTCHA JSON/cookie reuse, three unauthenticated JSON 401 routes, and private-root 404 checks.
- Shutdown checks pass SIGINT/SIGTERM, interruption during PHP readiness and Vite initialization, readiness timeout, unexpected owned PHP exit, and bounded Vite cleanup. Fake PHP PIDs are confirmed exited; both owned ports can be rebound after each case. Cookies are never printed.
- Regression discovery: installed Vite 7.3.8 installs its own SIGTERM/stdin-end process-exit handlers. A local configureServer hook removes only newly installed handlers so the launcher owns shutdown. Repeated testing also observed an intermittent dependency-optimizer cancellation hang in `vite.close()` under Node 26.4.0. Cleanup initiates Vite close, confirms PHP exit with TERM/KILL escalation, then bounds pending Vite cleanup to three seconds before exiting with the selected status. A deterministic hanging-close fixture proves port release and exit. Earlier runner timeouts/failures preceded these fixes; they are not counted as passes.

### Observed checks

- [x] `node tests/portable_dev.test.mjs` — PASS all focused cases above.
- [x] `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/request-guard.php` — PASS.
- [x] `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/consultation-rules.php` — PASS, offline fake only.
- [x] `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` — PASS, mocked transport.
- [x] `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/firebase-auth.php` — PASS, mocked transport.
- [x] `node --experimental-vm-modules tests/frontend-sfc.mjs` — PASS; expected experimental warning.
- [x] `npm --prefix frontend run build` — PASS; existing >500 kB chunk warning retained, no unrelated splitting.
- [x] `/opt/lampp/bin/php -l` on `backend/config/google.php`, `google.local.php.example`, and ignored `google.local.php` — no syntax errors; no configuration values printed.
- [x] `git diff --check` — PASS.
- [x] Both refreshed ZIPs — CRC/unique entries/exact three roots/current runtime parity, ordinary 0644 entries, public 0644/private 0600 archive modes, separate original SQL byte identity and private client/grant/expiry preservation. Public payload and changed/staged text contain no actual private client credentials or token bytes/hex encodings. Source ignored config and private packaged config differ only in the existing redirect literal.
- [x] Independent functional verification and user-approved exception-ok single local work-unit delivery. Parent reran `node tests/portable_dev.test.mjs` after correction: all cases PASS, including actual npm process-group Ctrl+C; native Windows explicitly SKIP. Parent `git diff --check`: PASS.
- [ ] Native Windows execution, real browser/Firebase/Google integration, and owner registration of `http://localhost:5174/backend/api/google_callback.php` in the existing OAuth client. These remain unverified; the preserved grant may need renewal from original consent, not export time.

### Sources, packaging and constraints

Course skills/source maps were read for the existing PHP/Vue/Firebase v10 boundary, plus ponytail and work-unit-commits. Official PHP CLI-server manual, Node child_process documentation and Context7 Vite 7 JavaScript API/proxy documentation were consulted; installed Vite source verified actual lifecycle behavior. No Node backend, dependency, authored source comment or application feature was added.

Authorized archive verifier/backups: `/tmp/opencode/pasos-portable-AyKSQtoP/package.py` and its two original ZIP backups (directory 0700, backups 0600). SQL/grants are read only from the authorized existing archives in memory, never exported loosely or regenerated. Only local owned loopback servers were used; no Apache/DB start/stop/configuration, database connections/writes, Google/Firebase HTTP, token refresh or Cloud changes were performed. The SQL and private ZIP remain outside PHP's backend-only document root. RequestGuard remains unchanged.

Mirror: Engram observation 6445, topic `odd/portable-dev-startup/tasks`, project `agente-interfaces`; full task plus locator is maintained for parent handoff. Repository text diff: 525 additions + 38 deletions = 563 changed lines, including this task (archives excluded). The user approved exception-ok single coherent local delivery; tests and code were not compressed to meet the advisory budget. Native unavailability does not constitute native approval or a mode change.

### Bounded correction after independent functional verification

Parent-reported independent checks passed on Node 26.4/22.15: portable runner, four PHP suites, SFC/build/diff checks, real npm startup outside cwd in a spaced path, session/CAPTCHA/item-query/origin checks, static SQL denial and archive preservation/separation. The verifier found one medium contract gap: SIGTERM sent only to npm's wrapper PID left descendants, while terminal process-group Ctrl+C worked. Existing direct-Node tests did not cover the wrapper.

Resolution: startup guides now require Ctrl+C in npm's terminal (process-group signal), and automation must invoke `node /absolute/path/to/frontend/dev.mjs` directly and signal that retained launcher PID. Killing only npm's PID does not guarantee cleanup. A bounded real-npm POSIX regression starts the redacted owned fixture from unrelated cwd/spaced paths, signals only its own process group, awaits wrapper/descendant stdio closure and verifies both ports reusable; finally cleanup targets only that newly owned group. Native Windows signaling is explicitly skipped, never simulated by unsupported negative PIDs. No runtime launcher, credential or origin-rule change was made.

Correction checks: `node tests/portable_dev.test.mjs` — PASS including real npm group Ctrl+C; `git diff --check` — PASS; `python3 /tmp/opencode/pasos-portable-rdCbyIVR/docs-only.py` — PASS. Tests remain outside archives. Only packaged `frontend/README.md` payloads changed; SQL, private configuration and every other entry are byte-identical to owner-only pre-correction ZIP backups in that 0700 directory (backups 0600). CRC, entry uniqueness/exact roots, entry/archive modes and public/changed-text secret absence pass; ignored source private config is unchanged. Earlier original SQL/redirect-only preservation remains intact.

Native correction preflight was unavailable/refused before mutation per parent: no native correction actor exists. This authorized ordinary local delivery does not claim native approval; no native retry or mode change is performed. Parent recheck passed and the user approved the single local commit exception. No push, Cloud registration or remote authentication test is authorized or performed.
