<?php

// Inventory reviewed against the main klini_mobile project and its local reference app.
return [
    ['title' => 'Menús y navegación', 'section' => 'navigation', 'status' => 'Conectado', 'description' => 'Nombre, orden y visibilidad de los accesos inferiores y del menú de soporte.'],
    ['title' => 'Botones y textos', 'section' => 'texts', 'status' => 'Conectado', 'description' => 'Etiquetas de citas, calendario, póliza, billetera e interacciones del portal.'],
    ['title' => 'Formatos y mensajes', 'section' => 'forms', 'status' => 'Conectado', 'description' => 'Títulos, instrucciones y confirmaciones de citas y pólizas. Accesos a los formatos clínicos.'],
    ['title' => 'Usuarios y perfiles', 'section' => 'users', 'status' => 'Conectado', 'description' => 'Usuarios registrados en Klini, búsqueda y estado de cuenta. Los perfiles familiares locales aún no se sincronizan.'],
    ['title' => 'Médicos y terapeutas', 'section' => 'catalogs', 'status' => 'Conectado', 'description' => 'Directorio y datos profesionales. Las agendas y disponibilidades se administran en el módulo médico.', 'catalog' => 'doctors'],
    ['title' => 'Seguros y coberturas', 'section' => 'catalogs', 'status' => 'Conectado', 'description' => 'Aseguradoras, planes, primas, documentos, coberturas y publicación en Mi Seguro.', 'catalog' => 'insurance-carriers'],
    ['title' => 'Especialidades', 'section' => 'catalogs', 'status' => 'Catálogo', 'description' => 'Catálogo central de especialidades; algunas listas nativas aún están incluidas en la aplicación.', 'catalog' => 'specialties'],
    ['title' => 'Medicamentos', 'section' => 'catalogs', 'status' => 'Catálogo', 'description' => 'Catálogo de medicamentos utilizado por los servicios clínicos y las recetas.', 'catalog' => 'medications'],
    ['title' => 'Dispositivos médicos', 'section' => 'catalogs', 'status' => 'Parcial', 'description' => 'Catálogo central. Las tarjetas de dispositivos del portal aún requieren conexión con este catálogo.', 'catalog' => 'medical-devices'],
    ['title' => 'Comunidades, publicaciones y eventos', 'section' => 'communities', 'status' => 'Parcial', 'description' => 'Gestión de comunidades y eventos. El contenido social y parte de las reservas se conservan localmente; no existe todavía un catálogo remoto completo.', 'route' => 'community.dashboard'],
    ['title' => 'Billetera, tokens y suscripciones', 'section' => 'subscriptions', 'status' => 'Parcial', 'description' => 'Editor de planes del sistema. Los saldos, recompensas y compras de demostración de la app requieren sincronización antes de administrarlos centralmente.', 'catalog' => 'subscriptions'],
    ['title' => 'Publicidad y banners', 'section' => 'advertising', 'status' => 'Parcial', 'description' => 'Editor de publicidad del módulo médico. Los espacios del feed móvil todavía no consumen esta configuración.', 'catalog' => 'advertising'],
    ['title' => 'Notificaciones y dispositivos', 'section' => 'notifications', 'status' => 'Consulta', 'description' => 'Dispositivos registrados, plataforma, permiso registrado y última conexión. El envío de campañas no está conectado.'],
    ['title' => 'Parámetros corporales y Apple Salud', 'section' => 'health', 'status' => 'Local', 'description' => 'Mediciones, favoritos e historial del módulo nativo son locales. Apple Salud requiere permiso del usuario; sus lecturas no se envían a este panel.'],
    ['title' => 'Archivo clínico y recetas', 'section' => 'health', 'status' => 'Parcial', 'description' => 'El historial, los documentos y las recetas del paciente usan Laravel. El archivo de la app de referencia mantiene datos separados.', 'catalog' => 'patients'],
    ['title' => 'Pantallas nativas de paciente y médico', 'section' => 'native', 'status' => 'Parcial', 'description' => 'Perfil, citas, recetas, documentos, agenda médica, notas y solicitudes ya tienen servicios. Sus títulos y componentes nativos requieren una actualización de la app.'],
    ['title' => 'Teleconsulta y Klini IA', 'section' => 'native', 'status' => 'Local', 'description' => 'Flujos presentes en la aplicación de referencia. No hay un servicio central de teleconsulta ni una suscripción de IA conectados a este panel.'],
];
