<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Location;
use App\Form\InventoryItemFilterType;
use App\Repository\InventoryItemRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LocationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly InventoryItemRepository $inventoryItemRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return Location::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/edit', 'location/edit.html.twig')
            ->overrideTemplate('crud/detail', 'location/detail.html.twig')
            ->setPageTitle('detail', fn (Location $l) => sprintf('%s (каб. %s)', $l->getName(), $l->getRoomNumber()))
            ->setPageTitle('edit', fn (Location $l) => sprintf('Редактирование: %s', $l->getName()))
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50);
    }// end configureCrud()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name')->setSortable(false);
        yield TextField::new('roomNumber')->setSortable(false);
        yield TextField::new('description')->hideOnIndex();
        // Вычисляемое поле для количества объектов (только на index).
        if (Crud::PAGE_INDEX === $pageName) {
            yield IntegerField::new('inventoryItemsCount')
                ->setTemplatePath('fields/inventory_count.html.twig')
                ->setSortable(false);
        }
    }// end configureFields()

    /**
     * @inheritDoc
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name');
    }// end configureFilters()

    /**
     * @inheritDoc
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }// end configureActions()

    /**
     * @inheritDoc
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->orderBy('entity.roomNumber', 'ASC');

        return $qb;
    }// end createIndexQueryBuilder()

    /**
     * @inheritDoc
     *
     * Добавляем вычисляемое поле inventoryItemsCount в результаты index.
     */
    public function index(AdminContext $context): KeyValueStore|Response
    {
        $response = parent::index($context);

        if ($response instanceof KeyValueStore) {
            $entities = $response->get('entities');
            foreach ($entities as $entity) {
                /**
                 * Location.
                 *
                 * @var Location $location
                 */
                $location = $entity->getInstance();
                // Получаем коллекцию полей, обработанных EasyAdmin.
                $fields = $entity->getFields();
                // Ищем поле с property 'inventoryItemsCount' и устанавливаем значение.
                foreach ($fields as $field) {
                    if ('inventoryItemsCount' === $field->getProperty()) {
                        $field->setValue($location->getInventoryItems()->count());
                        break;
                    }
                }
            }
        }

        return $response;
    }// end index()

    /**
     * @inheritDoc
     *
     * Расширенный detail: добавляет форму фильтрации инвентарных объектов
     * и отфильтрованный список для отображения в шаблоне.
     */
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        /**
         * Location.
         *
         * @var Location $location
         */
        $location = $context->getEntity()->getInstance();
        if (!$location instanceof Location) {
            throw $this->createNotFoundException();
        }

        $request = $context->getRequest();
        $filterForm = $this->createForm(InventoryItemFilterType::class);
        $filters = [];

        if ($request->query->has('inventory_item_filter')) {
            $filterForm->submit($request->query->all('inventory_item_filter'));
            if ($filterForm->isValid()) {
                $filters = $filterForm->getData();
            }
        }

        $inventoryItems = $this->inventoryItemRepository->findByLocationAndFilters($location, $filters);

        // AJAX-запрос: возвращаем HTML таблицы.
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView(
                'inventory/_list.html.twig',
                [
                    'items'   => $inventoryItems,
                    'columns' => [
                        'inventory_number',
                        'status',
                        'serial_number',
                        'checked',
                        'created_at',
                        'actions',
                    ],
                ]
            );

            return new JsonResponse(
                [
                    'html'  => $html,
                    'count' => count($inventoryItems),
                ]
            );
        }// end if

        // Получаем стандартный ответ от EasyAdmin (поля, действия и т.д.).
        $response = parent::detail($context);

        if ($response instanceof KeyValueStore) {
            // Генерируем CSRF-токен, совместимый с EasyAdmin BooleanField.
            $csrfToken = $this->csrfTokenManager->getToken('ea-toggle')->getValue();

            // Добавляем переменные для шаблона.
            $response->set('filterForm', $filterForm->createView());
            $response->set('inventoryItems', $inventoryItems);
            $response->set('booleanToggleCsrfToken', $csrfToken);
        }

        return $response;
    }// end detail()

    /**
     * @inheritDoc
     *
     * Переопределяем стандартное пакетное удаление, чтобы перед удалением
     * перенести все связанные InventoryItems в «без локации».
     */
    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        // Проверка CSRF выполняется в родительском методе, но для кастомного действия мы вызываем его после переноса.
        // Для единообразия полностью скопируем логику из родителя, добавив перенос.
        $entityFqcn = $batchActionDto->getEntityFqcn();
        $entityManager = $this->container->get('doctrine')->getManagerForClass($entityFqcn);
        $repository = $entityManager->getRepository($entityFqcn);

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            /**
             * Location.
             *
             * @var Location|null $location
             */
            $location = $repository->find($entityId);
            if (null === $location) {
                continue;
            }

            // Переносим инвентарные объекты в "без локации".
            foreach ($location->getInventoryItems() as $item) {
                $item->setLocation(null);
                $this->inventoryItemRepository->save($item, false);
            }

            // Удаляем локацию (flush будет в конце).
            $this->deleteEntity($entityManager, $location);
        }

        $entityManager->flush();

        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
        );
    }// end batchDelete()
}// end class
