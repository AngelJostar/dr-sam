<?php

if ($panelTitle === 'Expedientes') {
    return ['columns' => ['Institución', 'Hospital', 'Paciente', 'Folio/Contrato', 'Proveedor', 'Medicamento', 'Etapa', 'Poder (Cofepris)', 'Carta Motivo (Cofepris)', 'Permiso Cofepris', 'Agente aduanal', 'Documento aduanal', 'Abrir', 'Archivos editables Cofepris', 'Archivos editables Agencia Aduanal'],
        'filters' => ['Todos', 'En documentación', 'Permiso Cofepris', 'En tránsito', 'Entregados'],
        'rows' => collect([
            ['IMP-2026-001', 'Chongqing ISA', 'Tocilizumab', 'Permiso Cofepris', '30/09/2026', 'Ver detalle'],
            ['IMP-2026-002', 'BioPharma Ltd.', 'Asfotasa alfa', 'En tránsito', '29/09/2026', 'Ver detalle'],
            ['IMP-2026-003', 'MediGlobal', 'Cannabidiol', 'En documentación', '28/09/2026', 'Ver detalle'],
            ['IMP-2026-004', 'PharmaCore', 'Nivolumab', 'En revisión', '27/09/2026', 'Ver detalle'],
            ['IMP-2026-005', 'OncoSource', 'Pembrolizumab', 'Entregado', '26/09/2026', 'Ver detalle'],
        ])->map(fn ($cells) => ['cells' => ['Pendiente', 'Pendiente', 'Pendiente', ...array_slice($cells, 0, 4), 'Editar poder', 'Editar carta motivo', 'Pendiente', 'Pendiente', 'Ver documento', 'Abrir', 'Editar cartas', 'Editar cartas'], 'status' => match ($cells[3]) { 'Permiso Cofepris' => 'cofepris', 'En tránsito' => 'transit', 'En documentación' => 'documentation', 'Entregado' => 'delivered', default => 'pending' }]),
    ];
}

if ($panelTitle === 'Trámites') {
    return ['columns' => ['Expediente', 'Tipo de trámite', 'COFEPRIS #', 'Fecha envío', 'Estatus', 'Acciones'],
        'filters' => ['Todos', 'En proceso', 'Autorizados', 'Rechazados', 'Vencidos'],
        'rows' => collect([
            ['IMP-2026-001', 'Permiso de importación', '24330051234', '15/09/2026', 'Autorizado', 'Ver detalle'],
            ['IMP-2026-002', 'Registro sanitario', '24330051235', '12/09/2026', 'En proceso', 'Ver detalle'],
            ['IMP-2026-003', 'Modificación', '24330051236', '10/09/2026', 'En revisión', 'Ver detalle'],
            ['IMP-2026-004', 'Prórroga', '24330051237', '05/09/2026', 'Rechazado', 'Ver detalle'],
            ['IMP-2026-005', 'Aviso de importación', '24330051238', '01/09/2026', 'Autorizado', 'Ver detalle'],
        ])->map(fn ($cells) => ['cells' => $cells, 'status' => match ($cells[4]) { 'Autorizado' => 'authorized', 'Rechazado' => 'rejected', default => 'pending' }]),
    ];
}

if ($panelTitle === 'Pacientes') {
    return ['columns' => ['Nombre', 'CURP', 'Fecha nac.', 'Hospital', 'Expedientes', 'Acciones'],
        'filters' => ['Todos', 'Activos', 'Inactivos'],
        'rows' => collect([
            ['María López Hernández', 'Sin registrar', '15/01/1964', 'Hospital General', 3, 'Ver detalle'],
            ['Jorge Martínez Ruiz', 'Sin registrar', '08/03/1982', 'Hospital Ángeles', 2, 'Ver detalle'],
            ['Ana Torres García', 'Sin registrar', '01/12/1974', 'Hospital ABC', 1, 'Ver detalle'],
            ['Carlos Pérez Luna', 'Sin registrar', '22/06/1965', 'Hospital 20 Nov.', 1, 'Ver detalle'],
            ['Valeria López Guzmán', 'Sin registrar', '28/03/1968', 'Hospital General', 2, 'Ver detalle'],
        ])->map(fn ($cells) => ['cells' => $cells, 'status' => 'active']),
    ];
}

if ($panelTitle === 'Embarques') {
    return [
        'columns' => ['Expediente', 'Transportista', 'Origen', 'Destino', 'Estatus', 'Fecha estimada'],
        'filters' => ['Todos', 'En tránsito', 'En aduana', 'En entrega', 'Entregados'],
        'rows' => collect([
            ['IMP-2026-002', 'DHL', 'Beijing', 'CDMX', 'En tránsito', '02/10/2026'],
            ['IMP-2026-003', 'FedEx', 'Basel', 'CDMX', 'En aduana', '01/10/2026'],
            ['IMP-2026-006', 'UPS', 'New Jersey', 'CDMX', 'En tránsito', '04/10/2026'],
            ['IMP-2026-007', 'Kuehne+Nagel', 'Frankfurt', 'CDMX', 'En entrega', '30/09/2026'],
            ['IMP-2026-008', 'DHL', 'London', 'CDMX', 'Entregado', '28/09/2026'],
        ])->map(fn ($cells) => ['cells' => $cells, 'status' => match ($cells[4]) {
            'En tránsito' => 'transit', 'En aduana' => 'customs', 'En entrega' => 'in_route', default => 'delivered',
        }]),
    ];
}

return [
    'columns' => ['Nombre', 'Correo', 'Rol', 'Estatus', 'Último acceso', 'Acciones'],
    'filters' => ['Todos', 'Activos', 'Inactivos'],
    'rows' => collect([
        ['Ana Torres', 'ana.torres@empresa.com', 'Administrador', 'Activo', '30/09/2026 10:24', 'Ver detalle'],
        ['Luis Hernández', 'luis.hernandez@empresa.com', 'Regulatorio', 'Activo', '30/09/2026 09:15', 'Ver detalle'],
        ['María González', 'maria.gonzalez@empresa.com', 'Logística', 'Activo', '29/09/2026 16:40', 'Ver detalle'],
        ['Carlos Rivas', 'carlos.rivas@empresa.com', 'Consultor', 'Inactivo', '20/09/2026 11:02', 'Ver detalle'],
        ['Sofía Méndez', 'sofia.mendez@empresa.com', 'Captura', 'Activo', '30/09/2026 08:10', 'Ver detalle'],
    ])->map(fn ($cells) => ['cells' => $cells, 'status' => $cells[3] === 'Activo' ? 'active' : 'inactive']),
];
