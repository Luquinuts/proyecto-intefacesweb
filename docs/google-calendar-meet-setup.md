# Configuración de Google Calendar y Google Meet

Esta guía configura la integración OAuth 2.0 usada por PASOS para crear eventos en Google Calendar y enlaces de Google Meet.

## 1. Crear proyecto en Google Cloud

1. Entra en [Google Cloud Console](https://console.cloud.google.com/).
2. Crea un proyecto nuevo (por ejemplo, `pasos-integracion`).
3. Selecciona el proyecto.

## 2. Habilitar APIs

En **APIs y servicios > Biblioteca**, habilita:

- **Google Calendar API**
- **Google Meet REST API**

## 3. Configurar pantalla de consentimiento OAuth

1. Ve a **APIs y servicios > Pantalla de consentimiento de OAuth**.
2. Elige **Externo** (para pruebas) o el tipo que corresponda.
3. Completa nombre de app, correo de soporte y correo de desarrollador.
4. En **Usuarios de prueba**, agrega la cuenta de Google del abogado que usará la app.
5. Guarda los cambios.

## 4. Crear credenciales OAuth 2.0

1. Ve a **APIs y servicios > Credenciales**.
2. Crea **ID de cliente de OAuth**.
3. Tipo de aplicación: **Aplicación web**.
4. Agrega este URI de redirección autorizado:

   ```text
   http://localhost/proyecto-intefacesweb/backend/api/google_callback.php
   ```

5. Guarda y copia el **Client ID** y **Client Secret**.

## 5. Configurar credenciales en PASOS

1. Copia el archivo de ejemplo:

   ```text
   backend/config/google.local.php.example
   ```

   a:

   ```text
   backend/config/google.local.php
   ```

2. Completa las credenciales:

   ```php
   <?php
   return [
       'client_id' => 'TU_CLIENT_ID.apps.googleusercontent.com',
       'client_secret' => 'TU_CLIENT_SECRET',
       'redirect_uri' => 'http://localhost/proyecto-intefacesweb/backend/api/google_callback.php',
   ];
   ```

3. No subas `google.local.php` al repositorio.

## 6. Ejecutar migración de base de datos

Con MySQL/MariaDB iniciado, ejecuta:

```sql
SOURCE C:/xampp/htdocs/proyecto-intefacesweb/database/google_calendar_migration.sql;
```

O ejecuta el contenido del archivo en phpMyAdmin.

## 7. Conectar la cuenta del abogado

1. Inicia sesión como abogado.
2. Abre una consulta de teléfono o videollamada.
3. Pulsa **Conectar Google Calendar**.
4. Acepta los permisos de Google.
5. Al volver, la app mostrará la cuenta conectada.

## 8. Comportamiento esperado

- **Videollamada**: al agendar, se crea evento en Google Calendar y se genera enlace de Google Meet automáticamente.
- **Teléfono**: al agendar, se crea evento en Google Calendar sin enlace de Meet.
- El enlace de Meet se muestra en la consulta y en la pestaña **Videollamada Google Meet**.

## Notas

- La integración usa OAuth 2.0 con consentimiento del abogado.
- Los eventos se crean en el calendario principal (`primary`) de la cuenta Google conectada.
- Si el token expira, el backend intenta refrescarlo automáticamente con el `refresh_token`.
- Si ves errores de permisos, revisa que Calendar API y Meet REST API estén habilitadas y que la cuenta esté en usuarios de prueba mientras la app esté en modo prueba.
