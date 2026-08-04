# Entrega: Comunidad publica y cerrada

Este paquete contiene el codigo necesario para la seccion de comunidad publica/cerrada del modulo Wellness de Dr. Sam.

## Archivos incluidos

- `codigo/comunidad-publica.html`: entrada HTML de la vista publica de comunidad.
- `codigo/comunidad-publica.css`: estilos completos del panel publico.
- `codigo/comunidad-publica.js`: comportamiento, renderizado, carruseles, filtros, publicaciones, miembros, eventos y reserva.
- `codigo/paciente-wellness.js`: datos, estado local, comunidades abiertas/cerradas, acciones publicas y modulo administrador.
- `codigo/paciente-wellness.css`: estilos del modulo Wellness/admin que acompana la gestion.
- `codigo/comunidad-admin.html`: entrada del panel de administracion de comunidad.

## Comunidad publica de ejemplo

La comunidad publica principal es:

```txt
community-yoga-mente
```

Nombre visible:

```txt
Respira y Avanza
```

Se abre con:

```txt
comunidad-publica.html?community=community-yoga-mente
```

En `paciente-wellness.js`, el objeto vive dentro de `communitySeed()`:

```js
{
  id: "community-yoga-mente",
  name: "Respira y Avanza",
  slug: "respira-y-avanza",
  accessType: "open",
  visibility: "public",
  memberCount: 128,
  allowMemberPosts: true
}
```

## Comunidad cerrada de ejemplo

La comunidad cerrada de ejemplo es:

```txt
community-running-cardio
```

Nombre visible:

```txt
Ritmo Cardiometabolico
```

Se abre con:

```txt
comunidad-publica.html?community=community-running-cardio
```

En `paciente-wellness.js`, el objeto usa:

```js
{
  id: "community-running-cardio",
  name: "Ritmo Cardiometabolico",
  accessType: "closed",
  visibility: "public"
}
```

Cuando `accessType` es `closed`, el boton publico envia solicitud al administrador en lugar de unir al usuario directamente.

## Conexion entre vista publica y modulo comunidad

La vista publica consume el puente global:

```js
window.KliniWellnessCommunity = {
  defaultCommunityId: COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID,
  getPublicPayload: publicCommunityPayload,
  performPublicAction: publicCommunityAction
};
```

Funciones importantes:

- `publicCommunityPayload(communityId)`: arma los datos visibles de la comunidad.
- `publicCommunityAction(action, payload)`: ejecuta acciones como unirse, solicitar acceso, reservar eventos y crear publicaciones.
- `renderHero(payload)`: banner principal.
- `renderTabs(payload)`: botones de seccion.
- `renderContent(payload)`: orden general de secciones.
- `renderClasses()`: carrusel/lista de clases.
- `renderEvents(payload)`: eventos, filtros y reserva.
- `renderMembers(payload)`: carrusel y listado de miembros.
- `renderPosts(payload)`: publicaciones recientes.

## Nota tecnica

Este prototipo usa `localStorage` para persistencia local. Para produccion, conectar `publicCommunityPayload` y `publicCommunityAction` a endpoints reales del backend.
