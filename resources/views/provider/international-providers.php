<?php
  // Reference records are presentation-only demo data, never persisted as real providers.
  $internationalProviders = [
    ['Global Pharma Solutions', '🇺🇸 Estados Unidos', 'Biotecnológicos', 'John Smith', 'Director Comercial', 'john.smith@gps.com', '+1 305 555 0101', '24 h', 'Activo', '4.8', 5, '28 Sep 2026 10:24'],
    ['MedSolutions GmbH', '🇩🇪 Alemania', 'Oncológicos', 'Anna Müller', 'Account Manager', 'anna.muller@medsolutions.de', '+49 30 5555 7788', '48 h', 'Preferente', '4.6', 3, '27 Sep 2026 16:30'],
    ['Nippon Medical Co.', '🇯🇵 Japón', 'Inmunológicos', 'Hiroshi Tanaka', 'Gerente Internacional', 'h.tanaka@nippon-med.co.jp', '+81 3 5550 2211', '12 h', 'Activo', '4.9', 8, '29 Sep 2026 09:15'],
    ['BioGenix S.A.', '🇨🇭 Suiza', 'Terapias avanzadas', 'Sophie Dubois', 'Sales Director', 's.dubois@biogenix.ch', '+41 22 501 7788', '72 h', 'En validación', '4.2', 2, '25 Sep 2026 14:20'],
    ['HealthCore Ltd.', '🇬🇧 Reino Unido', 'Equipos médicos', 'James Wilson', 'Business Development', 'j.wilson@healthcore.co.uk', '+44 20 7946 0958', '24 h', 'Activo', '4.5', 4, '28 Sep 2026 11:35'],
    ['PharmaTrade S.L.', '🇪🇸 España', 'Genéricos', 'Laura Fernández', 'Key Account', 'l.fernandez@pharmatrade.es', '+34 91 123 4567', '48 h', 'Riesgo documental', '3.8', 1, '22 Sep 2026 12:45'],
    ['Korea Meditech', '🇰🇷 Corea del Sur', 'Dispositivos médicos', 'Min-Jae Park', 'Regional Manager', 'mj.park@koreameditech.kr', '+82 2 555 0199', '36 h', 'Activo', '4.7', 6, '29 Sep 2026 08:50'],
    ['EuroHealth BV', '🇳🇱 Países Bajos', 'Vacunas', 'Peter van Dijk', 'Commercial Director', 'p.vandijk@eurohealth.nl', '+31 20 808 7711', '24 h', 'Preferente', '4.4', 7, '26 Sep 2026 17:10'],
  ];
  $columns = ['Proveedor', 'País', 'Categorías', 'Contacto principal', 'Correo', 'Teléfono', 'Tiempo de respuesta', 'Estatus documental', 'Evaluación', 'Órdenes abiertas', 'Última actualización', 'Acción'];
  $rows = collect($internationalProviders)->map(function ($item, $index) {
    [$name, $country, $category, $contact, $job, $email, $phone, $response, $status, $rating, $orders, $updated] = $item;
    return ['cells' => [$name, $country, $category, $contact, $email, $phone, $response, $status, $rating, $orders, $updated, 'Abrir'],
      'code' => 'PROV-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT), 'job' => $job,
      'status' => match ($status) { 'Activo' => 'active', 'Preferente' => 'preferred', 'En validación' => 'pending', default => 'document_risk' }];
  });
  $filterLabels[$importSection] = ['Todos', 'Activos', 'Preferentes', 'En validación', 'Riesgo documental'];
return ['columns' => $columns, 'rows' => $rows, 'filters' => $filterLabels[$importSection]];
