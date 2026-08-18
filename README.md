# Formulario de postulaciones DDC

Formulario PHP para recibir postulaciones laborales y enviarlas de forma segura al equipo de Personas mediante SMTP.

## Requisitos

- PHP 8.1 o superior recomendado.
- Extensiones PHP: `ctype`, `filter`, `hash`, `fileinfo`, `mbstring` y `openssl`.
- Dependencias de Composer instaladas (`vendor/autoload.php`).
- HTTPS en producción.

## Configuración SMTP

Las credenciales **no deben quedar en el repositorio ni dentro de `public_html`**.

### cPanel / hosting actual

En el hosting de DDC la configuración privada se lee por defecto desde:

```text
/home/daviddelcurtotib/ddc-form-config.php
```

El archivo debe estar fuera de `public_html` y retornar un array PHP:

```php
<?php

return [
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 465,
    'SMTP_SECURE' => 'ssl',
    'SMTP_USER' => 'cuenta@dominio.cl',
    'SMTP_PASS' => 'credencial-secreta',
    'SMTP_FROM' => 'cuenta@dominio.cl',
    'SMTP_FROM_NAME' => 'David Del Curto - Postulaciones',
    'APPLICATION_RECIPIENT' => 'rrhh@dominio.cl',
];
```

Para pruebas, basta cambiar `APPLICATION_RECIPIENT`. Actualmente puede apuntarse a una casilla de desarrollo sin modificar `email.php`.

**No usar `SetEnv` en el `.htaccess` de este hosting:** la configuración de Apache del cPanel actual rechaza esa directiva y provoca un error 500 para la carpeta.

### Variables de entorno como fallback

`email.php` sigue aceptando variables de entorno si el archivo privado no existe o si una clave no está definida en él:

| Variable | Obligatoria | Ejemplo / propósito |
| --- | --- | --- |
| `DDC_FORM_CONFIG` | No | Ruta alternativa al archivo PHP privado |
| `SMTP_HOST` | No | `smtp.gmail.com` |
| `SMTP_PORT` | No | `465` |
| `SMTP_SECURE` | No | `ssl` o `tls` |
| `SMTP_USER` | Sí | Cuenta usada para autenticar SMTP |
| `SMTP_PASS` | Sí | Contraseña de aplicación o credencial SMTP |
| `SMTP_FROM` | Sí* | Remitente. Si se omite, usa `SMTP_USER` |
| `SMTP_FROM_NAME` | No | Nombre visible del remitente |
| `APPLICATION_RECIPIENT` | Sí | Casilla que recibe las postulaciones |

## Flujo

1. `formulario.php` inicia la sesión y genera un token CSRF.
2. `scripts/formulario.js` envía `FormData` directamente a `email.php`.
3. `email.php` aplica CSRF, honeypot, rate limit, validación de campos y validación del CV.
4. `email.php` carga la configuración privada desde `/home/daviddelcurtotib/ddc-form-config.php` y usa variables de entorno como fallback.
5. El CV se adjunta desde el archivo temporal administrado por PHP; nunca se escribe en una carpeta pública.
6. PHPMailer envía el correo y el servidor rota el token CSRF.

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
- Configuración SMTP privada fuera de `public_html`.
- Variables de entorno disponibles como fallback.
- Consentimiento explícito de tratamiento de datos.
- Interfaz accesible y responsive sin jQuery, Bootstrap ni `jsrsasign`.

## Campos funcionales definidos por el cliente

Se mantienen y procesan expresamente los campos incorporados por el cliente, incluyendo `Género`, `Estado Civil`, `Contacto de emergencia`, `Teléfono Contacto de Emergencia` y `Diseño Calle`. Están ubicados en las mismas secciones del formulario original, pasan por el backend y se incluyen en el correo enviado a RR.HH.

Estos campos **no deben eliminarse como parte de una refactorización técnica o visual** sin una nueva indicación del cliente.

Consulta [`docs/FORMULARIO.md`](docs/FORMULARIO.md) para el detalle de ubicación, obligatoriedad y validación de estos campos.

## Correo recibido por RR.HH.

El correo HTML usa la identidad visual DDC, separa los datos en bloques legibles, destaca cargo y planta, informa claramente que el CV viene adjunto y configura `Reply-To` con el email de la persona postulante. Se mantiene también una versión de texto plano para clientes que no renderizan HTML.

Consulta [`docs/CORREO.md`](docs/CORREO.md) para la paleta, estructura y decisiones de compatibilidad del template.

## Antes de producción

1. **Revocar inmediatamente cualquier contraseña SMTP que haya quedado expuesta previamente.**
2. Guardar la credencial activa únicamente en `/home/daviddelcurtotib/ddc-form-config.php` o en un mecanismo de secretos equivalente.
3. Verificar que el archivo privado no sea legible desde la web y tenga permisos restrictivos (idealmente `600` o `640`, según el hosting).
4. Verificar límites PHP: `upload_max_filesize >= 8M` y `post_max_size >= 9M`.
5. Confirmar con RR.HH./legal el texto de consentimiento y enlazar la política de privacidad corporativa vigente.
6. Hacer una prueba real de envío en staging/producción controlada.
7. Para múltiples servidores/containers, reemplazar el rate limit basado en `sys_get_temp_dir()` por Redis u otro almacenamiento compartido.

## Dependencias

El código de ejecución usa PHPMailer. El repositorio conserva las dependencias JWT existentes en `composer.json`/`composer.lock` para no dejar el lock inconsistente sin Composer disponible en este entorno. Ya no se usan en el código. En la próxima actualización de dependencias se recomienda ejecutar:

```bash
composer remove adhocore/jwt firebase/php-jwt
```

Después, confirmar y versionar el `composer.json` y `composer.lock` regenerados.

## Documentación de la mejora

- [`docs/MEJORAS.md`](docs/MEJORAS.md): vulnerabilidades corregidas, cambios funcionales y decisiones pendientes.
- [`docs/FORMULARIO.md`](docs/FORMULARIO.md): diseño visual y campos funcionales del formulario.
- [`docs/CORREO.md`](docs/CORREO.md): diseño y compatibilidad del correo de postulación.
