# Klini Wellness - Panel de administrador de comunidades

Este paquete contiene el panel administrativo de comunidades Klini Wellness listo para integrarse en otro proyecto.

## Archivos incluidos

- `comunidad-admin.html`: pantalla principal standalone del panel.
- `paciente-wellness.js`: logica del panel, datos demo, flujos de mensajes, solicitudes, eventos, perfil del creador y administracion de comunidad.
- `paciente-wellness.css`: estilos especificos del modulo Wellness y del panel administrativo.
- `paciente.css`: estilos base requeridos por la maqueta.

## Uso rapido

1. Copia estos archivos dentro de la misma carpeta en tu nuevo proyecto.
2. Abre `comunidad-admin.html` o enlazalo desde tu sistema.
3. Si cambias la ubicacion de los archivos CSS o JS, actualiza las rutas en el `<head>` y antes del cierre de `</body>`.

## Punto de entrada

El panel se inicializa desde `comunidad-admin.html` con:

```html
window.openCommunityAdminPanel?.("community-yoga-mente");
```

Puedes cambiar `community-yoga-mente` por el identificador real de la comunidad cuando lo conectes con tu backend.

## Integracion sugerida

- Reemplaza los datos demo en `paciente-wellness.js` por respuestas de tu API.
- Conserva los atributos `data-wellness-community-action`, porque controlan los flujos de botones, modales y desplegables.
- El perfil del creador usa `localStorage` para la demo. En produccion, guarda esos datos en el perfil del administrador.
- Las imagenes editables de comunidad, logotipo, eventos y perfil pueden conectarse a tu servicio de carga de archivos.

## Funciones incluidas

- Dashboard de comunidad.
- KPIs compactos y graficas.
- Gestion de miembros.
- Solicitudes pendientes con perfil e Instagram.
- Eventos y creacion de eventos.
- Publicaciones.
- Calendario.
- Analytics.
- Configuracion.
- Automatizaciones.
- Gamificacion.
- Mensajes recibidos con ventana de conversacion.
- Notificaciones de solicitudes.
- Perfil editable del creador de comunidad.
