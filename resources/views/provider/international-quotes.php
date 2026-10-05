<?php

$items = [
    ['Global Pharma Solutions', '🇺🇸 Estados Unidos', 'Nivolumab', '10 mg/ml · 1 frasco 10 ml', 'USD', '$1,250.00', '7 días', '30 Nov 2026', '30 días', 'Activa'],
    ['MedSolutions GmbH', '🇩🇪 Alemania', 'Tocilizumab', '162 mg · 1 jeringa prellenada', 'EUR', '€980.00', '14 días', '15 Nov 2026', '60 días', 'Por vencer'],
    ['Nippon Medical Co.', '🇯🇵 Japón', 'Osimertinib', '80 mg · 30 tabletas', 'USD', '$520.00', '21 días', '20 Dic 2026', 'Pago anticipado', 'Aprobada'],
    ['BioGenix S.A.', '🇨🇭 Suiza', 'Trastuzumab', '150 mg · 1 frasco', 'USD', '$760.00', '10 días', '10 Oct 2026', '30 días', 'Rechazada'],
    ['HealthCore Ltd.', '🇬🇧 Reino Unido', 'Atezolizumab', '1200 mg · 1 frasco 20 ml', 'GBP', '£830.00', '12 días', '25 Nov 2026', '45 días', 'Activa'],
    ['PharmaTrade S.L.', '🇪🇸 España', 'Pembrolizumab', '100 mg · 1 frasco', 'EUR', '€910.00', '18 días', '05 Dic 2026', '60 días', 'Por vencer'],
    ['Korea Meditech', '🇰🇷 Corea del Sur', 'Bevacizumab', '400 mg · 1 frasco 16 ml', 'USD', '$540.00', '8 días', '28 Nov 2026', '30 días', 'Activa'],
    ['EuroHealth BV', '🇳🇱 Países Bajos', 'Adalimumab', '40 mg · 2 jeringas', 'EUR', '€700.00', '15 días', '01 Nov 2026', '60 días', 'Aprobada'],
];

return [
    'columns' => ['Folio', 'Proveedor', 'Medicamento', 'Presentación', 'Moneda', 'Precio unitario', 'Tiempo de entrega', 'Vigencia', 'Condición de pago', 'Estatus', 'Acción'],
    'rows' => collect($items)->map(function ($item, $index) {
        [$provider, $country, $medicine, $presentation, $currency, $price, $delivery, $validity, $terms, $status] = $item;
        return ['cells' => ['COT-2026-'.str_pad($index + 1, 3, '0', STR_PAD_LEFT), $provider, $medicine, $presentation, $currency, $price, $delivery, $validity, $terms, $status, 'Ver'],
            'country' => $country,
            'status' => match ($status) { 'Activa' => 'active', 'Aprobada' => 'approved', 'Por vencer' => 'expiring', default => 'rejected' }];
    }),
    'filters' => ['Todos', 'Activas', 'Por vencer', 'Aprobadas', 'Rechazadas'],
];
