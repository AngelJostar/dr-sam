# Integración

## Montaje independiente

Copia `src/` y `assets/` juntos al directorio público de tu aplicación, por ejemplo `public/klini/`. Las rutas se resuelven respecto al módulo, de modo que ambas carpetas deben conservar su relación.

```html
<klini-workspace section="communities" embedded hide-bottom-nav></klini-workspace>
<script type="module" src="/klini/src/app.js"></script>
```

- `section="communities"` o `section="calendar"` selecciona la vista inicial.
- `embedded` oculta el encabezado de la demo.
- `hide-bottom-nav` oculta la navegación inferior para utilizar la de tu aplicación.
- Estos atributos se leen al montar el componente. Para cambiar de sección desde la aplicación, vuelve a montarlo con la sección deseada o adapta el componente al enrutador.

También puedes montarlo por código:

```js
import {mountKlini} from '/klini/src/app.js';
const app = mountKlini(document.querySelector('#panel'), {
  section: 'calendar',
  embedded: true,
  hideBottomNav: true,
});
// Al salir de la ruta:
// app.destroy();
```

Los estilos usan Shadow DOM para evitar colisiones con el proyecto. Las variables de color se pueden sobrescribir desde el host, por ejemplo `klini-workspace { --teal: #007D79; }`.

## React

`examples/KliniPanel.tsx` muestra un montaje del módulo público con `useEffect`. Es un ejemplo para React, no una dependencia de la demo ni un paquete React instalado. Ajusta la ruta `/klini/` a la base real de tu aplicación. En Next.js marca el adaptador como componente cliente. En otros empaquetadores puedes importar `mountKlini` directamente desde tu árbol de código.

## Navegación

El componente emite un evento `klini:navigate` con `detail.target` para `home`, `devices`, `register` o `health`. Conéctalo al router del proyecto. Estas cuatro pantallas no forman parte de este paquete.

El evento `klini:change` indica una mutación local. No constituye una integración remota ni envía los datos a un servidor. La propiedad `element.state` contiene el estado local de la demo; evita convertir esa estructura completa en un API de producción.

## Sustituir el adaptador local

`data.js` contiene la semilla y la persistencia local. `app.js` usa `mutate()` para clonar, guardar y volver a dibujar el estado. Para producción separa esa función en operaciones asíncronas por entidad, utilizando los servicios existentes de Klini.

| Operación | Información que debe resolver el servicio existente |
| --- | --- |
| Listar comunidades | Categoría, membresía del usuario, paginación, búsqueda y permisos |
| Unirse/salir | Usuario autenticado, comunidad, reglas de acceso y conteo actualizado |
| Feed | Publicaciones paginadas, autor, comunidad, imágenes autorizadas y estado del usuario |
| Like/guardar | Estado final deseado, evitando duplicados por reintentos |
| Comentarios | Autor autenticado, publicación, paginación y moderación |
| Compartir | Enlace canónico a la publicación; autorización al abrirla |
| Subir imágenes | Almacenamiento de objetos, tamaño, formato, procesamiento y URLs de acceso |
| Historias | Autor, audiencia, expiración y estado visto/no visto |
| Reportar | Motivo, autor, publicación y cola de moderación |
| Agenda | Paciente, sede, consultorio, médico, especialidad y zona horaria |
| Crear/reprogramar cita | Validación atómica de disponibilidad en backend |
| Reportes | Filtros, alcance del usuario y auditoría de acceso |

La tabla describe contratos necesarios; no afirma que esas rutas o servicios ya existan. `contracts.d.ts` documenta los datos simplificados de la demo. El modelo de producción debe usar identificadores de pacientes y usuarios en lugar de nombres como clave.

## Comportamientos de la demo

- `localStorage` usa la clave `klini-social-agenda-demo-v1`. Cada navegador conserva su propia copia.
- Las imágenes cargadas se reducen en el navegador a 1600 px de lado máximo y se guardan como datos locales. La cuota es limitada; las operaciones que no caben muestran un error y conservan el estado anterior.
- La demo no es un sistema de comunicación multiusuario. Publicar, comentar y reportar afecta únicamente a esa copia local.
- El selector Mis comunidades filtra el feed por membresías; Wellness y Sport filtran por categoría. Nuevas comunidades muestra las comunidades marcadas como nuevas.
- Guardados ofrece una vista de las publicaciones guardadas, independientemente de la categoría elegida.
- El calendario utiliza fechas `YYYY-MM-DD` y horas `HH:mm` locales, con horarios de 08:00 a 18:00. La validación de empalmes considera consultorio, fecha, duración y citas no canceladas.
- La vista Semana permite consultar siete días; con todos los consultorios agrupa sus citas por día. La vista Día separa columnas por consultorio.
- El resumen y el CSV siempre corresponden al día seleccionado y los filtros activos, incluso en vistas Semana o Mes.
- El estado cancelado libera el horario para la validación, pero la cita permanece visible en el historial del día.
- La demo admite compartir con `navigator.share` cuando está disponible y copiar enlace como alternativa. El usuario confirma la acción nativa.

## Recursos

Las imágenes de publicaciones son recortes de los diseños; sus resoluciones se conservan sin inventar detalle adicional. Los avatares tienen canal alfa circular. No contienen botones; sus anillos, etiquetas y controles se dibujan mediante CSS y HTML.

Las referencias visuales son propuestas, no capturas de una aplicación desplegada. La demo mantiene el lenguaje visual y adapta el contenido y las proporciones para el uso real. Las fuentes y los iconos incluyen sus licencias en `licenses/`.
