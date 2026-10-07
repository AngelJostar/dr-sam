# Klini · Comunidades y Calendario

Paquete de código y recursos para integrar los dos diseños en el proyecto existente de Klini. Incluye una demo frontend con interacciones locales, dos referencias visuales y un prompt para Codex.

## Abrir la demo

1. Extrae todo el ZIP.
2. Abre una terminal dentro de `Klini_Comunidades_Calendario_Codex`.
3. Con Node.js 20 o posterior, ejecuta `node serve.mjs`.
4. Abre `http://localhost:4173`.

No requiere `npm install`, claves API, CDN ni servicios externos. Mantén la terminal abierta. No abras `index.html` directamente con `file://`: los módulos JavaScript necesitan el servidor local. El servidor incluido escucha únicamente en tu equipo y es para desarrollo.

En la barra superior puedes cambiar entre **Comunidades** y **Calendario**. El calendario empieza el **27 de julio de 2026** para mostrar la cita de demostración; el botón **Hoy** usa la fecha actual del navegador. En opciones puedes exportar o restablecer los datos locales.

## Lo que funciona

| Comunidades | Calendario |
| --- | --- |
| Historias de personas, navegación anterior/siguiente y alta de una historia con imagen | Vistas día, semana, mes y lista |
| Mis comunidades, Wellness, Sport y Nuevas comunidades | Navegación por fecha, mes y año; botón Hoy |
| Explorar, unirse/salir y crear una comunidad local | Filtros por especialidad y consultorio |
| Feed de fotografías y carrusel de imágenes por publicación | Agenda por consultorio y franjas de 30 minutos |
| Likes reversibles, comentarios y guardados | Alta de cita, duración y cambio de estado |
| Compartir mediante función nativa o copiar enlace | Validación de empalmes y límites de horario |
| Crear publicaciones con imagen y texto | Resumen del día y descarga CSV |
| Ocultar publicaciones y registrar un reporte local | Persistencia local de citas de demostración |

## Contenido

- `src/app.js`: componente web reutilizable, vistas, formularios y eventos.
- `src/styles.css`: estilos adaptables a escritorio y móvil, aislados mediante Shadow DOM.
- `src/model.js`: fechas, filtros, validación de horarios, likes y CSV.
- `src/data.js`: datos de demostración y almacenamiento local.
- `src/icons.js` y `src/icon-nodes.js`: iconos SVG editables mediante código.
- `src/contracts.d.ts`: tipos de datos y opciones de integración.
- `assets/photos/`: 3 imágenes PNG para publicaciones y comunidades.
- `assets/avatars/`: 6 retratos PNG con recorte circular transparente.
- `assets/brand/`: logotipo SVG de Klini.
- `assets/fonts/`: fuentes DejaVu Sans regular y bold.
- `references/`: propuestas de Comunidades y Calendario en PNG.
- `examples/KliniPanel.tsx`: ejemplo de montaje en React.
- `PROMPT_PARA_CODEX.md`: instrucción lista para adjuntar al proyecto.
- `docs/INTEGRACION.md`: montaje y conexiones con el sistema existente.
- `docs/VALIDACION.md`: alcance de las verificaciones y revisión pendiente.
- `licenses/`: licencias de fuentes e iconos.

## Alcance

La demo ejecuta las interacciones en el navegador con datos de ejemplo. Las publicaciones, historias, reportes, membresías y citas **no se envían a servidores**. No incluye autenticación, mensajería, notificaciones push ni sincronización entre usuarios. Las historias permanecen hasta restablecer la demo; el vencimiento y su almacenamiento se implementan en el backend.

Los enlaces compartidos apuntan a la instancia donde está abierta la demo. Un enlace `localhost` no es accesible desde otro dispositivo. La opción nativa de compartir depende del navegador; se ofrece copiar enlace como alternativa.

Las imágenes son recursos extraídos de las propuestas visuales. Textos, controles, estados, calendarios e iconos se construyen con código; la interfaz no es una captura plana. En la demo, la cita usa un nombre genérico de prueba. Las referencias PNG conservan el material proporcionado para el diseño.

## Verificación

Ejecuta `npm test` para las pruebas de lógica y generación de vistas. Consulta `docs/VALIDACION.md` antes de integrar en producción.
