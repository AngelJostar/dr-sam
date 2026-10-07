# Validación del paquete

## Ejecutado

- Comprobación de sintaxis de los módulos JavaScript con Node.js.
- 13 pruebas automatizadas, todas aprobadas: calendario de julio de 2026, navegación entre meses y años, fecha local, límites horarios, duración, empalmes, consultorios distintos, cancelaciones, resumen, likes, filtros, persistencia, exportación CSV, escape de texto y generación de las vistas.
- Verificación de que el feed incluye las categorías y acciones solicitadas.
- Verificación de identificadores distintos para los formularios de comentarios del feed y del diálogo.
- Revisión visual de los recursos de fotografía y avatares extraídos de las propuestas.
- Comprobación de referencias locales y entrega de archivos por el servidor de desarrollo.
- Verificación de integridad del ZIP.

## Pendiente en el proyecto de destino

No se ejecutaron pruebas de navegador ni una comparación visual de la demo renderizada: este entorno no dispone de un navegador instalado. Las pruebas de generación de vistas inspeccionan HTML, no sustituyen una prueba interactiva en navegador. El adaptador de React es un ejemplo de integración y no se compiló dentro de un proyecto React.

Antes de integrar, abrir la demo y revisar escritorio y móvil, foco y teclado, diálogos, contraste, tipografía, recorte de fotografías, scroll horizontal y posición de la navegación inferior. Verificar también carga de archivos, cuota de almacenamiento y compartir en los navegadores objetivo.

Las pruebas remotas, autorización, sincronización, concurrencia real de reservas y moderación requieren el backend del proyecto. El bloqueo de empalmes de la demo es local y no resuelve la concurrencia entre usuarios.
