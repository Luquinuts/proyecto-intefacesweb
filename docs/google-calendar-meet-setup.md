# Run the private PASOS classroom delivery

The private ZIP includes application OAuth configuration and the explicitly authorized account grant in root `pasos.sql`. Local token presence does not prove remote authorization. Its roots are `frontend/`, `backend/`, and `pasos.sql`; no fixed parent-folder name is required.

## Start locally

1. Extract into a private folder **outside htdocs and every public webroot**. Keep MySQL running separately. Apache and copying the backend into htdocs are not required.
2. Import root `pasos.sql` into a fresh classroom database using your database administration tool. Never import over a working database. The packaged professional identity is bound; the administrator remains blocked pending a trusted UID. A Git checkout uses `database/pasos.sql` and the trusted setup below instead. The public ZIP has no Google grant.
3. Install Node.js 22.12+ with npm and PHP CLI with mysqli/cURL. In the extracted project's terminal:

   ```bash
   cd frontend
   npm install
   npm run dev
   ```

4. Open exactly `http://localhost:5174/`. The command runs Vite and PHP on private loopback port 8000, serving only `backend/`; both ports must be free. Use Ctrl+C in the terminal running npm to signal its process group and stop both owned servers, not MySQL. Killing only npm's PID does not guarantee descendant cleanup. For automation, run `node /absolute/path/to/frontend/dev.mjs` directly and signal that retained launcher PID with SIGINT/SIGTERM. Native Windows terminal shutdown remains unverified.

PHP discovery checks explicit `PHP_BINARY`, PATH `php`, then Windows `C:\xampp\php\php.exe` or Linux `/opt/lampp/bin/php`. Set the executable path before starting npm if necessary: POSIX `PHP_BINARY='/path with spaces/php' npm run dev`; PowerShell `$env:PHP_BINARY='C:\xampp\php\php.exe'; npm run dev`. An invalid explicit value is an error. Windows execution still requires manual verification.

Standard XAMPP database defaults are configured in `backend/config/conexion.php`. Custom installations can set `PASOS_DB_HOST`, `PASOS_DB_USER`, `PASOS_DB_PASSWORD`, `PASOS_DB_NAME`, and `PASOS_DB_PORT` in the launcher's environment. No elevated shell is expected; never expose root SQL or ZIP files through either server.

For a custom frontend, set public `PASOS_FRONTEND_URL` in the PHP process environment to the real frontend URL, for example `https://class.example.test/app/`. Write requests allow its normalized HTTP(S) origin (scheme/host/port only; default ports normalize), alongside the exact local origins `http://localhost`, `http://127.0.0.1` and those hosts on port 5174. Other ports, deceptive host suffixes and `Origin: null` are rejected. The guard does not read private OAuth configuration or forwarded headers. JSON session/consultation writes need `application/json`; form CAPTCHA and bodyless POST logout need only the origin check. Missing Origin is allowed for non-browser clients, not evidence of CSRF-token protection.

## Trusted privileged account setup

1. The administrator creates/controls the intended account in the configured Firebase Authentication project and copies its UID directly from that project's Authentication console. Do not infer a trusted UID from a submitted email, email verification alone, a local database row or logs.
2. For a **fresh Git-source installation**, manually replace the professional and administrator UID placeholders in `database/pasos.sql` with those trusted UIDs before the first import. Never share passwords, tokens or real UIDs in logs or public source. Unresolved privileged placeholders intentionally return setup-required HTTP 403. Ordinary client placeholders can still link at login. The packaged root SQL preserves the already confirmed professional binding; do not replace it or weaken role rules.
3. For an **existing installation**, preserve already-bound identities. An authorized administrator can bind only the reviewed pending row using a prepared statement such as `UPDATE users SET firebase_uid = ? WHERE id = ? AND firebase_uid = ? AND role = ?`, binding `siss` with the trusted console UID, reviewed row ID, exact pending placeholder and expected privileged role. Validate the reviewed role/placeholder first and require exactly one affected row; stop on a UID collision or unexpected row instead of changing roles or other identities. This is manual setup, not automatic email-based promotion; no such write was performed by the remediation.

