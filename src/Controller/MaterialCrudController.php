<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Material;
use App\Form\WriteOffToInventoryItemType;
use App\Form\WriteOffToLocationType;
use App\Repository\MaterialConsumptionRepository;
use App\Service\MaterialWriteOffService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaterialCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly MaterialWriteOffService $writeOffService,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return Material::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50);
    }// end configureCrud()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name');
        yield NumberField::new('quantity');
        yield AssociationField::new('location');
        yield BooleanField::new('checked');
        yield DateField::new('createdAt')->hideOnForm();
    }// end configureFields()

    /**
     * @inheritDoc
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add(EntityFilter::new('location'));
    }// end configureFilters()

    /**
     * @inheritDoc
     */
    public function configureActions(Actions $actions): Actions
    {
        $writeOffLocation = Action::new('writeOffLocation', 'actions.write_off.location')
            ->setIcon('fas fa-map-marker-alt')
            ->linkToCrudAction('writeOffLocation');

        $writeOffItem = Action::new('writeOffItem', 'actions.write_off.item')
            ->setIcon('fas fa-box')
            ->linkToCrudAction('writeOffItem');

        $resetCheck = Action::new('checkResetAll', 'actions.check.reset_all')
            ->setIcon('bi bi-arrow-counterclockwise')
            ->linkToCrudAction('checkResetAll')
            ->createAsGlobalAction();

        $report = Action::new('report', 'actions.material.report')
            ->setIcon('bi bi-arrow-counterclockwise')
            ->linkToCrudAction('report')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $writeOffLocation)
            ->add(Crud::PAGE_INDEX, $writeOffItem)
            ->add(Crud::PAGE_INDEX, $resetCheck)
            ->add(Crud::PAGE_INDEX, $report)
            ->add(Crud::PAGE_DETAIL, $writeOffLocation)
            ->add(Crud::PAGE_DETAIL, $writeOffItem);
    }// end configureActions()

    /**
     * Кастомное действие: списание материала на место.
     *
     * GET: отображает форму WriteOffToLocationType.
     * POST: обрабатывает форму, вызывает сервис списания.
     */
    #[AdminRoute]
    public function writeOffLocation(AdminContext $context, Request $request): Response
    {
        /**
         * Material.
         *
         * @var Material|null $material
         */
        $material = $context->getEntity()->getInstance();
        if (!$material instanceof Material) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(WriteOffToLocationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $this->writeOffService->writeOffToLocation(
                    $material,
                    (int) $data['quantity'],
                    $data['location'],
                    $this->getUser(),
                    $data['comment'] ?? null
                );
                $this->addFlash('success', $this->translator->trans('flash.write_off_location_success', domain: 'material'));
            } catch (\Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('flash.write_off_error', ['%error%' => $e->getMessage()], 'material')
                );
                return $this->render(
                    'material/write_off_location.html.twig',
                    [
                        'material' => $material,
                        'form'     => $form->createView(),
                    ]
                );
            }// end try

            return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl());
        }// end if

        return $this->render(
            'material/write_off_location.html.twig',
            [
                'material' => $material,
                'form'     => $form->createView(),
            ]
        );
    }// end writeOffLocation()

    /**
     * Кастомное действие: списание материала на инвентарный объект.
     *
     * GET: отображает форму WriteOffToInventoryItemType.
     * POST: обрабатывает форму, вызывает сервис списания,
     * сохраняет ID материала в сессии и перенаправляет на редактирование объекта.
     */
    #[AdminRoute]
    public function writeOffItem(AdminContext $context, Request $request): Response
    {
        /**
         * Material.
         *
         * @var Material|null $material
         */
        $material = $context->getEntity()->getInstance();
        if (!$material instanceof Material) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(WriteOffToInventoryItemType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $inventoryItem = $data['inventoryItem'];
                $this->writeOffService->writeOffToInventoryItem(
                    $material,
                    (int) $data['quantity'],
                    $inventoryItem,
                    $this->getUser(),
                    $data['comment'] ?? null
                );
                $request->getSession()->set('last_used_material_id', $material->getId());
                $this->addFlash('success', $this->translator->trans('flash.write_off_item_success', domain: 'material'));

                // Перенаправляем на редактирование инвентарного объекта.
                return $this->redirect(
                    $this->adminUrlGenerator
                        ->setController('App\Controller\InventoryItemCrudController')
                        ->setAction('edit')
                        ->setEntityId($inventoryItem->getId())
                        ->generateUrl()
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('flash.write_off_error', ['%error%' => $e->getMessage()], 'material')
                );
                return $this->render(
                    'material/write_off_item.html.twig',
                    [
                        'material' => $material,
                        'form'     => $form->createView(),
                    ]
                );
            }// end try
        }// end if

        return $this->render(
            'material/write_off_item.html.twig',
            [
                'material' => $material,
                'form'     => $form->createView(),
            ]
        );
    }// end writeOffItem()

    /**
     * Сбрасывает флаг "checked" у всех объектов материалов.
     *
     * Выполняется через прямой SQL-запрос для максимальной производительности.
     * После выполнения перенаправляет обратно на список материалов.
     */
    #[AdminRoute]
    public function checkResetAll(): RedirectResponse
    {
        $this->entityManager->getConnection()->executeStatement('UPDATE material SET checked = 0');

        $this->addFlash('success', $this->translator->trans('flash.check_reset_success', domain: 'material'));

        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
        );
    }// end checkResetAll()

    /**
     * Отчет списания.
     */
    #[AdminRoute]
    public function report(Request $request, MaterialConsumptionRepository $consumptionRepo): Response
    {
        $start = new DateTimeImmutable($request->query->get('start', 'first day of this month'));
        $end   = new DateTimeImmutable($request->query->get('end', 'last day of this month'));

        $consumptions = $consumptionRepo->findByPeriod($start, $end);
        $aggregated   = $consumptionRepo->getAggregatedByPeriod($start, $end);

        return $this->render(
            'material/report.html.twig',
            [
                'start'        => $start,
                'end'          => $end,
                'consumptions' => $consumptions,
                'aggregated'   => $aggregated,
            ]
        );
    }// end report()
}// end class
