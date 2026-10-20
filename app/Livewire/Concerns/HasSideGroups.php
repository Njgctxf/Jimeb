<?php

namespace App\Livewire\Concerns;

use App\Models\ModifierGroup;
use App\Support\ItemSideGroups;

/** Bloc « Ce plat est servi avec… » des formulaires de plat. */
trait HasSideGroups
{
    public array $sideGroups = [];

    public array $sideGroupChoices = [];

    public function mountHasSideGroups(): void
    {
        $this->sideGroupChoices = ItemSideGroups::choices();
    }

    public function addSideGroup(): void
    {
        $this->sideGroups[] = ItemSideGroups::blank();
    }

    public function reuseSideGroup($groupId): void
    {
        if (! $groupId || collect($this->sideGroups)->contains('id', (int) $groupId)) {
            return;
        }

        $group = ModifierGroup::with('options')->find($groupId);
        if ($group) {
            $this->sideGroups[] = ItemSideGroups::fromGroup($group);
        }
    }

    public function removeSideGroup(int $index): void
    {
        unset($this->sideGroups[$index]);
        $this->sideGroups = array_values($this->sideGroups);
    }

    public function addSideOption(int $group): void
    {
        $this->sideGroups[$group]['options'][] = ['name' => '', 'price' => ''];
    }

    public function removeSideOption(int $group, int $option): void
    {
        unset($this->sideGroups[$group]['options'][$option]);
        $this->sideGroups[$group]['options'] = array_values($this->sideGroups[$group]['options']);
    }
}
