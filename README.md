# PASOS — Interfaces y Programación Web

PASOS is a proposed academic web project for a first-entry legal orientation service. It helps a person describe a situation, request human review, and understand a reasonable next step. It is not an automated legal-advice system, a chatbot, or a promise of legal representation.

## Repository purpose

The repository will contain the course project proposal and, after scope confirmation, a basic page-oriented front end and PHP/MySQL back end. The implementation must remain feasible to explain and reproduce by hand.

## Academic stack

- Front end: Vue.js, HTML5, CSS, Bootstrap, and JavaScript.
- Back end: PHP, MySQL, Firebase Authentication, REST, jQuery, HTML5, CSS, and Bootstrap.
- Database access: PHP `mysqli` and explicit SQL queries.

## Current documents

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

The PHP backend must be served by XAMPP. To run the Vue frontend with Vite:

```bash
cd frontend
npm install
npm run dev
```

Copy `frontend/.env.example` to `frontend/.env.local` when the backend uses a different URL and set `VITE_API_BASE_URL`. For Google OAuth, set the non-secret backend environment value `PASOS_FRONTEND_URL` to the Vite frontend URL; otherwise the callback falls back to the XAMPP frontend path. Keep Google credentials in `backend/config/google.local.php`, which is ignored by Git.

The current MVP is implemented on the `developer` branch. The strategic proposal remains the scope reference, while runtime verification still requires XAMPP, MySQL, Firebase, and Google OAuth configuration.
