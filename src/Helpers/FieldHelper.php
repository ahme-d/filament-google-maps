<?php

namespace Cheesegrits\FilamentGoogleMaps\Helpers;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;

class FieldHelper
{
    public static function getTopComponent(Component $component): Component
    {
        $parentComponent = $component->getContainer()->getParentComponent();

        return $parentComponent ? static::getTopComponent($parentComponent) : $component;
    }

    public static function getFlatFields($topComponent): array
    {
        $flatFields = $topComponent->getContainer()->getFlatFields();

        foreach ($topComponent->getContainer()->getComponents() as $component) {
            foreach ($component->getChildComponentContainers() as $container) {
                if ($container->isHidden()) {
                    continue;
                }

                $flatFields = array_merge($flatFields, $container->getFlatFields());
            }
        }

        return $flatFields;
    }

    public static function getFieldId(string $field, Component $component): ?string
    {
        $topComponent = self::getTopComponent($component);
        $flatFields   = static::getFlatFields($topComponent);
        $flatFields = collect($flatFields)
            ->whereInstanceOf(Field::class)->keyBy(fn($field) => $field->getName());

        if ($flatFields->has($field)) {
            $fieldComponent = $flatFields->get($field);
            $statePath = $fieldComponent->getStatePath();

            // In Filament v4, the DOM id typically has 'data.' prefix but getStatePath() might not include it
            // Try to get the actual ID if available
            if (method_exists($fieldComponent, 'getId')) {
                try {
                    $id = $fieldComponent->getId();
                    if (! empty($id)) {
                        return $id;
                    }
                } catch (\Throwable $e) {
                    // getId() might throw if container not initialized, continue to fallback
                }
            }

            // Fallback: If statePath doesn't already have data prefix and looks like it needs one
            if (! str_starts_with($statePath, 'data.') && ! str_contains($statePath, '.')) {
                return 'data.' . $statePath;
            }

            return $statePath;
        }

        return null;
    }
}
