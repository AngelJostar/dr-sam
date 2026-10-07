# Carruseles y Comunidades Klini

## Integración

- Carruseles compartidos con fondo azul profundo generado con Runway, tarjetas claras, selección turquesa y recursos originales del ZIP `Klini_Carrusel_Recursos_PNG.zip`.
- El arte de los PNG se encuadra con CSS; los títulos y el estado «En uso» son texto dinámico. Las rutas y los controladores de servicios existentes se conservan.
- Aplicado a Unidad, Institución, Área operativa, Consulta externa, Farmacia externa e Importación. Los filtros y carruseles compactos del paciente comparten los colores.
- Nuevo módulo **Administrador de Comunidades**, accesible desde Superadministrador y el selector de módulos para los perfiles `superadmin` y `admin`.
- Botón **Comunidades** en la quinta posición de la barra inferior del paciente, enlazado a `/communities`. El panel administrativo usa `/communities/admin` y requiere autorización.
- Interfaz de comunidades adaptada del ZIP entregado por el usuario, con paleta clara de blanco, menta y salvia.

## Alcance de los datos del módulo nuevo

El ZIP es una demostración frontend. Se integró con autenticación Laravel, pero sus comunidades, eventos, publicaciones y métricas continúan siendo datos ficticios almacenados en el navegador, separados por usuario y por vista. No se envían mensajes, invitaciones ni recordatorios reales. Esta condición aparece en ambas pantallas.

La persistencia compartida en la base de datos, la sincronización entre administradores y pacientes y los envíos reales requieren una implementación posterior; no están simulados como servicios activos.

## Archivos de esta entrega

- `config/drsam.php`, `routes/web.php`: registro del módulo y rutas protegidas.
- `app/Http/Controllers/Community/CommunityDashboardController.php`: acceso administrativo y vista del paciente.
- `resources/views/community/dashboard.blade.php`: pantalla clara y sesión Klini.
- `resources/views/patient/dashboard.blade.php`, `public/css/patient-bottom-nav-mobile.css`: botón Comunidades.
- `resources/views/superadmin/dashboard.blade.php`, `resources/views/dashboard.blade.php`: descripción del módulo.
- `resources/views/layouts/app.blade.php`: carga del sistema de carruseles.
- `public/js/klini-carousels.js`, `public/css/klini-carousels.css`: diseño, estados, indicadores y controles compartidos.
- `public/css/klini-system.css`, `public/css/buttons.css`: retiro de estilos anteriores del carrusel y compatibilidad con indicadores.
- `public/brand/klini/carousel/`: PNG originales y fondo de Runway.
- `public/vendor/klini-community/`: recursos del ZIP adaptados, aislados de las pantallas clínicas.
- `public/css/klini-community.css`, `public/js/klini-community.js`: apariencia e integración del módulo.
- `tests/Feature/CommunityModuleTest.php`: sesión, permisos, registro y desactivación del módulo.

## Verificación

- Suite Laravel: **179 pruebas aprobadas, 2,764 aserciones**.
- Navegador: acceso desde el recuadro de Superadministrador; botón Comunidades del paciente; cambio de servicio en Unidad; desplazamiento en Institución; presentación de Importación y Área operativa; creación y persistencia tras recarga de un evento ficticio.
- El navegador integrado mantuvo su viewport en 1280 × 720 aunque se solicitó una medida móvil. Los estilos adaptables están incluidos; queda pendiente su comprobación visual en un dispositivo móvil real.
