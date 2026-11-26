# PASOS repository delivery

Download [PASOS-entrega.zip](PASOS-entrega.zip) and extract its
`proyecto-intefacesweb-develop/` folder under XAMPP's `htdocs`. Follow the
**Repository export notice** at the beginning of the extracted `README.md`.

Start Apache and MySQL, import `database/pasos.sql` into a fresh installation,
then run `npm install` and `npm run dev` inside `frontend/` (Node.js 22.12+).
Open `http://localhost:5174/`. Never import the seed over a working database.

The ZIP includes the public Firebase configuration, source connection defaults,
runtime code, SQL, setup reference and offline tests. It contains **no OAuth
credentials**. The original private delivery remains outside Git and is not
committed. Account passwords are supplied separately.

The core application can use the included Firebase project configuration with
valid accounts and the local database. Google Calendar/Meet additionally needs
private OAuth configuration: copy `backend/config/google.local.php.example` to
`backend/config/google.local.php` and obtain the real values privately. **Never
commit that local file or share credentials in Git.**

The supplied lawyer UID is preconfigured only for the provided Firebase project;
optional administrator setup remains manual and blocked until trusted binding.
The archive notice overrides historical private-package and UID-setup statements
in the preserved source documentation. Offline checks do not establish a real
Google/browser demonstration or a tested clean-machine installation.
