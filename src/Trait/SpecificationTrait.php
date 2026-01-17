<?php

declare(strict_types=1);

namespace App\Trait;

use App\Enum\InventoryCategory;

trait SpecificationTrait
{
    /**
     * Получает переведенные метки для спецификаций.
     *
     * @return array<string, string>
     */
    private function getSpecificationLabels(InventoryCategory $category): array
    {
        $labels = [];
        $specKeys = $category->getAllowedSpecifications();

        foreach ($specKeys as $specKey) {
            $labels[$specKey] = $this->translator->trans("inventory_item.spec_labels.{$specKey}");
        }

        return $labels;
    }// end getSpecificationLabels()
}// end trait
