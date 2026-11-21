# Vue/Vite frontend migration

## Objective

Make the PASOS frontend runnable with `npm run dev` and align it with the Vue, Bootstrap, JavaScript, Firebase, and component practices used in the course partial, while preserving the existing PHP backend and Google Calendar/Meet workflow.

## Confirmed constraints

- Keep Vue Options API components and the existing page-oriented structure.
- Use Bootstrap through npm imports in the frontend entry point.
- Preserve Firebase v10 email/password authentication and its first-partial sequence.
- Keep jQuery only for the course-required form/table interactions.
- Do not add a second authentication system or unrelated frontend features.
- The PHP backend remains served by XAMPP; Vite serves the frontend during development.

## Tasks

- [x] T1 — Add the npm/Vite frontend configuration and development scripts.
- [x] T2 — Migrate Vue, Bootstrap, jQuery, Firebase v10, and FullCalendar loading to the npm frontend without changing user behavior.
- [x] T3 — Configure the frontend-to-XAMPP backend base URL and preserve Google OAuth callback behavior.
- [x] T4 — Verify `npm run build`, `npm run dev` startup, JavaScript syntax, and the existing Firebase/REST markers.

## Authorized scope

- `frontend/**`
- `odd/tasks/frontend-vite-migration.md`
- `.gitignore`

## Acceptance criteria

- `frontend/package.json` provides `dev`, `build`, and `preview` scripts.
- `npm run dev` starts Vite and serves the Vue application.
- Bootstrap is imported from npm in the frontend entry point and existing components continue using Bootstrap classes.
- The app still contains the existing views and components, hash navigation, Firebase v10 email/password flow, jQuery interactions, and FullCalendar scheduling UI.
- API calls work against the XAMPP PHP backend through the documented `/backend` Vite proxy, with `VITE_API_BASE_URL` available for full-URL deployments.
- The default proxy targets `http://localhost/proyecto-intefacesweb-develop` and preserves the `/backend` path.
- Google OAuth navigation uses the proxy and Vite callbacks use `PASOS_FRONTEND_URL=http://127.0.0.1:5173/`.
- No credentials or local OAuth secrets are committed.

## Verification

- Run `npm install` and `npm run build` in `frontend/`.
- Start `npm run dev` once and verify the dev-server URL responds, then stop it.
- Search for required Vue views/components, Firebase calls, jQuery usage, Bootstrap import, and API base configuration.
- Record any browser/XAMPP-only checks that cannot run in this environment.

## Route

- Delegated direct writer: the migration spans package configuration, entrypoint, multiple frontend modules, and runtime configuration.
- Parent verification: inspect the diff, run the build/start checks, and commit the work unit.

## Progress

- Task document created before source writes.
- T1–T4 completed by the delegated writer and the parent proxy correction.
- Verification observed: `npm install` completed; `npm run build` passed; Vite served `http://127.0.0.1:5173/` with HTTP 200; frontend JavaScript syntax and static migration checks passed.
- Same-origin correction: Vite now proxies `/backend` to the XAMPP project so PHP session cookies do not require an unimplemented CORS boundary.
- Runtime limitation: browser/XAMPP, Firebase, and Google OAuth flows were not exercised; npm reported 12 audit vulnerabilities and the build reported a large bundle warning.
- Generated `frontend/dist/` is ignored and was removed from the working tree.
- Follow-up correction: captcha loading now handles non-JSON HTTP errors, PHP returns a JSON error envelope, and the unavailable external font is no longer requested.
- Work-unit commit: `e2413d4` (`developer: feat(frontend): run PASOS with Vite`), followed by the proxy correction pending commit.
- Next step: push the migration commits after user confirmation.

## Follow-up: PASOS 500 schema/API correction

- [x] Add the consultation scheduling fields and professional foreign key to the base schema and make the Google Calendar migration idempotent for existing databases.
- [x] Handle non-OK or non-JSON consultation and videollamada responses without throwing `Unexpected end of JSON input`.

### Authorized scope

`database/pasos.sql`, `database/google_calendar_migration.sql`, `frontend/views/ConsultationsView.js`, `frontend/views/VideoCallView.js`, and this task document only.

### Verification notes

- The runtime mismatch was verified against the local `pasos.consultations` table: `google_event_id` existed, while `professional_id`, `scheduled_at`, and `meeting_link` were absent.
- No deterministic RED test exists for this runtime schema mismatch; syntax checks, the frontend build, and local MariaDB migration verification are the applicable checks.
- Work-unit commit: `ea7f067` (`developer: fix(api): repair consultation schema errors`).
