<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Controller\InventoryItemCrudController;
use App\Repository\InventoryItemRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Компонент фильтра инвентаря по категориям для EasyAdmin.
 *
 * Генерирует набор кнопок-ссылок с количеством объектов в каждой категории.
 * Интегрируется со стандартным фильтром EasyAdmin через параметры `filters[category][...]`.
 */
#[AsTwigComponent]
final class CategoryFilter
{
    /**
     * Статистика по категориям (ключ => ['category' => InventoryCategory, 'count' => int]).
     *
     * @var array<string, array{category: \App\Enum\InventoryCategory, count: int}>
     */
    public array $list = [];

    /**
     * Общее количество объектов.
     */
    public int $countAll = 0;

    /**
     * URL для кнопки «Все».
     */
    public string $allUrl = '';

    /**
     * URL для каждой категории.
     *
     * @var array<string, string>
     */
    public array $urls = [];

    /**
     * Значение активной категории (если выбрана), иначе null.
     */
    public ?string $activeCategory = null;

    public function __construct(
        private readonly InventoryItemRepository $repository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly RequestStack $requestStack,
    ) {
    }// end __construct()

    /**
     * Инициализация данных и URL.
     */
    public function mount(): void
    {
        $this->list = $this->repository->getCategoryStatisticsWithZero();
        $this->countAll = array_sum(array_column($this->list, 'count'));

        // Извлекаем текущее значение фильтра категории из запроса.
        $request = $this->requestStack->getCurrentRequest();
        $filters = $request?->query->all('filters') ?? [];
        $this->activeCategory = $filters['category']['value'] ?? null;

        // URL для сброса фильтра (все категории).
        $this->allUrl = $this->adminUrlGenerator
            ->setController(InventoryItemCrudController::class)
            ->setAction('index')
            ->unset('filters')
            ->generateUrl();

        // URL для каждой категории.
        foreach ($this->list as $code => $item) {
            $this->urls[$code] = $this->adminUrlGenerator
                ->setController(InventoryItemCrudController::class)
                ->setAction('index')
                ->set(
                    'filters',
                    [
                        'category' => [
                            'comparison' => '=',
                            'value'      => $item['category']->value,
                        ],
                    ]
                )
                ->generateUrl();
        }
    }// end mount()
}// end class
