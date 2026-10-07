# Portal del paciente - codigo fuente

Copia del modulo de paciente de Dr. Sam (Laravel 12), preparada el 2026-10-06.
Los archivos conservan sus rutas relativas para facilitar la integracion. Este
paquete no es una aplicacion Android/iOS ni un proyecto Laravel independiente.

## Contenido

- `resources/views/patient/`: portal completo y parcial del historial clinico.
- `resources/views/layouts/app.blade.php`: layout compartido que carga el CSS base.
- `public/css/`, `public/js/` y `public/images/`: estilos, interacciones, iconos
  e imagenes locales. El perfil carga dinamicamente
  `subscriptions-mobile-pan.css` y `subscriptions-mobile-pan.js`; ambos estan
  incluidos.
- `app/Http/Controllers/Patient/PatientPortalController.php`: perfil, seguro,
  agenda, adjuntos y datos de la pantalla.
- `app/Services/`, `app/Models/`, `app/Enums/` y
  `app/Http/Middleware/EnsureUserRole.php`: dependencias de referencia.
- `database/migrations/`: esquema relacionado. **No ejecutar estas migraciones
  sin revisar las dependencias y el esquema de la aplicacion destino.**
- `routes/patient.php`: rutas aisladas del modulo, listas para adaptar.
- `routes/web.php`, `composer.json` y `tests/Feature/PatientPortalTest.php`:
  referencias del proyecto original.

## Integracion en otra aplicacion Laravel

1. Usa PHP 8.2+ y Laravel 12. Copia los archivos de interfaz manteniendo las
   rutas bajo `resources/` y `public/`. `public/css/drsam.css` es un archivo
   compartido por todo Dr. Sam; en otra app conviene aislar sus reglas de
   paciente para evitar conflictos con otros estilos.
2. Incorpora las rutas de `routes/patient.php` a tu archivo de rutas. La app
   destino debe tener autenticacion, las rutas con nombre `login` y `logout`,
   y un middleware `role` equivalente. El ejemplo de middleware esta incluido;
   registra su alias en `bootstrap/app.php` de la app destino.
3. Adapta los modelos y tablas a tu esquema. El controlador espera un usuario
   autenticado con relacion `patient`, y usa citas, medicos, unidades, registros
   clinicos, documentos, recetas y polizas. Las migraciones son referencias,
   no un instalador autonomo.
4. Configura el disco `local` para adjuntos privados. La carga y descarga de
   registro rapido requieren sesion, CSRF y autorizacion por paciente. Algunas
   vistas de documentos existentes usan URLs `storage/`; revisa el disco
   correspondiente y la politica de acceso antes de exponer esos archivos.
5. Ejecuta `php artisan test tests/Feature/PatientPortalTest.php` tras adaptar
   factories y esquema. En el repositorio origen, la suite completa paso con
   140 pruebas el 2026-10-06.

## Integracion movil

- En WebView, sirve `/patient` desde un backend HTTPS con sesion autenticada.
  Habilita cookies, CSRF, selector de archivos y permisos de camara/galeria.
- Para una interfaz nativa, usa las vistas y recursos como referencia y lleva
  el controlador a una API autenticada. Blade no funciona como HTML local.
- El perfil, registro rapido y Comunidades usan `localStorage`/`sessionStorage`
  para parte de su estado. No equivalen a datos clinicos sincronizados.

## Limites y privacidad

Home muestra publicaciones de ejemplo definidas en `public/js/communities.js`.
El repositorio no incluye un backend de publicaciones de usuarios. Algunas
fotografias del feed usan URLs externas de Unsplash; no viajan en este ZIP y
deben reemplazarse o revisarse antes de distribuir la app.

No se incluyen `.env`, credenciales, sesiones, bases de datos, `vendor/`,
archivos cargados por pacientes ni registros clinicos reales. No copies datos
productivos a la nueva aplicacion como parte de esta integracion.
