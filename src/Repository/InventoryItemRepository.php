<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InventoryItem;
use App\Entity\Location;
use App\Enum\InventoryCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for InventoryItem entity.
 *
 * @extends ServiceEntityRepository<InventoryItem>
 */
final class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }// end __construct()

    /**
     * Searches for inventory items matching the query in name, inventory number, or serial number.
     *
     * @return InventoryItem[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.name LIKE :query')
            ->orWhere('i.inventoryNumber LIKE :query')
            ->orWhere('i.serialNumber LIKE :query')
            ->orWhere('i.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('i.name', 'ASC')
            ->getQuery()
            ->getResult();
    }// end search()

    /**
     * Finds inventory items by location.
     *
     * @return InventoryItem[]
     */
    public function findByLocation(?int $locationId): array
    {
        $qb = $this->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC');

        if ($locationId) {
            $qb->andWhere('i.location = :locationId')
                ->setParameter('locationId', $locationId);
        } else {
            $qb->andWhere('i.location IS NULL');
        }

        return $qb->getQuery()->getResult();
    }// end findByLocation()

    /**
     * Finds inventory items of category 'pc' that have non-null specifications.
     *
     * @return InventoryItem[] Returns an array of InventoryItem objects
     */
    public function findWithSpecifications(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.category = :category')
            ->andWhere('i.specifications IS NOT NULL')
            ->setParameter('category', 'pc')
            ->getQuery()
            ->getResult();
    }// end findWithSpecifications()

    /**
     * Получить статистику по категориям.
     *
     * @return array<string, mixed> Массив с категориями и количеством объектов
     */
    public function getCategoryStatistics(): array
    {
        $query = $this->createQueryBuilder('i')
            ->select('i.category, COUNT(i.id) as item_count')
            ->groupBy('i.category')
            ->orderBy('item_count', 'DESC')
            ->getQuery();

        $results = $query->getResult();

        $statistics = [];
        foreach ($results as $row) {
            try {
                $category = $row['category'];
                $statistics[$category->value] = [
                    'category' => $category,
                    'count'    => (int) $row['item_count'],
                ];
            } catch (\ValueError $e) {
                // Пропускаем некорректные категории.
                continue;
            }
        }

        return $statistics;
    }// end getCategoryStatistics()

    /**
     * Получить количество объектов по категориям с нулевыми значениями.
     *
     * @return array<string, mixed> Все категории с количеством (включая 0)
     */
    public function getCategoryStatisticsWithZero(): array
    {
        $statistics = $this->getCategoryStatistics();
        $allCategories = InventoryCategory::cases();

        $fullStatistics = [];
        foreach ($allCategories as $category) {
            $categoryCode = $category->value;

            if (isset($statistics[$categoryCode])) {
                $fullStatistics[$categoryCode] = $statistics[$categoryCode];
            } else {
                $fullStatistics[$categoryCode] = [
                    'category' => $category,
                    'count'    => 0,
                ];
            }
        }

        return $fullStatistics;
    }// end getCategoryStatisticsWithZero()

    /**
     * Получить общее количество объектов по категории.
     */
    public function countByCategory(InventoryCategory $category): int
    {
        return $this->count(['category' => $category]);
    }// end countByCategory()

    /**
     * Получить объекты определенной категории.
     *
     * @return InventoryItem[]
     */
    public function findByCategory(InventoryCategory $category, ?int $limit = null): array
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->andWhere('i.category = :category')
            ->setParameter('category', $category)
            ->orderBy('i.name', 'ASC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }// end findByCategory()

    /**
     * Finds locations by given Location entity and optional filters.
     *
     * @param Location             $location The Location entity to filter by.
     * @param array<string, mixed> $filters  Optional associative array of additional filters:
     *      - name: string (partial match)
     *      - category: string (exact match)
     *      - status: string (exact match)
     *
     * @return array<int, Location> Returns an array of Location objects.
     */
    public function findByLocationAndFilters(Location $location, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.location = :location')
            ->setParameter('location', $location)
            ->orderBy('i.name', 'ASC');

        if (!empty($filters['name'])) {
            $qb->andWhere('i.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('i.category = :category')
                ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('i.status = :status')
                ->setParameter('status', $filters['status']);
        }

        return $qb->getQuery()->getResult();
    }// end findByLocationAndFilters()

    /**
     * Advanced search with multiple filters and sorting.
     *
     * @param array<string, mixed> $criteria  Search criteria:
     *       - query: string (search in name, inventoryNumber, serialNumber, description)
     *       - category: InventoryCategory|string|null
     *       - location: Location|int|null (entity or ID)
     *       - hasLocation: bool|null (true = with location, false = without location)
     *       - hasSerial: bool|null (true = serialNumber IS NOT NULL)
     *       - hasSpecifications: bool|null (true = specifications IS NOT NULL AND category = 'pc')
     *       - status: string|null (exact match on i.status field, if needed)
     *       - balanceType: BalanceType|string|null (exact match)
     * @param string|null          $sort      Field name (e.g., 'name', 'inventoryNumber', 'createdAt', 'category')
     * @param string               $direction 'ASC' or 'DESC'
     *
     * @return InventoryItem[]
     */
    public function searchByCriteria(array $criteria = [], ?string $sort = null, string $direction = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC');

        // Text search.
        if (!empty($criteria['query'])) {
            $qb->andWhere(
                'i.name LIKE :query OR
                i.inventoryNumber LIKE :query OR
                i.serialNumber LIKE :query OR
                i.description LIKE :query'
            )
                ->setParameter('query', '%' . $criteria['query'] . '%');
        }

        // Category filter.
        if (!empty($criteria['category'])) {
            $qb->andWhere('i.category = :category')
                ->setParameter('category', $criteria['category']);
        }

        // Location filter (by entity or ID).
        if (!empty($criteria['location'])) {
            $qb->andWhere('i.location = :location')
                ->setParameter('location', $criteria['location']);
        }

        // Location presence filter.
        if (array_key_exists('hasLocation', $criteria) && $criteria['hasLocation'] !== null) {
            if ($criteria['hasLocation']) {
                $qb->andWhere('i.location IS NOT NULL');
            } else {
                $qb->andWhere('i.location IS NULL');
            }
        }

        // Serial number presence.
        if (array_key_exists('hasSerial', $criteria) && $criteria['hasSerial']) {
            $qb->andWhere('i.serialNumber IS NOT NULL');
        }

        // Specifications presence.
        if (array_key_exists('hasSpecifications', $criteria) && $criteria['hasSpecifications']) {
            $qb->andWhere('i.specifications IS NOT NULL');
        }

        // Exact status match (if needed).
        if (!empty($criteria['status'])) {
            $qb->andWhere('i.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        // Balance type filter.
        if (!empty($criteria['balanceType'])) {
            $qb->andWhere('i.balanceType = :balanceType')
                ->setParameter('balanceType', $criteria['balanceType']);
        }

        // Sorting.
        $allowedSortFields = [
            'name',
            'inventoryNumber',
            'createdAt',
            'category',
        ];
        if ($sort && in_array($sort, $allowedSortFields, true)) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $qb->orderBy('i.' . $sort, $direction);
        }

        return $qb->getQuery()->getResult();
    }// end searchByCriteria()
}// end class
