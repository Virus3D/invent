<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InventoryItemRepository;
use App\Repository\LocationRepository;
use App\Repository\MovementLogRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Главная панель администрирования.
 *
 * Отображает дашборд с ключевыми метриками, графиком категорий и последними
 * перемещениями, используя данные из существующих репозиториев.
 */
#[AdminDashboard(routePath: '/', routeName: 'app_dashboard')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly InventoryItemRepository $itemRepository,
        private readonly LocationRepository $locationRepository,
        private readonly MovementLogRepository $logRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }// end __construct()

    /**
     * @inheritDoc
     */
    public function index(): Response
    {
        $stats = $this->getStats();
        $recentMovements = $this->logRepository->findRecent(10);
        $itemsWithoutLocation = $this->itemRepository->findByLocation(null);

        return $this->render(
            'dashboard/index.html.twig',
            [
                'stats'                => $stats,
                'recentMovements'      => $recentMovements,
                'itemsWithoutLocation' => $itemsWithoutLocation,
            ]
        );
    }// end index()

    /**
     * @inheritDoc
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle($this->translator->trans('title'))
            ->setTranslationDomain('admin')
            ->useEntityTranslations();
    }// end configureDashboard()

    /**
     * @inheritDoc
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('page.dashboard', 'fa fa-home');

        yield MenuItem::section('sections.main');
        yield MenuItem::linkTo(InventoryItemCrudController::class, 'page.inventory', 'fas fa-box');
        yield MenuItem::linkTo(LocationCrudController::class, 'page.location', 'fas fa-map-marker-alt');
        yield MenuItem::linkTo(SoftwareLicenseCrudController::class, 'page.license', 'fas fa-certificate');
        yield MenuItem::linkTo(MaterialCrudController::class, 'page.material', 'fas fa-cubes');
        yield MenuItem::linkTo(CartridgeCrudController::class, 'page.cartridge', 'fas fa-fill-drip');

        yield MenuItem::section('sections.users');
        yield MenuItem::linkTo(UserCrudController::class, 'page.users', 'fas fa-users');
    }// end configureMenuItems()

    /**
     * @inheritDoc
     */
    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addWebpackEncoreEntry('app');
    }// end configureAssets()

    /**
     * Собирает статистику для дашборда.
     *
     * @return array<string, mixed>
     */
    private function getStats(): array
    {
        $movementStats = $this->logRepository->getMovementStats();
        $itemsByCategory = $this->itemRepository->getCategoryStatisticsWithZero();

        $chartCategories = [];
        $chartCounts = [];

        foreach ($itemsByCategory as $category => $item) {
            $chartCategories[] = $category;
            $chartCounts[] = $item['count'];
        }

        return [
            'total_items'       => $this->itemRepository->count([]),
            'total_locations'   => $this->locationRepository->count([]),
            'total_movements'   => $movementStats['total_movements'] ?? 0,
            'first_movement'    => $movementStats['first_movement'] ?? null,
            'last_movement'     => $movementStats['last_movement'] ?? null,
            'items_by_category' => $itemsByCategory,
            'chart_categories'  => $chartCategories,
            'chart_counts'      => $chartCounts,
        ];
    }// end getStats()
}// end class
