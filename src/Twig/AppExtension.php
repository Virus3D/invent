<?php

declare(strict_types=1);

namespace App\Twig;

use App\Enum\InventoryCategory;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

/**
 * Class AppExtension provides custom Twig functions for inventory categories.
 */
class AppExtension extends AbstractExtension
{
    /**
     * Get Twig functions provided by this extension.
     *
     * @return array<int, \Twig\TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_category_enum', [$this, 'getCategoryEnum']),
            new TwigFunction('get_all_categories', [$this, 'getAllCategories']),
        ];
    }// end getFunctions()

    /**
     * Get Twig filters provided by this extension.
     *
     * @return array<int, \Twig\TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('category_icon', [$this, 'getCategoryIcon']),
            new TwigFilter('category_color', [$this, 'getCategoryColor']),
        ];
    }// end getFilters()

    /**
     * Get the InventoryCategory enum instance for a given value.
     */
    public function getCategoryEnum(string $value): ?InventoryCategory
    {
        return InventoryCategory::tryFrom($value);
    }// end getCategoryEnum()

    /**
     * Get all inventory categories.
     *
     * @return InventoryCategory[] List of all inventory category cases.
     */
    public function getAllCategories(): array
    {
        return InventoryCategory::cases();
    }// end getAllCategories()

    /**
     * Get the icon associated with a category value.
     */
    public function getCategoryIcon(string $value): string
    {
        $category = InventoryCategory::tryFrom($value);
        return $category ? $category->getIcon() : 'bi-question-circle';
    }// end getCategoryIcon()

    /**
     * Get the color associated with a category value.
     */
    public function getCategoryColor(string $value): string
    {
        $category = InventoryCategory::tryFrom($value);
        return $category ? $category->getColor() : 'secondary';
    }// end getCategoryColor()
}// end class
