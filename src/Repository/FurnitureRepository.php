<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Furniture;
use App\Entity\Location;
use App\Enum\FurnitureCategory;
use App\Enum\ItemStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for Furniture entity.
 *
 * @extends ServiceEntityRepository<Furniture>
 */
final class FurnitureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Furniture::class);
    }// end __construct()

    /**
     * Возвращает всю мебель, отсортированную по категории, затем по name.
     *
     * @return Furniture[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.location', 'l')
            ->addSelect('l')
            ->orderBy('f.category', 'ASC')
            ->addOrderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }// end findAllOrdered()

    /**
     * Переопределяем findAll(), чтобы всегда использовать сортировку.
     *
     * @return Furniture[]
     */
    public function findAll(): array
    {
        return $this->findAllOrdered();
    }// end findAll()

    /**
     * Переопределяем findBy() с автоматической сортировкой по умолчанию.
     *
     * @param array<mixed>      $criteria
     * @param array<mixed>|null $orderBy
     *
     * @return Furniture[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if (null === $orderBy) {
            $orderBy = [
                'category' => 'ASC',
                'name'     => 'ASC',
            ];
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }// end findBy()

    /**
     * Находит один предмет мебели с возможностью задать сортировку (обычно не нужно, но для единообразия).
     *
     * @param array<mixed>      $criteria
     * @param array<mixed>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?Furniture
    {
        if (null === $orderBy) {
            $orderBy = [
                'category' => 'ASC',
                'name'     => 'ASC',
            ];
        }

        return parent::findOneBy($criteria, $orderBy);
    }// end findOneBy()

    /**
     * Возвращает статистику по категориям мебели.
     *
     * @return array<string, array{count: int}>
     */
    public function getCategoryStatistics(): array
    {
        $result = $this->createQueryBuilder('f')
            ->select('f.category as category', 'COUNT(f.id) as count')
            ->groupBy('f.category')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($result as $item) {
            $stats[$item['category']] = ['count' => (int) $item['count']];
        }

        return $stats;
    }// end getCategoryStatistics()

    /**
     * Возвращает общую стоимость мебели.
     */
    public function getTotalValue(): float
    {
        $result = $this->createQueryBuilder('f')
            ->select('COALESCE(SUM(f.purchasePrice), 0) as total')
            ->getQuery()
            ->getSingleResult();

        return (float) $result['total'];
    }// end getTotalValue()

    /**
     * Возвращает мебель без местоположения.
     *
     * @return Furniture[]
     */
    public function findByLocation(?Location $location): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.location', 'l')
            ->where('l.id IS NULL')
            ->orWhere('l = :null')
            ->setParameter('null', null, 'integer')
            ->getQuery()
            ->getResult();
    }// end findByLocation()

    /**
     * Получить с фильтром.
     *
     * @return Furniture[]
     */
    public function findFiltered(
        Location $location,
        ?string $search,
        ?FurnitureCategory $category,
        ?ItemStatus $status,
        string $sortField,
        string $sortDirection,
    ): array {
        $allowedSort = [
            'inventoryNumber',
            'status',
            'checked',
            'createdAt',
            'category',
        ];
        if (!\in_array($sortField, $allowedSort, true)) {
            $sortField = 'createdAt';
        }
        $direction = $sortDirection === 'asc' ? 'asc' : 'desc';

        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.location = :location')
            ->setParameter('location', $location)
            ->orderBy('i.' . $sortField, $direction);

        if ($search !== null && $search !== '') {
            $qb->andWhere('i.inventoryNumber LIKE :q OR i.serialNumber LIKE :q OR i.name LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }
        if ($category !== null) {
            $qb->andWhere('i.category = :category')->setParameter('category', $category);
        }
        if ($status !== null) {
            $qb->andWhere('i.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }// end findFiltered()
}// end class
