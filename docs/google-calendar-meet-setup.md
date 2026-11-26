# Run the private PASOS classroom delivery

The private ZIP includes application OAuth configuration, not a signed-in Google account. Keep its root folder named `proyecto-intefacesweb-develop` so the registered callback and Vite proxy continue to match.

## Start locally

1. Extract `proyecto-intefacesweb-develop/` under XAMPP's `htdocs`. Start **Apache** and **MySQL** in XAMPP.
2. Complete the trusted privileged setup below, then import `database/pasos.sql` through phpMyAdmin into a fresh classroom database. It creates the `pasos` schema and role records, not account passwords or Google authorization. Pending privileged records cannot log in. Do not import over a working database. The optional `database/google_calendar_migration.sql` is only for an older existing PASOS schema.
3. Install Node.js 22.12+ with npm. In the extracted project's terminal:

   ```bash
   cd frontend
   npm install
   npm run dev
   ```

4. Open `http://localhost:5174/`. Keep that hostname and port throughout login. Vite's strict port setting intentionally refuses a busy port instead of selecting another one.

Standard local XAMPP database defaults are configured in `backend/config/conexion.php`. Custom installations can set `PASOS_DB_HOST`, `PASOS_DB_USER`, `PASOS_DB_PASSWORD`, `PASOS_DB_NAME`, and `PASOS_DB_PORT` in Apache's environment. PHP needs mysqli and cURL for the database and Google requests. No manual permission command or elevated shell is expected after a standard XAMPP unzip; custom installations may require administrator assistance.

For a custom frontend, set public `PASOS_FRONTEND_URL` in Apache's environment to the real frontend URL, for example `https://class.example.test/app/`. Write requests allow its normalized HTTP(S) origin (scheme/host/port only; default ports normalize), alongside the exact local origins `http://localhost`, `http://127.0.0.1` and those hosts on port 5174. Other ports, deceptive host suffixes and `Origin: null` are rejected. The guard does not read private OAuth configuration or forwarded headers. JSON session/consultation writes need `application/json`; form CAPTCHA and bodyless POST logout need only the origin check. Missing Origin is allowed for non-browser clients, not evidence of CSRF-token protection.

## Trusted privileged account setup

1. The administrator creates/controls the intended account in the configured Firebase Authentication project and copies its UID directly from that project's Authentication console. Do not infer a trusted UID from a submitted email, email verification alone, a local database row or logs.
2. For a **fresh installation**, manually replace the professional and administrator UID placeholders in `database/pasos.sql` with those trusted UIDs before the first import. Never share passwords, tokens or real UIDs in logs or public source. Unresolved privileged placeholders intentionally return setup-required HTTP 403. Ordinary client placeholders can still link at login.
3. For an **existing installation**, preserve already-bound identities. An authorized administrator can bind only the reviewed pending row using a prepared statement such as `UPDATE users SET firebase_uid = ? WHERE id = ? AND firebase_uid = ? AND role = ?`, binding `siss` with the trusted console UID, reviewed row ID, exact pending placeholder and expected privileged role. Validate the reviewed role/placeholder first and require exactly one affected row; stop on a UID collision or unexpected row instead of changing roles or other identities. This is manual setup, not automatic email-based promotion; no such write was performed by the remediation.

Privileged seed collisions execute `id = id`, preserving existing identities, names and roles even on email/UID conflicts. Do not use a seed rerun to repair accounts. The working bound professional requires no mutation. A source build may proceed, but a refreshed ready-to-login private package is pending trusted privileged UID input from the user; this remediation neither generated that package nor proved provenance from live account data.

## Connect and verify Google manually

1. Obtain the classroom Firebase user and professional account credentials separately. Complete trusted UID binding above; login never links a privileged placeholder. Submit a phone or video consultation as a user, then sign in as the assigned professional.
2. Open the consultation and choose **Conectar Google Calendar**. The Cloud owner must enable Calendar API and Meet REST API and allow the connecting Google account as an OAuth test user while the application is in testing mode.
3. Accept Google's consent screen in the browser. The registered redirect is exactly `http://localhost/proyecto-intefacesweb-develop/backend/api/google_callback.php`, not a Vite URL. The backend then returns to `http://localhost:5174/` and displays connection feedback. The private bundle already includes `backend/config/google.local.php`; do not replace it with the example file. A Git-only checkout requires configuring that ignored local file separately.
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

Authorization in Google OAuth testing mode can expire, including refresh authorization; reconnect when necessary. Wrong callback, denied consent, missing test-user access, unavailable APIs, Firebase account access, or custom database settings can prevent setup. Do not change the callback to work around an occupied frontend port.

## Keep the delivery private

- `backend/config/google.local.php` intentionally contains the application client configuration. It is ignored in Git but deliberately included once in this private ZIP; downloaded Cloud JSON and signed-in user tokens are not included.
- The ZIP itself is owner-only on the preparing machine. Its PHP entries have ordinary read permissions for XAMPP/Apache portability; local ACLs do not travel with ZIP files. Never serve backend source with a static server, Vite preview, or a misconfigured PHP server. Apache must execute PHP rather than expose its source.
- Do not upload the ZIP to Git, a public share, or a public web root. Share only with the intended teacher and delete sensitive archive/extracted copies when no longer needed. Never distribute live database exports containing OAuth tokens, passwords, cookies, or sessions.

## Verification boundary

Offline tests cover pending/success, stable retries, rescheduling with timezone-aware instant comparison, conflicts, failures, and phone events. Run `php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` from the project root. Local syntax/build and unauthenticated JSON checks do not prove browser consent, authenticated MySQL persistence, or a real Google meeting; those remain manual and installation-specific.
