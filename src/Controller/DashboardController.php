<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\InventoryCategory;
use App\Repository\InventoryItemRepository;
use App\Repository\LocationRepository;
use App\Repository\MovementLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    /**
     * Display the main dashboard page.
     */
    #[Route('/', name: 'app_dashboard')]
    public function dashboard(
        InventoryItemRepository $itemRepository,
        LocationRepository $locationRepository,
        MovementLogRepository $logRepository
    ): Response {
        $stats = $this->getStats($itemRepository, $locationRepository, $logRepository);

        return $this->render(
            'dashboard/index.html.twig',
            [
                'stats'                => $stats,
                'recentMovements'      => $logRepository->findRecent(10),
                'itemsWithoutLocation' => $itemRepository->findByLocation(null),
            ]
        );
    }// end dashboard()

    /**
     * Display dashboard statistics as a partial.
     */
    public function stats(
        InventoryItemRepository $itemRepository,
        MovementLogRepository $logRepository
    ): Response {
        $stats = [
            'items_count'     => $itemRepository->count([]),
            'movements_count' => $logRepository->count([]),
        ];

        return $this->render(
            'dashboard/_stats.html.twig',
            ['stats' => $stats]
        );
    }// end stats()

    /**
     * Get various dashboard statistics.
     *
     * @return array<string, mixed>
     */
    private function getStats(
        InventoryItemRepository $itemRepository,
        LocationRepository $locationRepository,
        MovementLogRepository $logRepository
    ): array {
        $movementStats = $logRepository->getMovementStats();

        $itemsByCategory = $itemRepository->getCategoryStatisticsWithZero();

        $chartCategories = [];
        $chartCounts = [];

        foreach ($itemsByCategory as $category => $item) {
            $chartCategories[] = $category;
            $chartCounts[] = $item['count'];
        }

        return [
            'total_items'       => $itemRepository->count([]),
            'total_locations'   => $locationRepository->count([]),
            'total_movements'   => $movementStats['total_movements'] ?? 0,
            'first_movement'    => $movementStats['first_movement'] ?? null,
            'last_movement'     => $movementStats['last_movement'] ?? null,
            'items_by_category' => $itemsByCategory,
            'chart_categories'  => $chartCategories,
            'chart_counts'      => $chartCounts,
        ];
    }// end getStats()
}// end class
