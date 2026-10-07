# Instrucción de integración para Codex

Integra en mi proyecto actual de Klini las secciones **Comunidades** y **Calendario de consulta externa** usando el código y los recursos de esta carpeta.

Primero revisa el repositorio, sus instrucciones, framework, rutas, autenticación, componentes, fuentes, permisos y servicios de datos existentes. Identifica la pantalla actual de Comunidades y la sección del calendario. Trabaja sobre esas pantallas y conserva las funciones que ya existen.

Usa `references/comunidades.png` y `references/calendario.png` como referencias de diseño. Mantén fondos blancos y menta clara, texto azul marino, acentos turquesa y la identidad de Klini. Conserva la navegación principal del proyecto, evitando duplicar el encabezado y la barra inferior de la demo.

## Comunidades

- Implementa la distribución de un feed social fotográfico tipo Instagram con identidad Klini.
- Arriba, carrusel horizontal de avatares/historias de contactos y botón para añadir una historia.
- Botones: Mis comunidades, Comunidades Wellness, Comunidades Sport y Nuevas comunidades.
- Botón Explorar comunidades y flujos para descubrir comunidades y unirse a ellas.
- Feed vertical con autor, comunidad, fecha/hora, fotografía, carrusel por publicación y menú de opciones.
- Likes, comentarios, compartir y guardar con estados y contadores reales.
- Alta de publicaciones con fotografías. Conecta moderación, reportes y ocultamiento con las capacidades existentes.
- Adapta `assets/photos` y `assets/avatars` para la demo; en producción utiliza imágenes y perfiles devueltos por el backend.

## Calendario

- Vistas Día, Semana, Mes y Lista; calendario mensual auxiliar con selección de mes y año.
- Navegación por fecha, botón Hoy, filtros de especialidad y consultorio.
- Bloques horarios, tarjetas de cita, estados, resumen del día y reporte.
- Formulario de nueva cita con fecha, hora, duración, paciente, consultorio y especialidad.
- Respeta el modelo real de pacientes: vincula IDs y búsqueda de pacientes existentes.
- Valida empalmes por consultorio en el servidor, de forma atómica, además de la respuesta inmediata del frontend. Integra también las restricciones existentes de médico, sede y disponibilidad.
- Usa la zona horaria configurada de cada sede; en esta demo las fechas y horas son locales y no se envían a un backend.

## Implementación

1. Puedes portar las vistas al framework del repositorio o montar el componente con `mountKlini`. Consulta `docs/INTEGRACION.md` y `examples/KliniPanel.tsx`.
2. Reutiliza los estilos, los colores, las fuentes, el logo, los iconos y las imágenes incluidos. Mantén el texto como texto editable y los controles como elementos interactivos; no conviertas las pantallas en una sola imagen.
3. Sustituye los datos y `localStorage` de la demo por los servicios existentes. No guardes datos clínicos reales ni tokens de autenticación en el adaptador de demostración.
4. Conserva las credenciales, roles, permisos y rutas existentes. No inventes endpoints como si ya estuvieran implementados. Documenta cualquier servicio faltante y prepara el cambio necesario para revisión.
5. Integra las acciones Home, Dispositivos, Registro y Mi salud con la navegación actual.
6. Implementa estados de carga, vacío y error; reintentos y reversión de cambios optimistas cuando falle una operación remota.
7. Comprueba el resultado en navegador a 390, 768 y 1440 px, con teclado y lectores de pantalla. Prueba formularios, filtros, persistencia remota, autorización, empalmes concurrentes y acciones del feed.
8. Entrega un resumen de los archivos modificados, conexiones realizadas, pruebas ejecutadas y servicios pendientes. No publiques ni despliegues automáticamente.

La demo no contiene backend ni autenticación. `docs/VALIDACION.md` indica qué se verificó en el paquete y qué debe comprobarse en el navegador del proyecto.
