# PASOS repository delivery

Download [PASOS-entrega.zip](PASOS-entrega.zip). Both delivery variants have
exactly three root items: `frontend/`, `backend/`, and `pasos.sql`. Setup guidance
is inside `frontend/README.md`; tests, historical documents, migrations, build
output and dependencies are not packaged. Maintained runtime files are unchanged.

## Safe local setup

1. Extract outside Apache's public document root, for example into a private
   folder in your personal Downloads. **Never put the private ZIP or `pasos.sql`
   inside `htdocs`, Git, or another public web directory.** Private SQL contains
   Google account authorization, including access/refresh tokens, not just schema.
2. Start Apache and MySQL. In phpMyAdmin, import root `pasos.sql` directly from
   that private folder into a fresh demo setup. Never import over a working database.
3. Create `C:\xampp\htdocs\proyecto-intefacesweb-develop` (Linux XAMPP:
   `/opt/lampp/htdocs/proyecto-intefacesweb-develop`) and copy **only `backend/`**
   into its `backend/` subdirectory. The private version includes its private
   `google.local.php`; the public version requires separately provided OAuth
   configuration. Do not copy either ZIP, SQL, or `frontend/` into this mapping.
4. Keep `frontend/` outside `htdocs`. With Node.js 22.12+, run `npm install` and
   `npm run dev` from its folder; open `http://localhost:5174/`. Apache must keep
   serving the backend on localhost port 80, separately from Vite.

The unchanged Vite proxy and OAuth callback require Apache to serve
`http://localhost/proyecto-intefacesweb-develop/backend/`. A portable PHP launcher
or callback change is not included; extracting outside that mapping is not enough.

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
