# Vue single-file component migration

## Objective

Migrate the six existing PASOS Vue component modules from JavaScript template strings to .vue single-file components, matching the prior Integral 2 Options API style. Existing frontend functionality must remain unchanged.

## Scope and constraints

- Rename app.js to App.vue; convert the two components and three views to .vue.
- Keep main.js, config.js and vite.config.js as JavaScript. Preserve Bootstrap, jQuery, FullCalendar, Firebase v10, PHP session restoration, roles and OAuth hash handling.
- Add only the official compatible Vue Vite plugin and update component imports, lockfile and configuration. No router, UI redesign, backend, schema, deployment-path or credential changes.
- Use standard template/script blocks and Options API, not script setup or Composition API.
- Reference integral2 read-only. Preserve current Vite proxy despite the pre-existing README/deployment path mismatch; report that mismatch separately.

## Delivery strategy

The user explicitly selected one coherent delivery (exception-ok), rather than chained branches. Approximately 1,500–1,700 textual additions/deletions are expected from moving existing templates, plus lockfile changes; this is an advisory estimate, not a reason to omit tests or split artificially. Local work-unit commit on developer; no push or PR authorized.

## Tasks and routing

- [x] S1 — Add an offline SFC/role-restoration regression test, observe RED, migrate all six components and configure Vite, then observe GREEN. Delegated direct: multi-file migration and reading required to prepare writes.
- [x] S2 — Verify compilation/imports, session-role/hash behavior and build, update README and record commit evidence. Delegated direct verification plus one parent command spot check. Browser Firebase/OAuth/jQuery/FullCalendar parity remains explicitly pending if unavailable.

## Acceptance and checks

- Six .vue files compile; old component .js files no longer exist and no active imports refer to them.
- Root App uses Options API with session restoration, callback hash preservation and listener cleanup.
- Existing login sequence, props/emits and component templates are preserved.
- Run node --experimental-vm-modules tests/frontend-sfc.mjs, node --check frontend/main.js, node --check frontend/vite.config.js, npm --prefix frontend run build and git diff --check.
- PHP Calendar test is a supplementary unchanged-backend smoke check when available, not proof of browser behavior.
- Read-only native review status/assessment controls checking; never toggle its user-owned switch. A previously unsupported immutable transport must not be represented as approval.

## Progress

- Read-only mapping completed at 6c90e1f; clean developer baseline. Six components contain 657 existing lines.
- SFC plugin absent; mapping identified @vitejs/plugin-vue 6.0.1 as compatible with installed Vite/Vue and the prior partial.
- User approved a single coherent delivery before implementation.
- Engram mirror synchronized under `odd/vue-sfc-migration/tasks`, project `agente-interfaces`, with full-document read-back and locator `proyectofinal/odd/tasks/vue-sfc-migration.md`.

### S1 implementation evidence

- RED: `node --experimental-vm-modules tests/frontend-sfc.mjs` exited 1 before migration with `AssertionError: Missing SFC: App.vue`.
- GREEN: the same command exited 0 after migration: six SFCs compile; imports, session roles, rendered child props, hash navigation, callback preservation and listener cleanup pass. A test assertion was corrected to accept the existing bare `src="main.js"`; HTML was not changed.
- Converted `app.js` to `App.vue`, both components and all three views to `.vue`; updated all six component import edges. No old component modules remain. Root restoration starts in `beforeMount` before the login child's captcha request; `mounted` registers a named handler and `beforeUnmount` removes the same bound function.
- Baseline parity check against `6c90e1f`: all six templates match exactly; five non-root scripts differ only in component import extensions. Firebase v10, Spanish UI, props/emits, jQuery and FullCalendar hooks remain unchanged.
- Added only exact devDependency `@vitejs/plugin-vue` 6.0.1 and its lockfile helper `@rolldown/pluginutils` 1.0.0-beta.29. Official Vue Context7 documentation confirms ordinary-script SFCs and Vite plugin configuration. Removed the runtime-compiler alias and redundant Vue defines; port 5174, strictPort and proxy are unchanged.
- `node --check frontend/main.js`, `node --check frontend/vite.config.js`, `npm --prefix frontend run build`, and `git diff --check` all exited 0. Vite 7.3.8 built 48 modules; its existing >500 kB chunk warning remains.
- Supplementary `/opt/lampp/bin/php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` exited 0. This unchanged-backend check is not browser evidence.
- Read-only `curl http://localhost:5174/main.js` failed with connection refused (exit 7); no existing Vite service was available. No server was started or terminated, so transformed-module and stale-main checks remain unverified.
- npm reported 12 vulnerabilities (6 moderate, 6 high); no audit fix or unrelated dependency upgrades were performed.
- README records SFC checks, offline-test limits, and the pre-existing proxy `proyecto-intefacesweb` versus private ZIP `proyecto-intefacesweb-develop` mismatch. No backend, SQL, styles, config.js, HTML, credentials, deployment, private ZIP, or XAMPP copy was changed. No commit, push or PR was made by the writer.

### S2 verification evidence

- Parent reran the exact SFC regression command and git diff check; both passed.
- Independent verifier reran all required commands and the supplementary PHP Calendar test; all exited 0. Baseline template parity and non-root script parity were confirmed independently.
- Independent foreground Vite check served main.js, all six SFCs and 19 direct imports with HTTP 200 and compiled render functions. The temporary server was closed in finally; the earlier unavailable served-module check is now resolved.
- Native risk assessment: medium, slice_budget_reached (19 path-based changes, 1,639 authored lines before final tracking updates). User explicitly selected Skip this time; provider returned action declined and consent declined_this_candidate for the exact assessed candidate. No native review record or approval is claimed; the user-owned switch remains enabled for future candidates.

### Remaining verification

- Local work-unit commit identity will be recorded after committing this verified implementation; no push or PR is authorized.
- Browser Firebase login, professional/administrator refresh, Google OAuth return, jQuery submit/filter, FullCalendar rendering and actual Google Meet behavior require manual verification in a configured installation. No browser E2E is claimed.

## Next step

Record local commit evidence and hand off the verified SFC migration with the explicit browser/deployment limitations above.
