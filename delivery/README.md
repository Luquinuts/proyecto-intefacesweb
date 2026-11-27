# PASOS repository delivery

Download [PASOS-entrega.zip](PASOS-entrega.zip). Both delivery variants have
exactly three root items: `frontend/`, `backend/`, and `pasos.sql`. Setup guidance
is inside `frontend/README.md`; tests, historical documents, migrations, build
output and dependencies are not packaged. Both archives include the portable launcher.

## Safe local setup

1. Extract outside Apache's public document root, for example into a private
   folder in your personal Downloads. **Never put the private ZIP or `pasos.sql`
   inside `htdocs`, Git, or another public web directory.** Private SQL contains
   Google account authorization, including access/refresh tokens, not just schema.
2. Keep MySQL running separately. Import root `pasos.sql` directly from that
   private folder into a fresh demo setup. Never import over a working database.
3. Install Node.js 22.12+ and PHP CLI with mysqli/cURL. The private version includes
   `google.local.php`; the public version requires privately supplied OAuth config.
4. Run `npm install` then `npm run dev` inside `frontend/`. Open exactly
   `http://localhost:5174/`. Vite proxies `/backend` to owned PHP on `127.0.0.1:8000`,
   serving only `backend/`. Apache/htdocs mapping is unnecessary. Both ports are
   strict; **Ctrl+C in the terminal running npm** signals its process group and
   stops both owned servers without touching MySQL.

For automation, start `node /absolute/path/to/frontend/dev.mjs` directly and
send SIGINT/SIGTERM to the retained launcher PID. Killing only npm's wrapper PID
does not guarantee descendant cleanup. POSIX process-group Ctrl+C is tested;
native Windows terminal shutdown remains a manual check.

PHP discovery uses `PHP_BINARY`, PATH `php`, then Windows `C:\xampp\php\php.exe`
or Linux `/opt/lampp/bin/php`. An invalid explicit override fails. POSIX example:
`PHP_BINARY='/path with spaces/php' npm run dev`; PowerShell:
`$env:PHP_BINARY='C:\xampp\php\php.exe'; npm run dev`.

Before real consent, the OAuth client owner must manually register exactly
`http://localhost:5174/backend/api/google_callback.php` on the existing Cloud
client. Local configuration changes do not perform or prove that registration.

## Public and private variants

- **Public:** `delivery/PASOS-entrega.zip` is the shareable repository artifact.
  It includes the public Firebase configuration, default connection settings,
  OAuth example configuration, complete schema and three demo identity seeds.
  It contains no private OAuth configuration or grant; the token table starts
  empty. Obtain OAuth application values privately and connect the lawyer's
  Google account before demonstrating Calendar/Meet.
- **Private:** `/home/lpalavecino/Descargas/PASOS-entrega-privada.zip` stays outside
  Git. It intentionally includes the existing private OAuth configuration and
  the single explicitly authorized lawyer grant in its root `pasos.sql`. No
  other live users, passwords, phones or consultations are exported. **Never
  commit or publicly share that ZIP, its SQL, or `google.local.php`.**

Both seeds bind the lawyer only to the confirmed Firebase identity. The ordinary
client placeholder binds after verified Firebase login; the administrator stays
blocked until a trusted administrator UID is supplied manually. Account passwords
are provided separately. The client Gmail receives invitations and does not need
to authorize the lawyer's Calendar OAuth application.

Private export preserves the grant timestamps; it does not restart Google's
seven-day Testing lifetime from original consent. Local token presence and dates
do not prove remote validity, remaining lifetime, or availability tomorrow. The
copied access token was locally expired at export and will need a refresh during
authorized online use; a revoked or expired grant may require the lawyer to
reconnect Google. No refresh was performed for this delivery. The grant table does not store its issuing
client ID, so remote OAuth-client compatibility remains unverified. No Google or
Firebase verification/refresh, Windows/browser demonstration, or clean-machine
installation is established by the local packaging checks.
