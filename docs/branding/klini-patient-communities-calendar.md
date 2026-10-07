# Comunidades y calendario del paciente

Implementado el 6 de octubre de 2026 a partir de `Klini_Comunidades_Calendario_Codex.zip`.

## Resultado

- Comunidades dentro de `/patient`, accesible desde la navegación inferior y `/patient?view=communities`.
- Feed claro, avatares e historias, categorías, exploración, membresías, imágenes deslizables, comentarios, reacciones, guardados y publicaciones locales del paquete.
- Acceso a los mensajes ya existentes del perfil; pestañas para alternar entre Comunidades y Mi calendario.
- Calendario en `/patient?view=calendar`, con Día/Semana/Mes/Lista, filtros, selector de fecha, resumen, CSV y detalle de citas.
- El calendario recibe exclusivamente las citas del paciente resuelto por el controlador. Conserva sus estados y muestra horarios fuera del rango de demostración original. Agendar abre el flujo existente de médicos, que valida y guarda en el servidor.
- El listado anterior y el historial clínico permanecen accesibles en «Mis citas e historial de consultas».
- Encabezado ajustado en estas dos vistas para que el logotipo no desplace ni cubra los controles.

## Archivos

- `resources/views/patient/dashboard.blade.php`: montaje, datos seguros, navegación y enlace con el perfil actual.
- `resources/views/patient/partials/wellness-navigation.blade.php`: navegación compartida.
- `app/Support/PatientCalendar.php` y `app/Http/Controllers/Patient/PatientPortalController.php`: datos de presentación de las citas del paciente.
- `public/js/klini-patient-workspace.js`: adaptación del componente al portal, calendario real y persistencia local de la demostración social.
- `public/css/klini-patient-workspace.css` y `public/css/klini-patient-component.css`: estilos aislados y adaptación móvil.
- `public/vendor/klini-patient/`: código y recursos adjuntos, con sus licencias y procedencia. El constructor evita leer el almacenamiento global de la demo; las URL de imágenes se validan y escapan.
- `tests/Feature/PatientPortalTest.php` y `tests/Frontend/PatientWorkspace.test.mjs`: aislamiento de pacientes, agenda vacía, horarios, navegación y exclusión de citas de la persistencia social.

## Recursos

Las fotografías y avatares originales provienen del ZIP del usuario. La imagen adicional `public/vendor/klini-patient/assets/photos/caminata-runway.png` se generó con Runway, tarea `3a9f7650-0238-45d9-95f2-6d04d5d3d5a5`, para la historia Klini y «Caminemos juntos». Se conserva localmente, sin depender de enlaces temporales. Representa personas ficticias.

## Verificación

- Laravel: 181 pruebas aprobadas, 2778 aserciones.
- JavaScript: 5 pruebas aprobadas.
- Compilación de vistas Blade y revisión de espacios: correctas.
- Navegador: vistas Día/Semana/Mes/Lista; detalle de cita real de demostración; agendamiento abre Médicos y terapeutas; mensajes abre el panel existente; carrusel de fotografías; comentarios; reacción persistente tras recargar y restaurada a su valor original.
- Revisión visual en escritorio y a 390 × 844; sin desbordamiento horizontal del documento en las dos secciones. El calendario conserva desplazamiento interno para tablas amplias.
- Capturas: `klini-patient-calendar-redesign.png` y `klini-patient-communities-redesign.png`.

## Alcance de los datos

Comunidades conserva el comportamiento local del paquete adjunto. Un aviso visible aclara que publicaciones, membresías, comentarios e historias son de demostración y se guardan solo en ese navegador, separados por usuario y perfil. No se sincronizan con el administrador de comunidades ni con otros dispositivos. Los datos locales anteriores se conservan bajo sus claves originales.

Las citas clínicas no se sustituyen por citas ficticias ni se guardan en localStorage. No se modificaron las rutas, permisos, tablas ni el servicio de agendamiento. La administración de comunidades existente permanece sin cambios.
