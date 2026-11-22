# PASOS — Interfaces y Programación Web

PASOS is a proposed academic web project for a first-entry legal orientation service. It helps a person describe a situation, request human review, and understand a reasonable next step. It is not an automated legal-advice system, a chatbot, or a promise of legal representation.

## Repository purpose

The repository will contain the course project proposal and, after scope confirmation, a basic page-oriented front end and PHP/MySQL back end. The implementation must remain feasible to explain and reproduce by hand.

## Academic stack

- Front end: Vue.js, HTML5, CSS, Bootstrap, and JavaScript.
- Back end: PHP, MySQL, Firebase Authentication, REST, jQuery, HTML5, CSS, and Bootstrap.
- Database access: PHP `mysqli` and explicit SQL queries.

## Repository planning documents (not included in the private runtime ZIP)

- [`docs/Proyecto_PASOS.md`](docs/Proyecto_PASOS.md): strategic product concept and service boundaries.
- [`docs/propuesta.md`](docs/propuesta.md): proposed academic scope, user flow, data model, endpoints, and evidence plan.
- [`odd/tasks/pasos-project-proposal.md`](odd/tasks/pasos-project-proposal.md): documentation tasks and acceptance criteria.

## Review and verification path

1. Implement the complete Front and Backend scope required for a group submission.
2. Use the same Firebase v10 email/password and PHP-session flow documented in the first partial.
3. Review [`docs/propuesta.md`](docs/propuesta.md) against `docs/Practico Final Interfaces y Prog Web.pdf`.
4. Implement only the confirmed front-end views, PHP pages, related MySQL tables, authentication boundary, and REST endpoints.
5. Demonstrate the forms, authentication, database queries, REST responses, validation, captcha boundary, and final user flow in a video or PDF, together with the zipped Front, Backend, and `.sql` deliverables required by the assignment.

## Local development

For the private classroom ZIP, follow [Google Calendar/Meet setup](docs/google-calendar-meet-setup.md). Extract its `proyecto-intefacesweb-develop/` folder under XAMPP's `htdocs`, start Apache/MySQL, and import `database/pasos.sql` through phpMyAdmin. Use Node.js 22.12+ and npm. From the extracted project:

```bash
cd frontend
npm install
npm run dev
```

Open `http://localhost:5174/`. Vite uses strict port 5174 and proxies `/backend` to `http://localhost/proyecto-intefacesweb-develop`. If the port is occupied, stop this project's previous frontend instance; do not switch ports for this delivery. Google returns through the registered Apache callback `http://localhost/proyecto-intefacesweb-develop/backend/api/google_callback.php`, then back to port 5174. `PASOS_FRONTEND_URL` can override the configured frontend return on a custom installation.

The private ZIP intentionally includes ignored `backend/config/google.local.php`; a Git checkout does not. Keep the ZIP private and outside Git/public web roots. Never serve the backend with a static server. Obtain account credentials separately, and delete sensitive archive copies when no longer needed. Browser OAuth and a real meeting still require manual verification; another installation is not guaranteed without testing.

The current MVP is implemented on the `developer` branch. The strategic proposal remains the scope reference, while runtime verification still requires XAMPP, MySQL, Firebase, and Google OAuth configuration.
