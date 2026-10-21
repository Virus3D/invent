<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Furniture;
use App\Entity\Location;
use App\Enum\FurnitureCategory;
use App\Enum\ItemStatus;
use App\Form\FurnitureFilterType;
use App\Repository\FurnitureRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class FurnitureTable
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public Location $location;

    /**
     * CSRF-токен, чтобы встроенный boolean-toggle в таблице продолжал работать.
     */
    #[LiveProp]
    public string $booleanToggleCsrfToken = '';

    /**
     * Какие колонки показывать
     *
     * @var string[]
     */
    #[LiveProp]
    public array $columns = [
        'inventory_number',
        'status',
        'location',
        'checked',
        'created_at',
        'actions',
    ];

    #[LiveProp(writable: true)]
    public string $sortField = 'createdAt';

    #[LiveProp(writable: true)]
    public string $sortDirection = 'desc';

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly FurnitureRepository $repository,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public function mount(): void
    {
        // Ключи должны совпадать с именами полей FurnitureFilterType.
        $this->formValues = [
            'name'     => null,
            'category' => null,
            'status'   => null,
        ];
    }// end mount()

    /**
     * Creates the filter form for this component.
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(FurnitureFilterType::class);
    }// end instantiateForm()

    /**
     * @return Furniture[]
     */
    public function getItems(): array
    {
        /**
         * Фильтр.
         *
         * @var array{name?: ?string, category?: ?FurnitureCategory, status?: ?ItemStatus} $filters
         */
        $filters = $this->getForm()->getData() ?? [];

        return $this->repository->findFiltered(
            location:      $this->location,
            search:        $filters['name'] ?? null,
            category:      $filters['category'] ?? null,
            status:        $filters['status'] ?? null,
            sortField:     $this->sortField,
            sortDirection: $this->sortDirection,
        );
    }// end getItems()

    /**
     * Sorts furniture items by the given field.
     *
     * @param string $field The field to sort by (whitelisted).
     */
    #[LiveAction]
    public function sortBy(#[LiveArg] string $field): void
    {
        // Whitelist — иначе через URL можно прислать произвольное имя поля.
        $allowed = [
            'inventoryNumber',
            'name',
            'createdAt',
            'checked',
        ];
        if (!\in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }// end sortBy()

    /**
     * Triggers form submission and validation.
     */
    #[LiveAction]
    public function save(): void
    {
        // Прогнать submit + валидацию формы (fallback для submit).
        $this->getForm();
    }// end save()
}// end class
