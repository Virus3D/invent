<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InventoryItem;
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
}// end class
