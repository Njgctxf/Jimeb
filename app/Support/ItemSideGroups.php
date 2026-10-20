<?php

namespace App\Support;

use App\Models\ItemModifier;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;

/**
 * « Ce plat est servi avec… » : vue simplifiée des groupes d'options (modificateurs) d'un plat.
 * Un groupe existant est réutilisé tel quel ; seuls « obligatoire » et « plusieurs choix » sont propres au plat.
 */
class ItemSideGroups
{
    /** Groupes déjà créés, proposés pour réutilisation. */
    public static function choices(): array
    {
        return ModifierGroup::orderBy('id')->get()->map(fn (ModifierGroup $g) => [
            'id' => $g->id,
            'name' => $g->name,
        ])->all();
    }

    public static function blank(): array
    {
        return [
            'id' => null,
            'name' => '',
            'required' => true,
            'multiple' => false,
            'options' => [['name' => '', 'price' => '']],
        ];
    }

    public static function fromGroup(ModifierGroup $group, bool $required = true, bool $multiple = false): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'required' => $required,
            'multiple' => $multiple,
            'options' => $group->options->sortBy('sort_order')->map(fn ($o) => [
                'name' => $o->name,
                'price' => (float) $o->price,
            ])->values()->all(),
        ];
    }

    public static function load(MenuItem $item): array
    {
        return ItemModifier::with('modifierGroup.options')
            ->where('menu_item_id', $item->id)
            ->whereNull('menu_item_variation_id')
            ->get()
            ->filter(fn ($im) => $im->modifierGroup)
            ->map(fn ($im) => self::fromGroup($im->modifierGroup, (bool) $im->is_required, (bool) $im->allow_multiple_selection))
            ->values()
            ->all();
    }

    public static function sync(MenuItem $item, array $groups): void
    {
        $keepGroupIds = [];

        foreach ($groups as $data) {
            $group = ! empty($data['id']) ? ModifierGroup::find($data['id']) : self::createGroup($data);

            if (! $group) {
                continue;
            }

            $keepGroupIds[] = $group->id;

            ItemModifier::updateOrCreate(
                ['menu_item_id' => $item->id, 'modifier_group_id' => $group->id, 'menu_item_variation_id' => null],
                ['is_required' => (bool) ($data['required'] ?? false), 'allow_multiple_selection' => (bool) ($data['multiple'] ?? false)]
            );
        }

        ItemModifier::where('menu_item_id', $item->id)
            ->whereNull('menu_item_variation_id')
            ->whereNotIn('modifier_group_id', $keepGroupIds)
            ->delete();
    }

    private static function createGroup(array $data): ?ModifierGroup
    {
        $name = trim((string) ($data['name'] ?? ''));
        $options = collect($data['options'] ?? [])->filter(fn ($o) => trim((string) ($o['name'] ?? '')) !== '');

        if ($name === '' || $options->isEmpty()) {
            return null;
        }

        $locales = languages()->pluck('language_code')->all();

        $group = ModifierGroup::create([
            'name' => $name,
            'description' => '',
            'branch_id' => branch()->id,
        ]);

        $group->translations()->createMany(array_map(fn ($l) => ['locale' => $l, 'name' => $name, 'description' => ''], $locales));

        foreach ($options->values() as $index => $option) {
            $new = new ModifierOption();
            $new->modifier_group_id = $group->id;
            $new->setTranslations('name', array_fill_keys($locales, trim($option['name'])));
            $new->price = (float) ($option['price'] ?: 0);
            $new->is_available = true;
            $new->sort_order = $index;
            $new->save();
        }

        return $group;
    }
}
