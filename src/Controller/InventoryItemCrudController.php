<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CartridgeInstallation;
use App\Entity\InventoryItem;
use App\Entity\MovementLog;
use App\Enum\BalanceType;
use App\Enum\InventoryCategory;
use App\Form\InventoryItemType;
use App\Form\MoveInventoryType;
use App\Form\MovementLogType;
use App\Repository\CartridgeInstallationRepository;
use App\Repository\CartridgeRepository;
use App\Repository\InventoryItemRepository;
use App\Service\CartridgeManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD-контроллер для управления инвентарными объектами.
 *
 * Инкапсулирует стандартные операции (list, new, edit, delete),
 * а уникальные действия (перемещение, проверка, расширенный поиск)
 * вынесены в InventoryActionController.
 */
final class InventoryItemCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CartridgeManager $cartridgeManager,
        private readonly CartridgeRepository $cartridgeRepository,
        private readonly InventoryItemRepository $inventoryItemRepository,
        private readonly CartridgeInstallationRepository $installationRepo,
        private readonly TranslatorInterface $translator,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return InventoryItem::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('inventoryNumber')
            ->setTemplatePath('fields/inventory_number.html.twig');

        yield TextField::new('name')
            ->setHelp('inventory_item.form.name_help')
            ->setTemplatePath('fields/inventory_item.html.twig');

        yield TextField::new('serialNumber');

        yield ChoiceField::new('category')
            ->setChoices(InventoryCategory::cases())
            ->setTemplatePath('fields/category.html.twig');
        ;

        yield AssociationField::new('location');

        yield DateTimeField::new('createdAt')
            ->setTemplatePath('fields/inventory_created.html.twig')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt')
            ->setFormat('d.m.Y')
            ->onlyOnDetail();

        yield BooleanField::new('checked');
    }// end configureFields()

    /**
     * @inheritDoc
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('category')
            ->add('location');
    }// end configureFilters()

    /**
     * @inheritDoc
     */
    public function configureActions(Actions $actions): Actions
    {
        // Кастомные действия на странице списка и деталей.
        $moveAction = Action::new('move', 'actions.move')
            ->setIcon('fas fa-exchange-alt')
            ->linkToCrudAction('move');

        $resetCheck = Action::new('check_reset_all', 'actions.check.reset_all')
            ->setIcon('bi bi-arrow-counterclockwise')
            ->linkToCrudAction('checkResetAll')
            ->createAsGlobalAction();

        // Действия для принтеров.
        $installCartridge = Action::new('installCartridge', 'actions.cartridge.install')
            ->setIcon('fas fa-download')
            ->linkToCrudAction('installCartridge')
            ->displayIf(fn (InventoryItem $item) => $item->getCategory() === InventoryCategory::PRINTER);

        $removeCartridge = Action::new('removeCartridge', 'actions.cartridge.install')
            ->setIcon('fas fa-upload')
            ->linkToCrudAction('removeCartridge')
            ->displayIf(fn (InventoryItem $item) => $item->getCategory() === InventoryCategory::PRINTER);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $moveAction)
            ->add(Crud::PAGE_INDEX, $resetCheck)
            ->add(Crud::PAGE_DETAIL, $moveAction)
            ->add(Crud::PAGE_DETAIL, $installCartridge)
            ->add(Crud::PAGE_DETAIL, $removeCartridge)
            ->add(Crud::PAGE_EDIT, Action::DELETE);
    }// end configureActions()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/index', 'inventory/index.html.twig')
            ->overrideTemplate('crud/detail', 'inventory/detail.html.twig')
            ->overrideTemplate('crud/edit', 'inventory/edit.html.twig')
            ->overrideTemplate('crud/new', 'inventory/new.html.twig')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50)
            ->setDefaultSort(['id' => 'ASC']);
    }// end configureCrud()

    /**
     * @inheritDoc
     */
    public function createEditFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context,
    ): FormBuilderInterface {
        // Передаём опцию specs_url в форму.
        return $this->createFormBuilderForEntity(
            $entityDto->getInstance(),
            $formOptions->get('specs_url', $this->generateUrl('api_category_specs', ['category' => '__CATEGORY__']))
        );
    }// end createEditFormBuilder()

    /**
     * @inheritDoc
     */
    public function createNewFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context,
    ): FormBuilderInterface {
        return $this->createFormBuilderForEntity(
            $entityDto->getInstance(),
            $formOptions->get('specs_url', $this->generateUrl('api_category_specs', ['category' => '__CATEGORY__']))
        );
    }// end createNewFormBuilder()

    /**
     * @inheritDoc
     */
    private function createFormBuilderForEntity(?InventoryItem $entity, string $specsUrl): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createBuilder(
            InventoryItemType::class,
            $entity,
            ['specs_url' => $specsUrl]
        );
    }// end createFormBuilderForEntity()

    /**
     * @inheritDoc
     *
     * Расширенный detail: добавляет форму фильтрации инвентарных объектов
     * и отфильтрованный список для отображения в шаблоне.
     */
    public function detail(AdminContext $context): KeyValueStore|Response
    {
        /**
         * Item.
         *
         * @var InventoryItem $location
         */
        $item = $context->getEntity()->getInstance();

        // ✅ Данные для блока картриджей
        $activeInstallation = null;
        $prediction = null;
        $recentInstallations = [];

        if ($item->getCategory()?->value === 'printer') {
            $activeInstallation = $this->cartridgeManager->getActiveInstallation($item);

            if ($activeInstallation) {
                $prediction = $this->cartridgeManager->predictReplacementDate(
                    $item,
                    $activeInstallation->getCartridge()
                );
            }

            $recentInstallations = $this->installationRepo->findBy(
                ['printer' => $item],
                ['installedAt' => 'DESC'],
                5
            );
        }

        $response = parent::detail($context);

        if ($response instanceof KeyValueStore) {
            // Добавляем переменные для шаблона.
            $response->set('active_installation', $activeInstallation);
            $response->set('replacement_prediction', $prediction);
            $response->set('recent_installations', $recentInstallations);
        }

        return $response;
    }// end detail()

    /**
     * Сбрасывает флаг "checked" у всех объектов инвентаря.
     *
     * Выполняется через прямой SQL-запрос для максимальной производительности.
     * После выполнения перенаправляет обратно на список инвентаря.
     */
    #[AdminRoute]
    public function checkResetAll(): RedirectResponse
    {
        $this->entityManager->getConnection()->executeStatement('UPDATE inventory_item SET checked = 0');

        $this->addFlash('success', 'Все проверки успешно сброшены.');

        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
        );
    }// end checkResetAll()

    /**
     * Кастомная страница перемещения (GET – форма, POST – обработка).
     */
    #[AdminRoute]
    public function move(AdminContext $context): Response
    {
        $item = $context->getEntity()->getInstance();
        if (!$item instanceof InventoryItem) {
            throw $this->createNotFoundException();
        }

        $log = new MovementLog();
        $log->setInventoryItem($item);
        $log->setFromLocation($item->getLocation());

        $form = $this->createForm(MovementLogType::class, $log);
        $form->handleRequest($context->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            // Обновляем местоположение объекта.
            $item->setLocation($log->getToLocation());

            $this->entityManager->flush();
            $this->addFlash('success', 'Перемещение зарегистрировано.');

            return $this->redirect(
                $this->adminUrlGenerator->setController(self::class)
                    ->setAction('detail')
                    ->setEntityId($item->getId())->generateUrl()
            );
        }

        return $this->render(
            'inventory/move.html.twig',
            [
                'form' => $form->createView(),
                'item' => $item,
            ]
        );
    }// end move()

    /**
     * Установка картриджа на принтер.
     *
     * GET: можно показывать форму выбора картриджа, но для упрощения сразу редиректим.
     * POST: обрабатывает выбор картриджа и вызывает сервис установки.
     */
    #[AdminRoute]
    public function installCartridge(AdminContext $context, Request $request): Response
    {
        $printerId   = $request->request->get('printer_id');
        $cartridgeId = $request->request->get('cartridge_id');
        $comment     = trim($request->request->get('comment', ''));

        /**
         * InventoryItem.
         *
         * @var InventoryItem|null $printer
         */
        $printer = $this->inventoryItemRepository->find($printerId);
        if (!$printer || $printer->getCategory() !== InventoryCategory::PRINTER) {
            $this->addFlash('danger', $this->translator->trans('cartridge.flash.not_a_printer', domain: 'cartridge'));
            return $this->redirectToIndex();
        }

        $cartridge = $this->cartridgeRepository->find($cartridgeId);
        if (!$cartridge) {
            $this->addFlash('danger', $this->translator->trans('cartridge.flash.entity_not_found', domain: 'cartridge'));
            return $this->redirectToPrinterDetail($printer);
        }

        try {
            $this->cartridgeManager->installCartridge($cartridge, $printer, $comment);
            $this->addFlash(
                'success',
                $this->translator->trans(
                    'cartridge.flash.cartridge_installed',
                    [
                        '%name%'    => $cartridge->getName(),
                        '%printer%' => $printer->getName(),
                    ],
                    'cartridge'
                )
            );
        } catch (\RuntimeException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToPrinterDetail($printer);
    }// end installCartridge()

    /**
     * Снятие картриджа с принтера.
     *
     * Ожидается POST с указанием ID установки (CartridgeInstallation).
     */
    #[AdminRoute]
    public function removeCartridge(AdminContext $context, Request $request): Response
    {
        /**
         * InventoryItem.
         *
         * @var InventoryItem|null $printer
         */
        $printer = $context->getEntity()->getInstance();
        if (!$printer instanceof InventoryItem || $printer->getCategory() !== InventoryCategory::PRINTER) {
            $this->addFlash('danger', $this->translator->trans('cartridge.flash.not_a_printer', domain: 'cartridge'));
            return $this->redirectToIndex();
        }

        if ($request->isMethod('POST')) {
            $installationId = $request->request->get('installation_id');
            $printedPages   = $request->request->get('printed_pages');

            /**
             * CartridgeInstallation.
             *
             * @var CartridgeInstallation|null $installation
             */
            $installation = $this->entityManager->getRepository(CartridgeInstallation::class)->find($installationId);
            if (!$installation) {
                $this->addFlash('danger', 'Установка не найдена');
                return $this->redirectToPrinterDetail($printer);
            }

            try {
                $this->cartridgeManager->removeCartridge(
                    $installation,
                    $printedPages ? (int) $printedPages : null
                );
                $this->addFlash(
                    'success',
                    $this->translator->trans('cartridge.flash.cartridge_removed', domain: 'cartridge')
                );
            } catch (\RuntimeException $e) {
                $this->addFlash('danger', $e->getMessage());
            }

            return $this->redirectToPrinterDetail($printer);
        }// end if

        return $this->redirectToPrinterDetail($printer);
    }// end removeCartridge()

    /**
     * Вспомогательные методы для редиректов.
     */
    private function redirectToPrinterDetail(InventoryItem $printer): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction('detail')
                ->setEntityId($printer->getId())
                ->generateUrl()
        );
    }// end redirectToPrinterDetail()

    /**
     * Вспомогательные методы для редиректов.
     */
    private function redirectToIndex(): RedirectResponse
    {
        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction('index')->generateUrl()
        );
    }// end redirectToIndex()
}// end class
