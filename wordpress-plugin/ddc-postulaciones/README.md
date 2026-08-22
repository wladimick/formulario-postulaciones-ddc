# DDC Postulaciones — plugin WordPress

Plugin instalable para renderizar y procesar el formulario de postulaciones DDC dentro de WordPress.

## Por qué esta versión

El formulario se ejecuta completamente dentro de WordPress y envía mediante `wp_mail()`. Por lo tanto, si el sitio ya tiene **WP Mail SMTP** configurado con SendGrid, el formulario utiliza esa misma configuración sin almacenar ni duplicar API keys dentro de este plugin.

## Instalación

1. Copiar la carpeta `ddc-postulaciones` dentro de `wp-content/plugins/` o comprimirla como ZIP e instalarla desde **Plugins → Añadir plugin → Subir plugin**.
2. Activar **DDC Postulaciones**.
3. Ir a **Ajustes → Postulaciones DDC**.
4. Configurar el destinatario y el asunto.
5. Crear o editar la página de postulaciones y agregar:

```text
[ddc_formulario_postulacion]
```

Para reemplazar la página existente `/trabaja-con-nosotros/`, basta con usar el shortcode como contenido de esa página. No es necesario mantener `formulario.php`, `email.php`, `.htaccess` ni un archivo privado de SMTP para el nuevo flujo.

## Ajustes disponibles

### Destinatario

Casilla a la que se envían las postulaciones. Puede usarse un correo de pruebas y luego cambiarse desde el panel por la casilla definitiva de RR.HH.

### Asunto

La plantilla del asunto acepta:

- `{nombre}`: nombre completo del postulante;
- `{cargo}`: cargo seleccionado;
- `{planta}`: planta seleccionada;
- `{rut}`: RUT ingresado.

Ejemplo:

```text
Postulación DDC - {nombre} - {cargo}
```

## Correo y WP Mail SMTP

El plugin **no configura SMTP**. Usa:

```php
wp_mail(...)
```

WP Mail SMTP puede interceptar ese envío y entregarlo mediante SendGrid, SMTP u otro proveedor configurado en WordPress.

El `From` queda bajo control de WP Mail SMTP. El plugin agrega `Reply-To` con el email indicado por la persona postulante para que RR.HH. pueda responder directamente.

## Seguridad

- nonce de WordPress en cada envío;
- honeypot anti-bot;
- rate limit por IP con transients;
- sanitización y validación del lado servidor;
- RUT validado con dígito verificador;
- CV máximo 8 MB y limitado a PDF, DOC y DOCX;
- el CV se adjunta desde el temporal de PHP y no se guarda permanentemente en Media Library;
- datos insertados en el correo con escape HTML;
- no contiene contraseñas, credenciales SMTP ni API keys.

## Campos del cliente

Se mantienen, entre otros:

- Género;
- Estado Civil;
- Contacto de emergencia;
- Teléfono Contacto de Emergencia;
- Diseño Calle;
- RUT;
- país y documento para personas extranjeras;
- planta, cargo, temporadas y disponibilidad de turnos;
- ropa de trabajo;
- formación, experiencia y CV.

## Diagnóstico de correo

Si `wp_mail()` devuelve error, el plugin registra el error mediante el hook `wp_mail_failed`. En cPanel puede revisarse el log PHP y, si WP Mail SMTP tiene registro de correos habilitado, también su pantalla de logs.

Para diagnosticar SendGrid debe usarse primero la herramienta **Email Test** de WP Mail SMTP. Si esa prueba funciona, el formulario usa la misma ruta de envío.
