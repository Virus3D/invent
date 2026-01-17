<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Repository\InventoryItemRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class CategoryFilter
{
    /**
     * List.
     *
     * @var array<string, mixed>
     */
    public array $list = [];

    public int $coutnAll = 0;

    public function __construct(private InventoryItemRepository $repository)
    {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public function mount(): void
    {
        $this->list = $this->repository->getCategoryStatisticsWithZero();
        $this->coutnAll = array_sum(array_column($this->list, 'count'));
    }// end mount()
}// end class
