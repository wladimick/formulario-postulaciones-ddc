# Mejoras aplicadas al formulario de postulaciones

## Resumen

Esta versión reemplaza el flujo anterior basado en un JWT firmado en el navegador por un POST estándar y protegido desde el servidor. Corrige errores funcionales, endurece el tratamiento del currículum y mantiene los campos funcionales definidos por el cliente.

## Seguridad

### Credenciales SMTP

**Antes:** usuario y contraseña de aplicación Gmail estaban escritos directamente en `email.php`.

**Ahora:** `email.php` obtiene la configuración desde variables de entorno y no contiene credenciales.

**Acción operativa obligatoria:** la contraseña que estuvo publicada debe revocarse/rotarse. Como sigue presente en el historial de Git anterior a esta rama, una limpieza de código por sí sola no la vuelve segura.

### JWT eliminado

**Antes:** el navegador firmaba un JWT HS256 con el secreto literal `secret`, por lo que cualquier visitante podía fabricar solicitudes válidas. Los timestamps calculados tampoco se incluían en el payload.

**Ahora:** no existe JWT. Los campos viajan como `multipart/form-data` y son validados por el servidor. Se usa un token CSRF ligado a la sesión para evitar envíos forjados desde otros orígenes.

### Carga de currículum

**Antes:** el archivo se movía a `uploads/` con su nombre original, sin validar tamaño, extensión ni MIME.

**Ahora:**

- máximo 8 MB;
- solo PDF, DOC y DOCX;
- extensión y MIME comprobados;
- se valida que PHP lo reconozca como archivo subido;
- se adjunta directamente desde el temporal de PHP;
- nunca se crea un archivo ejecutable dentro del webroot;
- el nombre del adjunto se reemplaza por uno controlado por el servidor.

### Validación y escape

Todos los campos críticos se validan en `email.php`, incluso si el navegador es omitido. Los valores de selección se comparan contra listas permitidas y los textos se escapan antes de generar HTML del correo.

Los campos añadidos por el cliente también están cubiertos por este flujo. `Género` y `Estado Civil` se validan contra listas permitidas; el teléfono de contacto de emergencia se valida como teléfono; y los valores de texto libre se limitan en longitud y se escapan antes de incluirse en el correo.

### Anti-abuso

Se agregaron:

- honeypot invisible;
- rate limit por IP;
- rate limit por sesión;
- límite de tamaño total de la solicitud;
- mensajes de error que no exponen detalles de PHPMailer/SMTP.

El rate limit actual usa almacenamiento temporal local. Para despliegues con más de una instancia debe migrarse a un backend compartido, idealmente Redis.

## Correcciones funcionales

### RUT

**Antes:** el formulario solicitaba RUT, pero el JavaScript no lo incorporaba al JWT y `email.php` tampoco lo enviaba a RR.HH.

**Ahora:** el RUT forma parte del POST, se valida con módulo 11 y se incluye en la postulación. Para personas extranjeras es opcional; para nacionalidad chilena es obligatorio.

### Nacionalidad y pasaporte

**Antes:** país de origen y pasaporte eran obligatorios incluso para personas chilenas.

**Ahora:** esos campos solo aparecen y son obligatorios cuando se selecciona nacionalidad extranjera.

### Campos con valores seleccionados por defecto

**Antes:** planta, cargo, turnos, temporadas y otros `select` podían enviar una opción válida sin que la persona hubiese hecho una elección explícita.

**Ahora:** todos parten con “Selecciona…” y los campos relevantes son obligatorios.

### Currículum

**Antes:** el CV no era obligatorio en HTML, pero el backend intentaba adjuntarlo siempre.

**Ahora:** es obligatorio tanto en el navegador como en el servidor.

## Campos definidos por el cliente

En una primera revisión se propuso retirar algunos campos por criterios de minimización de datos. Posteriormente se confirmó que dichos campos fueron agregados expresamente por el cliente y forman parte del requerimiento del formulario, por lo que se reincorporaron y se mantienen.

Los campos son:

- `Género` — obligatorio;
- `Estado Civil` — obligatorio;
- `Contacto de emergencia (nombre completo)` — opcional;
- `Teléfono Contacto de Emergencia` — obligatorio;
- `Diseño Calle` — opcional y conservando la denominación original del cliente.

Además de aparecer nuevamente en su posición original dentro de la interfaz, estos campos:

- se envían mediante `FormData`;
- se limpian y limitan en longitud en `email.php`;
- se validan cuando corresponde;
- se incluyen en la versión HTML del correo de RR.HH.;
- se incluyen en la versión de texto plano del correo.

Se mantiene la casilla obligatoria de consentimiento para el tratamiento de los datos del proceso. RR.HH./legal debe validar el texto final y vincular la política corporativa vigente antes del lanzamiento.

## Experiencia de usuario y accesibilidad

- documento en `es-CL`;
- diseño responsive con identidad visual DDC;
- fecha de nacimiento mediante `input type="date"`;
- etiquetas y ayudas claras;
- estados de carga y éxito sin `alert()`;
- resumen de errores accesible con `role="alert"`;
- foco en el primer campo inválido;
- botón bloqueado durante el envío para reducir duplicados;
- tallas con opción “Prefiero indicar después”;
- “Cómo te enteraste” convertido a opciones consistentes;
- eliminación de jQuery, Bootstrap y `jsrsasign` del flujo del formulario.

## Operación y despliegue

La aplicación requiere variables de entorno SMTP. Consultar `README.md`.

### Acción crítica pendiente fuera del código

La credencial SMTP que apareció en el commit original debe ser revocada. También se recomienda reescribir el historial del repositorio si se desea eliminar el secreto del histórico, pero esto no sustituye la rotación de la credencial.

### Recomendación futura

En una evolución posterior, si el cliente lo considera apropiado, puede evaluarse almacenar las postulaciones en un sistema protegido y enviar a RR.HH. solo una notificación/enlace, en lugar de distribuir todos los datos personales y el CV por correo electrónico.
