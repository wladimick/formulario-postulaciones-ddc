# Diseño visual del formulario

## Objetivo

El formulario conserva las mejoras de seguridad y validación de la nueva versión, pero recupera la identidad visual de la página de postulaciones DDC usada como referencia y mantiene los campos definidos por el cliente.

## Cambios visuales

- Hero fotográfico de equipo a todo el ancho.
- Bloque azul superpuesto sobre la fotografía con el mensaje “Entrega lo mejor de ti en DDC!”.
- Gradiente basado en los colores corporativos `#0662FF` y `#003DA6`.
- Fondo gris `#E9E9E9` para el área del formulario, eliminando la apariencia de tarjeta blanca genérica.
- Títulos de sección en azul corporativo `#003DA6`.
- Tipografía `Titillium Web`, coherente con el sitio DDC, con fallback Arial.
- Campos más compactos, bordes discretos y radios mínimos para acercarse al diseño original.
- Distribución de 2, 3 y 4 columnas en escritorio y apilado en pantallas pequeñas.
- Botón de envío azul similar al utilizado en el sitio corporativo.
- Estados de error, foco, accesibilidad y validación se mantienen visibles sin romper la estética.

## Campos definidos por el cliente

Tras confirmar que forman parte del requerimiento funcional, se conservaron/reincorporaron en su ubicación original:

- `Género` — obligatorio.
- `Estado Civil` — obligatorio.
- `Contacto de emergencia (nombre completo)` — opcional, tal como estaba definido originalmente.
- `Teléfono Contacto de Emergencia` — obligatorio.
- `Diseño Calle` — opcional, conservando el nombre entregado por el cliente.

Estos campos viajan en el mismo `FormData`, se procesan y escapan en el servidor y se incorporan al correo recibido por RR.HH. Los campos con opciones cerradas (`Género` y `Estado Civil`) se validan además contra listas permitidas en `email.php`.

La distribución recupera el orden de la referencia original: Género y Estado Civil junto a RUT/fecha de nacimiento; contacto y teléfono de emergencia dentro de Información de contacto; y Diseño Calle inmediatamente después.

## Imagen de cabecera

La cabecera utiliza la fotografía de equipo DDC que corresponde a la referencia visual proporcionada. Actualmente se carga desde `cdn.portalfruticola.com` y el CSP permite únicamente ese host adicional para imágenes. Si DDC dispone del archivo original, se recomienda copiarlo al proyecto y servirlo localmente para eliminar esa dependencia externa.

## Responsive

- Escritorio: contenedor de hasta 1065 px con varias columnas.
- Tablet: los campos de tres/cuatro columnas pasan a dos columnas cuando es necesario.
- Móvil: todos los campos se muestran en una sola columna y el bloque de cabecera ocupa todo el ancho.
