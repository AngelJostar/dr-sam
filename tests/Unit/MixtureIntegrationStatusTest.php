<?php

namespace Tests\Unit;

use App\Support\MixtureIntegrationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MixtureIntegrationStatusTest extends TestCase
{
    #[DataProvider('statuses')]
    public function test_it_uses_the_cbta_visible_vocabulary(string $status, string $label): void
    {
        $this->assertSame($label, MixtureIntegrationStatus::label($status));
    }

    public static function statuses(): array
    {
        return [
            ['requested', 'Pendiente'],
            ['accepted', 'Aprobada'],
            ['dispensed', 'Dispensada'],
            ['preparing', 'Preparada'],
            ['ready', 'Inspeccionada'],
            ['in_route', 'En ruta'],
            ['delivered', 'Entregada'],
            ['rejected', 'No aprobada'],
            ['cancelled', 'Cancelada'],
        ];
    }
}
