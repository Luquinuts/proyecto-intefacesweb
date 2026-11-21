# Google Calendar and Meet Integration

Objetivo: Integrar Google Calendar API y Google Meet REST API en PASOS para agendar citas del abogado.
Rama: developer
Estrategia de entrega: single-pr
Test runner: manual/endpoint checks (PHP, no test suite configured)

## Specs

- S1. Acceso con consentimiento del abogado/cuenta Google (OAuth 2.0). El abogado autoriza su cuenta Google y la app usa sus tokens para crear eventos.
- S2. No hay proyecto en Google Cloud aún; la implementación debe incluir configuración/documentación clara para crear credenciales y colocar `client_id`, `client_secret` y redirect URI.
- S3. Los eventos se crean en el calendario del abogado logueado (su cuenta Google autorizada).
- S4. Redirect URI: `http://localhost/proyecto-intefacesweb/backend/api/google_callback.php`.
- S5. Al agendar una consulta **videollamada** se crea evento en Google Calendar + enlace de Google Meet automáticamente.
- S6. Al agendar una consulta **teléfono** se crea evento en Google Calendar sin Meet.
- S7. El campo manual "Enlace de Google Meet" se reemplaza por el generado automáticamente.

## Tasks

| ID | Specs | Route | Status | Commit/Evidence |
|---|---|---|---|---|
| T1 | S1-S4 | inline | done | `6b74e86` OAuth config/lib/endpoints, token table, setup doc; php -l OK |
| T2 | S5-S7 | inline | done | `6b74e86` consultations.php creates Calendar event/Meet link; UI removes manual Meet input; php -l OK |
| T3 | S1-S7 | inline | done | `6b74e86` PHP syntax checks passed; DB migration SQL added; MySQL service unavailable for live migration during implementation |

## Log

### L1 - Solicitud original

> 1. Con consentimiento del abogado/cuenta Google, 2. No tengo proyecto en google cloud, 3. Los eventos se crean en el calendario del abogado logueado, 4. Que sea esa, 5. - Si, -Si, - Lo reemplazamos por el generado

Solicitud previa: "Necesito que implementes la api rest de google meet y la api de google calendar a la aplicación"

### L2 - Implementación

- Se creó config OAuth (`backend/config/google.php`, `google.local.php.example`), cliente Google REST (`backend/lib/GoogleCalendarMeet.php`), endpoints `google_auth.php`, `google_callback.php`, `google_status.php`.
- Se agregó migración `database/google_calendar_migration.sql` y tabla `google_oauth_tokens` en `pasos.sql`.
- `consultations.php` crea evento de Google Calendar al agendar teléfono/videollamada; videollamada solicita `conferenceData` y guarda `meeting_link` generado.
- Frontend abogado: botón conectar Google, estado conectado, eliminación de input manual de Meet, mensaje de enlace generado.
- Verificación: `php -l` en archivos PHP modificados/creados OK. MySQL local no estaba disponible (10061), por lo que la migración no se ejecutó en vivo; se dejó SQL listo.