Privileged seed collisions execute `id = id`, preserving existing identities, names and roles even on email/UID conflicts. Do not use a seed rerun to repair accounts. The working bound professional requires no mutation. The administrator still needs trusted manual setup; packaged SQL is preserved, not regenerated from a database.

## Connect and verify Google manually

1. Obtain the classroom Firebase user and professional account credentials separately. Complete trusted UID binding above; login never links a privileged placeholder. Submit a phone or video consultation as a user, then sign in as the assigned professional.
2. Open the consultation and choose **Conectar Google Calendar**. The Cloud owner must enable Calendar API and Meet REST API and allow the connecting Google account as an OAuth test user while the application is in testing mode.
3. **Before consent**, the Cloud owner must add exactly `http://localhost:5174/backend/api/google_callback.php` to the existing OAuth client's authorized redirect URIs. Local defaults/private configuration now use that URL, but no Cloud registration has been performed or verified. Accept consent only after registration. Vite forwards the callback to PHP, which returns to `http://localhost:5174/`. The private bundle includes `backend/config/google.local.php`; do not replace it with the example. A public ZIP/Git checkout requires private application configuration separately.
4. Schedule a video consultation. Confirm one event appears in the professional's primary Google Calendar and its Meet link appears in PASOS. If the conference is pending, the appointment is saved: use **Comprobar enlace / reagendar** with the same time to check that event. A failed conference is reported without creating another event; inspect it in Calendar rather than repeatedly expecting a new conference.
5. Change the appointment time and confirm the same event moves without duplication. A phone consultation creates a Calendar event without requesting Meet. These are manual acceptance checks, not results already demonstrated by the ZIP.

## Human browser checklist — pending

- [ ] Retry a failed login with a fresh CAPTCHA; verify a consumed approval cannot authorize another attempt.
- [ ] Verify a pending privileged account is blocked and an existing trusted bound professional still logs in. Never record credentials or UIDs in screenshots/logs.
- [ ] Save phone/video notes as the assigned professional, including a closed consultation; check the appointment/status are unchanged and the owner sees literal text, not interpreted HTML. Check administrator/legacy-email review separately.
- [ ] Select dates in month and timed week/day views, then a slot. Check the submitted date is `YYYY-MM-DDTHH:mm` in Buenos Aires, including from a differently configured browser timezone.
- [ ] With Firebase account A and connected Google account B, verify professional Meet entry hints B, client entry hints the client, and missing binding leaves Google account choice unhindered. Check early, expired, closed and unrelated-user entry denials.
- [ ] Check allowed local/configured deployment origins and JSON requests, then rejection of foreign origins/wrong JSON types; confirm form CAPTCHA and bodyless logout still work.

No real-browser integration, invitation delivery, Google admission, video or PDF demonstration is claimed by the offline checks. Complete the required course demonstration separately.

Authorization in Google OAuth testing mode can expire, including refresh authorization; the seven-day lifetime runs from original consent, not export or this startup update. The preserved private grant may need renewal. No refresh or remote validity check was performed. Wrong callback, denied consent, missing test-user access, unavailable APIs, Firebase account access, or custom database settings can prevent setup. Do not change the callback to work around an occupied frontend port.

## Keep the delivery private

- `backend/config/google.local.php` contains application client configuration and is ignored in Git but included once in the private ZIP. Root private SQL also contains the specifically authorized account grant; neither file belongs in public source.
- The private ZIP is owner-only on the preparing machine. Its PHP entries have ordinary read permissions for portability; local ACLs do not travel with ZIP files. Never serve backend source with a static server, Vite preview, or a misconfigured PHP server. The launcher restricts PHP's document root to `backend/`.
- Do not upload the ZIP to Git, a public share, or a public web root. Share only with the intended teacher and delete sensitive archive/extracted copies when no longer needed. Never distribute live database exports containing OAuth tokens, passwords, cookies, or sessions.

## Verification boundary

Offline tests cover pending/success, stable retries, rescheduling with timezone-aware instant comparison, conflicts, failures, and phone events. Run `php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` from the project root. Local syntax/build and unauthenticated JSON checks do not prove browser consent, authenticated MySQL persistence, or a real Google meeting; those remain manual and installation-specific.
