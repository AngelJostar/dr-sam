# Portal del paciente: integración literal del ZIP

Fecha: 6 de octubre de 2026.
Origen: `patient-portal-source-2026-10-06.zip`, proporcionado por el usuario.

## Interfaz instalada

Se instalaron o verificaron 53 archivos idénticos byte por byte al paquete: vista principal, historial clínico, controlador del paciente, CSS, JavaScript e imágenes. El manifiesto original de 110 archivos se verificó antes de integrar; el inventario de archivos activos y sus SHA-256 está en `patient-portal-source-manifest.json`.

Inicio ahora muestra las historias y publicaciones del paquete. Comunidades, calendario, registro, dispositivos, perfil, seguro, recetas, análisis y médicos utilizan el código suministrado. La vista y los recursos se copiaron sin reinterpretar su diseño, modificar sus textos ni sustituir fotografías.

## Archivos y conexión con Laravel

- `resources/views/patient/dashboard.blade.php`: copia literal del portal adjunto.
- `resources/views/patient/partials/clinical-history.blade.php`: verificado idéntico.
- `app/Http/Controllers/Patient/PatientPortalController.php`: copia literal; únicamente difiere de la versión anterior en la retirada de un dato de presentación del calendario sustituido.
- `public/css/communities.css`, `public/css/patient-bottom-nav-mobile.css`, `public/js/communities.js` y `public/js/patient-profile-panel.js`: versiones adjuntas.
- `public/css/patient-portal-source.css`: copia literal de `public/css/drsam.css` del ZIP bajo otro nombre para aislarla del resto del sistema.
- `public/js/patient-portal-table-filters.js`: copia literal del script compartido del ZIP bajo otro nombre, por el mismo motivo.
- Los demás estilos, scripts e imágenes relacionados ya coincidían con el paquete y se verificaron por hash.
- `resources/views/layouts/app.blade.php`: selecciona los recursos originales únicamente en `patient.dashboard`. Los demás módulos conservan sus estilos Klini y sus filtros actuales. Se mantienen el título y favicon de la aplicación.
- `tests/Feature/PatientPortalTest.php`: expectativas adaptadas al inicio importado y al calendario del paquete, conservando comprobaciones de aislamiento por paciente y añadiendo cobertura del aislamiento de estilos.

Las rutas del paciente ya coincidían con las necesarias. Los modelos y servicios utilizados también coincidían. No se sustituyeron rutas globales, autenticación, el modelo compartido de usuarios ni Composer; no se ejecutaron migraciones ni se cambiaron datos.

El código anterior de la interfaz quedó respaldado en `tmp/patient-before-source-20261006-124252/`. El paquete extraído y sus referencias están en `tmp/patient-portal-source-20261006/`. El componente anterior de Comunidades y calendario permanece en disco, pero esta vista ya no lo carga.

## Verificación

- Manifiesto original: 110 archivos verificados; copias activas: 53 coincidencias exactas, sin diferencias.
- Todas las rutas literales a recursos locales encontradas en los archivos importados existen.
- Pruebas enfocadas de paciente: 13 aprobadas, 128 aserciones.
- Suite completa: 183 aprobadas, 2798 aserciones.
- Navegador: Inicio, Comunidades, detalle y regreso de comunidad, Registro, Mi salud, Dispositivos, menú médico y detalle de citas.
- Inicio revisado a 390 × 844: sin desbordamiento horizontal del documento. Se restauró el tamaño normal al terminar.
- Sin errores JavaScript observados durante esta revisión.
- Capturas: `patient-portal-source-home.png`, `patient-portal-source-communities.png` y `patient-portal-source-mobile.png`.

## Comportamiento conservado del paquete

El código incluye menciones visibles a «Dr. Sam», fotografías externas de Unsplash y ejemplos de publicaciones. Se conservaron para respetar la copia literal solicitada. Comunidades y parte del registro utilizan almacenamiento local del navegador; esta integración no añade sincronización entre dispositivos. Las citas, pólizas, perfil y adjuntos siguen usando los controladores y permisos de Laravel.
