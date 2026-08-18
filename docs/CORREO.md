# Diseño del correo de postulaciones DDC

## Objetivo

El correo que recibe RR.HH. fue rediseñado para alinearse visualmente con la identidad de David Del Curto y facilitar una revisión rápida de cada postulación.

## Paleta utilizada

- Azul primario: `#003DA6`
- Celeste secundario: `#3CB4E5`
- Texto: `#606060`
- Turquesa de acento: `#00C7B1`
- Azul oscuro: `#2C2E65`
- Azul destacado: `#0662FF`
- Azul claro: `#BFD6F7`

Los otros colores corporativos disponibles se reservaron para no sobrecargar visualmente un correo de uso operativo.

## Tipografía

El template declara `Titillium Web` como primera opción y usa `Arial`, `Helvetica` y `sans-serif` como fallback.

No se importa la fuente desde Google Fonts porque muchos clientes de correo bloquean fuentes externas o eliminan reglas CSS avanzadas. De esta forma el mensaje conserva una apariencia consistente en Gmail, Outlook, Apple Mail y clientes móviles incluso cuando Titillium Web no está disponible.

## Estructura

El mensaje se organiza en:

1. encabezado corporativo DDC;
2. nombre del postulante y resumen del cargo/planta;
3. aviso de currículum adjunto y Reply-To al postulante;
4. información personal;
5. contacto y ubicación;
6. datos de la postulación;
7. formación y experiencia;
8. tallas de ropa de trabajo;
9. nota de privacidad;
10. pie corporativo.

El correo muestra país de origen y pasaporte/documento únicamente cuando la nacionalidad seleccionada es extranjera.

## Campos definidos por el cliente

Además de los datos generales, el correo incluye los campos que el cliente confirmó como parte del requerimiento funcional:

- Género.
- Estado Civil.
- Contacto de emergencia.
- Teléfono Contacto de Emergencia.
- Diseño Calle.

Estos valores pasan por la limpieza y validación del backend antes de insertarse en el mensaje. Los textos se escapan antes de generar HTML y los mismos campos se incluyen en `AltBody` para la versión de texto plano.

## Compatibilidad de correo

El HTML usa tablas de presentación y estilos inline de forma deliberada. Aunque no es el patrón habitual de una aplicación web moderna, sigue siendo la técnica más compatible para email HTML, especialmente con versiones de Outlook que no interpretan correctamente Flexbox, Grid ni gran parte del CSS moderno.

No depende de imágenes externas para comunicar información esencial. La marca `DDC.` se representa con texto, por lo que el encabezado sigue siendo reconocible aunque el cliente del destinatario bloquee imágenes remotas.

Se mantiene además `AltBody` en texto plano para clientes que no renderizan HTML.

## Comportamiento de respuesta

PHPMailer configura `Reply-To` con el email indicado por la persona postulante. Por lo tanto, RR.HH. puede utilizar **Responder** desde el mismo correo recibido y contactar directamente al candidato sin copiar manualmente su dirección.
