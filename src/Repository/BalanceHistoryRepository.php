<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BalanceHistory;
use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BalanceHistory>
 */
class BalanceHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BalanceHistory::class);
    }// end __construct()

    public function save(BalanceHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }// end save()

    public function remove(BalanceHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }// end remove()

    public function findByInventoryItem(InventoryItem $inventoryItem, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('bh')
            ->where('bh.inventoryItem = :inventoryItem')
            ->setParameter('inventoryItem', $inventoryItem)
            ->orderBy('bh.changedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }// end findByInventoryItem()

    public function getRecentBalanceChanges(int $limit = 10): array
    {
        return $this->createQueryBuilder('bh')
            ->leftJoin('bh.inventoryItem', 'i')
            ->addSelect('i')
            ->orderBy('bh.changedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }// end getRecentBalanceChanges()

    public function getBalanceChangeStats(): array
    {
        $qb = $this->createQueryBuilder('bh')
            ->select(
                [
                    'COUNT(bh.id) as total_changes',
                    'SUM(CASE WHEN bh.previousBalanceStatus = :onBalance AND bh.newBalanceStatus = :offBalance THEN 1 ELSE 0 END) as to_off_balance',
                    'SUM(CASE WHEN bh.previousBalanceStatus = :offBalance AND bh.newBalanceStatus = :onBalance THEN 1 ELSE 0 END) as to_on_balance',
                ]
            )
            ->setParameter('onBalance', 'on_balance')
            ->setParameter('offBalance', 'off_balance');

        return $qb->getQuery()->getSingleResult();
    }// end getBalanceChangeStats()
}// end class
