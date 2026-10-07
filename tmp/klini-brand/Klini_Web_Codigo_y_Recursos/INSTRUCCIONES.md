# Klini — Código y recursos

Actualización del 5 de octubre de 2026: campaña de expectativa «Muévete. Conecta. Sé parte.». La página se centra en movimiento, hábitos, pertenencia a una comunidad y registro digital del bienestar.

## Contenido del paquete

| Ruta | Contenido |
|---|---|
| `sitio/index.html` | Página completa, íconos internos, acceso y registro de usuarios. |
| `sitio/styles.css` | Identidad visual y estilos para escritorio, tableta y móvil. |
| `sitio/app.js` | Menú, diálogos, pestañas, validaciones y registro ilustrativo de hábitos. |
| `sitio/assets/` | Tres fotografías WebP, logotipos SVG, favicon, tipografías WOFF y licencia. |
| `recursos/catalogo.html` | Catálogo visual para consultar y descargar los recursos. |
| `recursos/catalogo.json` | Inventario con rutas, medidas, tamaños y huellas SHA-256. |
| `recursos/originales/` | Tres fotografías originales de alta resolución generadas con Runway. |
| `recursos/iconos/` | 17 íconos SVG individuales, exportados de los símbolos del HTML. |

## Publicar o integrar

1. Extrae el ZIP completo. Abre `sitio/index.html` para revisar la página y `recursos/catalogo.html` para explorar las imágenes.
2. Para publicarla, sube **el contenido de `sitio`** al directorio público de tu hosting, por ejemplo `public_html`, o a una subcarpeta.
3. Mantén `index.html`, `styles.css`, `app.js` y `assets` con sus rutas relativas.
4. Abre tu dominio o ruta mediante HTTPS y comprueba el diseño y las interacciones.

No requiere React, Node.js, bases de datos ni compilación. Todos los recursos son locales; no hay una CDN obligatoria. `recursos` es material de entrega y no es necesario en producción. No se incluyen claves privadas, Git ni configuración privada de Sites.

Si tu entorno necesita servidor local y tienes Python instalado, ejecuta `python -m http.server 8080` dentro de `sitio` y abre `http://localhost:8080` para revisar. No uses este servidor de prueba como hosting público.

Para incorporar secciones a una plantilla existente, adapta las rutas de CSS, JavaScript y assets; conserva los identificadores usados por `app.js`. El CSS contiene reglas globales, por lo que conviene delimitarlas a un contenedor de Klini al combinarlo con otros estilos.

## Dirección comercial

- Mensaje principal: **Muévete. Conecta. Sé parte.**
- Propuesta: una nueva forma de vivir el bienestar a través de movimiento, hábitos, comunidad y una experiencia digital.
- Tono: cercano, joven, inclusivo, activo y aspiracional.
- La versión entregada utiliza la marca **Klini**, sin el descriptor adicional ni el aviso de lanzamiento. Conserva el enfoque en bienestar y comunidad.
- Llamadas principales: «Únete, crea tu cuenta» y «Quiero ser parte».

Las imágenes muestran jóvenes compartiendo yoga/estiramientos, ejercicio en grupo y consulta de un reloj inteligente. Fueron generadas con Runway para esta campaña; no son testimonios de usuarios reales.

## Registro digital ilustrativo

La sección «Tu bienestar digital» tiene tres vistas: hábitos, señales del cuerpo y comunidad. En la primera, «Probar un registro» permite cambiar los valores de ejemplo de actividad, hidratación y descanso. Esos datos solo existen en memoria de la página y se reinician al recargar. No se transmiten ni se guardan.

La frecuencia cardíaca, los pasos y el descanso son valores ilustrativos, no lecturas en vivo. La página no accede a un reloj ni a un dispositivo. Las integraciones y la compatibilidad se presentan como pendientes de definir para el lanzamiento. Las actividades de comunidad son conceptos, sin reservas ni programación confirmada.

Para incorporar persistencia y sincronización reales, integra los servicios autorizados de Klini y los proveedores de dispositivos elegidos. No uses esta demostración como un registro clínico.

## Acceso y creación de cuentas

El ícono de persona permanece arriba a la derecha. El botón «Únete, crea tu cuenta» aparece centrado debajo del menú y en otras llamadas de la campaña. Abre el formulario con tipo de usuario, nombre, correo, contraseña y confirmación.

**Los formularios son vistas previas. No crean cuentas, autentican usuarios ni envían correos.** Muestran el estado pendiente de conexión y no simulan un registro exitoso. Ambos usan `method="dialog"` para evitar envíos de navegación sin JavaScript. Las contraseñas se borran al enviar; todos los datos se limpian al cerrar o cambiar de modo.

El selector muestra «Miembro de la comunidad» (valor interno `paciente`, conservado para la integración) y «Médico» (`medico`). La autorización de roles debe validarse en el servidor.

Para conectar la funcionalidad:

1. Integra el manejador de `login-form` con el servicio oficial de acceso y sesiones.
2. Integra `signup-form` con el servicio de creación de cuentas.
3. Conecta `forgot-password` con la recuperación de contraseñas.
4. Gestiona espera, error y éxito según la respuesta real del servicio; aplica permisos en el servidor.
5. Retira los avisos de vista previa únicamente cuando esas funciones estén conectadas y verificadas.

## Recursos y marca

| Archivo web | Uso | Dimensiones |
|---|---|---|
| `assets/wellness-yoga.webp` | Portada; mantener el encuadre vertical | 1120 × 1400 px |
| `assets/wellness-running.webp` | Ejercicio en grupo | 1200 × 900 px |
| `assets/wellness-watch.webp` | Reloj inteligente y bienestar | 1200 × 900 px |
| `assets/klini.svg` | Logotipo principal en fondo claro | Vectorial |
| `assets/klini-white.svg` | Logotipo blanco para fondo oscuro | Vectorial |
| `assets/favicon.svg` | Ícono de pestaña | Vectorial |

Los originales PNG están en `recursos/originales`: yoga 2048 × 2560 px; running y reloj 2560 × 1920 px. Las versiones WebP son las utilizadas en la página. El logotipo conserva el dibujo extraído del manual facilitado.

Paleta: azul `#082441`, turquesa `#00AAA5`, turquesa oscuro `#007D79`, menta `#DDF3EF`, fondo suave `#F5F9F8`. Tipografía: DejaVu Sans Regular y Bold autoalojada. Conserva `assets/font-license.txt` al redistribuirla.

## Edición y comprobaciones

Edita textos y metadatos en `index.html`, diseño en `styles.css` e interacciones en `app.js`. El catálogo JSON identifica los recursos; puedes incorporarlos a otro proyecto manteniendo sus formatos y proporciones.

Se verificaron sintaxis JavaScript, rutas locales, vínculos internos, referencias a elementos e identificadores de accesibilidad. El código de `sitio` coincide con el checkout publicado. Comprueba el resultado en el navegador y en tu hosting al integrarlo, así como los servicios reales que conectes.
