# Formulario de postulaciones DDC

Formulario PHP para recibir postulaciones laborales y enviarlas de forma segura al equipo de Personas mediante SMTP.

## Requisitos

- PHP 8.1 o superior recomendado.
- Extensiones PHP: `ctype`, `filter`, `hash`, `fileinfo`, `mbstring` y `openssl`.
- Dependencias de Composer instaladas (`vendor/autoload.php`).
- HTTPS en producción.

## Configuración SMTP

Las credenciales **no deben quedar en el repositorio**. Configura estas variables de entorno en el servidor:

| Variable | Obligatoria | Ejemplo / propósito |
| --- | --- | --- |
| `SMTP_HOST` | No | `smtp.gmail.com` |
| `SMTP_PORT` | No | `465` |
| `SMTP_SECURE` | No | `ssl` o `tls` |
| `SMTP_USER` | Sí | Cuenta usada para autenticar SMTP |
| `SMTP_PASS` | Sí | Contraseña de aplicación o credencial SMTP |
| `SMTP_FROM` | Sí* | Remitente. Si se omite, usa `SMTP_USER` |
| `SMTP_FROM_NAME` | No | Nombre visible del remitente |
| `APPLICATION_RECIPIENT` | Sí | Casilla que recibe las postulaciones |

Ejemplo Apache (fuera del repositorio):

```apacheconf
SetEnv SMTP_USER "cuenta@dominio.cl"
SetEnv SMTP_PASS "credencial-secreta"
SetEnv SMTP_FROM "cuenta@dominio.cl"
SetEnv APPLICATION_RECIPIENT "rrhh@dominio.cl"
```

En Nginx/PHP-FPM, define las variables en el pool de PHP-FPM o mediante el mecanismo de secretos del hosting. Evita archivos `.env` servidos por el web server.

## Flujo

1. `formulario.php` inicia la sesión y genera un token CSRF.
2. `scripts/formulario.js` envía `FormData` directamente a `email.php`.
3. `email.php` aplica CSRF, honeypot, rate limit, validación de campos y validación del CV.
4. El CV se adjunta desde el archivo temporal administrado por PHP; nunca se escribe en una carpeta pública.
5. PHPMailer envía el correo y el servidor rota el token CSRF.

## Controles implementados

- Sin JWT ni secretos en el navegador.
- Validación completa del lado servidor.
- RUT chileno con dígito verificador.
- Campos de extranjero condicionales.
- CV obligatorio, máximo 8 MB, extensión y MIME controlados.
- Token CSRF por sesión.
- Honeypot anti-bot.
- Rate limit local: 5 intentos por 15 minutos por IP y sesión.
- Escape HTML de todos los datos incluidos en el correo.
- Errores internos enviados a logs; el usuario recibe mensajes genéricos.
- SMTP mediante variables de entorno.
- Consentimiento explícito de tratamiento de datos.
- Interfaz accesible y responsive sin jQuery, Bootstrap ni `jsrsasign`.

## Antes de producción

1. **Revocar inmediatamente la contraseña SMTP que estuvo publicada en el historial Git.** Cambiar el archivo actual no invalida una credencial ya expuesta.
2. Configurar las variables de entorno anteriores.
3. Verificar límites PHP: `upload_max_filesize >= 8M` y `post_max_size >= 9M`.
4. Confirmar con RR.HH./legal el texto de consentimiento y enlazar la política de privacidad corporativa vigente.
5. Hacer una prueba real de envío en staging.
6. Para múltiples servidores/containers, reemplazar el rate limit basado en `sys_get_temp_dir()` por Redis u otro almacenamiento compartido.

## Dependencias

El código de ejecución usa PHPMailer. El repositorio conserva las dependencias JWT existentes en `composer.json`/`composer.lock` para no dejar el lock inconsistente sin Composer disponible en este entorno. Ya no se usan en el código. En la próxima actualización de dependencias se recomienda ejecutar:

```bash
composer remove adhocore/jwt firebase/php-jwt
```

Después, confirmar y versionar el `composer.json` y `composer.lock` regenerados.

## Documentación de la mejora

Consulta [`docs/MEJORAS.md`](docs/MEJORAS.md) para el detalle de vulnerabilidades corregidas, cambios de UX y decisiones pendientes.
