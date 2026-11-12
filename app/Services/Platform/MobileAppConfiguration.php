<?php

namespace App\Services\Platform;

use App\Models\PlatformModule;

class MobileAppConfiguration
{
    public function read(): array
    {
        $settings = PlatformModule::query()->where('key', 'patient')->value('settings') ?? [];
        $stored = $settings['mobile_ui'] ?? [];
        $values = ['revision' => (int) ($settings['mobile_ui_revision'] ?? 0), 'updated_at' => $settings['mobile_ui_updated_at'] ?? null];

        foreach (config('klini-mobile.navigation') as $key => $item) {
            $values['navigation'][$key] = array_replace($item, array_intersect_key($stored['navigation'][$key] ?? [], array_flip(['label', 'visible', 'order'])));
        }
        // Inicio is always available, including with older stored configurations.
        $values['navigation']['home']['visible'] = true;
        foreach (['texts', 'forms'] as $group) {
            foreach (config('klini-mobile.'.$group) as $key => $field) {
                $values[$group][$key] = $stored[$group][$key] ?? $field['default'];
            }
        }

        return $values;
    }

    public function navigation(array $values, string $group): array
    {
        return collect($values['navigation'])->filter(fn ($item) => $item['group'] === $group && $item['visible'])->sortBy('order')->all();
    }
}
