<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Cartridge;
use App\Repository\CartridgeInstallationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartridgeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly CartridgeInstallationRepository $installationRepository,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public static function getEntityFqcn(): string
    {
        return Cartridge::class;
    }// end getEntityFqcn()

    /**
     * @inheritDoc
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->overrideTemplate('crud/index', 'cartridge/index.html.twig')
            ->overrideTemplate('crud/new', 'cartridge/new.html.twig')
            ->overrideTemplate('crud/edit', 'cartridge/edit.html.twig')
            ->overrideTemplate('crud/detail', 'cartridge/detail.html.twig')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(50);
    }// end configureCrud()

    /**
     * @inheritDoc
     */
    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('entities.' . $this->getEntityFqcn() . '.sections.base')
            ->setIcon('bi bi-info-circle me-2');
        yield TextField::new('name')
            ->setHelp('entities.' . $this->getEntityFqcn() . '.properties.name_help')
            ->setColumns(9)
            ->setTemplatePath('fields/cartridge_name.html.twig');

        yield ChoiceField::new('color')
            ->setChoices(
                [
                    'colors.black'   => 'black',
                    'colors.cyan'    => 'cyan',
                    'colors.magenta' => 'magenta',
                    'colors.yellow'  => 'yellow',
                    'colors.color'   => 'color',
                    'colors.other'   => 'other',
                ]
            )
            ->renderExpanded(false)
            ->setColumns(3)
            ->setTemplatePath('fields/cartridge_color.html.twig');

        yield IntegerField::new('yieldPages')
            ->setHelp('entities.' . $this->getEntityFqcn() . '.properties.yieldPages_help')
            ->setColumns(6);

        yield IntegerField::new('stockQuantity')
            ->setHelp('entities.' . $this->getEntityFqcn() . '.properties.stockQuantity_help')
            ->setColumns(6)
            ->setTemplatePath('fields/cartridge_stock.html.twig');

        yield FormField::addFieldset('entities.' . $this->getEntityFqcn() . '.sections.compatible')
            ->setIcon('bi bi-link-45deg me-2');

        yield AssociationField::new('printers')
            ->setLabel('entities.' . $this->getEntityFqcn() . '.properties.printers')
            ->setHelp('entities.' . $this->getEntityFqcn() . '.properties.printers_help')
            ->setQueryBuilder(
                fn (\Doctrine\ORM\QueryBuilder $qb) => $qb
                    ->andWhere('entity.category = :printerCategory')
                    ->setParameter('printerCategory', 'printer')
                    ->orderBy('entity.name', 'ASC')
            )
            ->autocomplete()
            ->setRequired(false)
            ->setFormTypeOption('multiple', true)
            ->setTemplatePath('fields/printers.html.twig');
    }// end configureFields()

    /**
     * @inheritDoc
     */
    public function configureActions(Actions $actions): Actions
    {
        $addStock = Action::new('addStock', 'Пополнить склад')
            ->setIcon('fas fa-plus-square')
            ->linkToCrudAction('addStock')
            ->renderAsButton();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $addStock);
    }// end configureActions()

    /**
     * Кастомное действие: пополнение складского запаса.
     *
     * Принимает POST-параметр quantity (по умолчанию 1).
     */
    #[AdminRoute]
    public function addStock(AdminContext $context, Request $request): RedirectResponse
    {
        /**
         * Cartridge.
         *
         * @var Cartridge|null $cartridge
         */
        $cartridge = $context->getEntity()->getInstance();
        if (!$cartridge instanceof Cartridge) {
            throw $this->createNotFoundException();
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cartridge->increaseStock($quantity);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Добавлено %d картридж(ей) "%s" на склад', $quantity, $cartridge->getName()));

        return $this->redirect(
            $this->adminUrlGenerator->setController(self::class)->setAction('detail')
                ->setEntityId($cartridge->getId())->generateUrl()
        );
    }// end addStock()

    /**
     * @inheritDoc
     *
     * Переопределяем детальный просмотр, чтобы добавить отчёт использования.
     */
    public function detail(AdminContext $context): Response
    {
        /**
         * Cartridge.
         *
         * @var Cartridge $cartridge
         */
        $cartridge = $context->getEntity()->getInstance();
        $request = $context->getRequest();

        $from = new DateTimeImmutable($request->query->get('from', 'first day of this year'));
        $to   = new DateTimeImmutable($request->query->get('to', 'today'));
        $stats = $this->installationRepository->getUsageStats($cartridge, $from, $to);

        return $this->render(
            'cartridge/detail.html.twig',
            [
                'cartridge' => $cartridge,
                'stats'     => $stats,
                'date_from' => $from,
                'date_to'   => $to,
                'entity'    => $context->getEntity(),
            ]
        );
    }// end detail()

    /**
     * @inheritDoc
     *
     * Проверка перед удалением: нельзя удалить, если есть активные установки.
     */
    public function deleteEntity(EntityManagerInterface $entityManager, object $entityInstance): void
    {
        if ($entityInstance instanceof Cartridge) {
            if ($entityInstance->getInstallations()->exists(fn ($k, $i) => $i->isInstalled())) {
                throw new \RuntimeException('Нельзя удалить картридж, который сейчас установлен на принтере');
            }
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }// end deleteEntity()

    /**
     * Переопределяем index, чтобы добавить блок статистики.
     */
    public function index(AdminContext $context): KeyValueStore|Response
    {
        $response = parent::index($context);

        if ($response instanceof KeyValueStore) {
            $stats = $this->getCartridgeStats();
            $response->set('stats', $stats);
        }

        return $response;
    }// end index()

    /**
     * Собирает статистику по картриджам: количество моделей, общий запас,
     * модели с низким остатком и количество активных установок.
     *
     * @return array<string, int>
     */
    private function getCartridgeStats(): array
    {
        $repo = $this->entityManager->getRepository(Cartridge::class);
        $all = $repo->findAll();

        $totalModels = count($all);
        $inStock     = array_sum(array_map(fn(Cartridge $c) => $c->getStockQuantity(), $all));
        $lowStock    = count(array_filter($all, fn(Cartridge $c) => $c->getStockQuantity() < 2));
        $installed   = array_sum(
            array_map(
                fn(Cartridge $c) => $c->getInstallations()->filter(fn($i) => $i->isInstalled())->count(),
                $all
            )
        );

        return [
            'totalModels' => $totalModels,
            'inStock'     => $inStock,
            'lowStock'    => $lowStock,
            'installed'   => $installed,
        ];
    }// end getCartridgeStats()
}// end class
