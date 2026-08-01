<?php

return [
    'appointment' => [
        'scheduled' => ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'],
        'confirmed' => ['confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'],
        'in_progress' => ['in_progress', 'completed', 'cancelled'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
        'no_show' => ['no_show'],
    ],
    'provider_request' => [
        'draft' => ['draft', 'requested', 'cancelled'],
        'requested' => ['requested', 'accepted', 'preparing', 'in_route', 'rejected', 'cancelled'],
        'accepted' => ['accepted', 'preparing', 'in_route', 'rejected', 'cancelled'],
        'preparing' => ['preparing', 'in_route', 'rejected', 'cancelled'],
        'in_route' => ['in_route', 'delivered', 'cancelled'],
        'delivered' => ['delivered'],
        'rejected' => ['rejected'],
        'cancelled' => ['cancelled'],
    ],
    'patient_order' => [
        'created' => ['created', 'received', 'preparing', 'cancelled'],
        'received' => ['received', 'preparing', 'cancelled'],
        'preparing' => ['preparing', 'in_route', 'cancelled'],
        'in_route' => ['in_route', 'delivered', 'cancelled'],
        'delivered' => ['delivered'],
        'cancelled' => ['cancelled'],
    ],
    'delivery_route' => [
        'planned' => ['planned', 'assigned', 'cancelled'],
        'assigned' => ['assigned', 'picked_up', 'in_route', 'delivered', 'failed', 'cancelled'],
        'picked_up' => ['picked_up', 'in_route', 'delivered', 'failed', 'cancelled'],
        'in_route' => ['in_route', 'delivered', 'failed', 'cancelled'],
        'delivered' => ['delivered'],
        'failed' => ['failed'],
        'cancelled' => ['cancelled'],
    ],
];
