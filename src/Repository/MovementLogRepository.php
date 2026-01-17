<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MovementLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for MovementLog entity.
 *
 * @extends ServiceEntityRepository<MovementLog>
 */
final class MovementLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementLog::class);
    }// end __construct()

    /**
     * Find movement logs by item ID.
     *
     * @return array<MovementLog>
     */
    public function findByItem(int $itemId): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.fromLocation', 'fl')
            ->leftJoin('m.toLocation', 'tl')
            ->where('m.inventoryItem = :itemId')
            ->setParameter('itemId', $itemId)
            ->orderBy('m.movedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }// end findByItem()

    /**
     * Find recent movement logs.
     *
     * @param int $limit The maximum number of results to return.
     *
     * @return array<MovementLog>
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.inventoryItem', 'i')
            ->leftJoin('m.fromLocation', 'fl')
            ->leftJoin('m.toLocation', 'tl')
            ->orderBy('m.movedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }// end findRecent()

    /**
     * Get statistics about movement logs.
     *
     * @return array{
     *     total_movements: int,
     *     first_movement: \DateTimeInterface|null,
     *     last_movement: \DateTimeInterface|null
     * }
     */
    public function getMovementStats(): array
    {
        return $this->createQueryBuilder('m')
            ->select(
                '
                COUNT(m.id) as total_movements,
                MIN(m.movedAt) as first_movement,
                MAX(m.movedAt) as last_movement
            '
            )
            ->getQuery()
            ->getSingleResult();
    }// end getMovementStats()
}// end class
