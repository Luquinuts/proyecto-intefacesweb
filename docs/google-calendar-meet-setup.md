# Run the private PASOS classroom delivery

The private ZIP includes application OAuth configuration, not a signed-in Google account. Keep its root folder named `proyecto-intefacesweb-develop` so the registered callback and Vite proxy continue to match.

## Start locally

1. Extract `proyecto-intefacesweb-develop/` under XAMPP's `htdocs`. Start **Apache** and **MySQL** in XAMPP.
2. Open phpMyAdmin and import `database/pasos.sql` from the extracted folder into a fresh classroom database. It creates the `pasos` schema and demo role records, not account passwords or Google authorization. Do not import over a working database without a backup. The optional `database/google_calendar_migration.sql` is only for an older existing PASOS schema.
3. Install Node.js 22.12+ with npm. In the extracted project's terminal:

   ```bash
   cd frontend
   npm install
   npm run dev
   ```

4. Open `http://localhost:5174/`. Keep that hostname and port throughout login. Vite's strict port setting intentionally refuses a busy port instead of selecting another one.

Standard local XAMPP database defaults are configured in `backend/config/conexion.php`. Custom installations can set `PASOS_DB_HOST`, `PASOS_DB_USER`, `PASOS_DB_PASSWORD`, `PASOS_DB_NAME`, and `PASOS_DB_PORT` in Apache's environment. PHP needs mysqli and cURL for the database and Google requests. No manual permission command or elevated shell is expected after a standard XAMPP unzip; custom installations may require administrator assistance.

## Connect and verify Google manually

1. Obtain the classroom Firebase user and professional account credentials separately. The seeded professional record in `database/pasos.sql` must match an account available in the configured Firebase project; login links its placeholder UID. Submit a phone or video consultation as a user, then sign in as the assigned professional.
2. Open the consultation and choose **Conectar Google Calendar**. The Cloud owner must enable Calendar API and Meet REST API and allow the connecting Google account as an OAuth test user while the application is in testing mode.
3. Accept Google's consent screen in the browser. The registered redirect is exactly `http://localhost/proyecto-intefacesweb-develop/backend/api/google_callback.php`, not a Vite URL. The backend then returns to `http://localhost:5174/` and displays connection feedback. The private bundle already includes `backend/config/google.local.php`; do not replace it with the example file. A Git-only checkout requires configuring that ignored local file separately.
4. Schedule a video consultation. Confirm one event appears in the professional's primary Google Calendar and its Meet link appears in PASOS. If the conference is pending, the appointment is saved: use **Comprobar enlace / reagendar** with the same time to check that event. A failed conference is reported without creating another event; inspect it in Calendar rather than repeatedly expecting a new conference.
5. Change the appointment time and confirm the same event moves without duplication. A phone consultation creates a Calendar event without requesting Meet. These are manual acceptance checks, not results already demonstrated by the ZIP.

Authorization in Google OAuth testing mode can expire, including refresh authorization; reconnect when necessary. Wrong callback, denied consent, missing test-user access, unavailable APIs, Firebase account access, or custom database settings can prevent setup. Do not change the callback to work around an occupied frontend port.

## Keep the delivery private

- `backend/config/google.local.php` intentionally contains the application client configuration. It is ignored in Git but deliberately included once in this private ZIP; downloaded Cloud JSON and signed-in user tokens are not included.
- The ZIP itself is owner-only on the preparing machine. Its PHP entries have ordinary read permissions for XAMPP/Apache portability; local ACLs do not travel with ZIP files. Never serve backend source with a static server, Vite preview, or a misconfigured PHP server. Apache must execute PHP rather than expose its source.
- Do not upload the ZIP to Git, a public share, or a public web root. Share only with the intended teacher and delete sensitive archive/extracted copies when no longer needed. Never distribute live database exports containing OAuth tokens, passwords, cookies, or sessions.

## Verification boundary

Offline tests cover pending/success, stable retries, rescheduling with timezone-aware instant comparison, conflicts, failures, and phone events. Run `php -d zend.assertions=1 -d assert.exception=1 tests/google-calendar-meet.php` from the project root. Local syntax/build and unauthenticated JSON checks do not prove browser consent, authenticated MySQL persistence, or a real Google meeting; those remain manual and installation-specific.
