<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Cartridge;
use App\Entity\CartridgeInstallation;
use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartridgeInstallationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartridgeInstallation::class);
    }// end __construct()

    public function findActiveForPrinter(InventoryItem $printer): ?CartridgeInstallation
    {
        return $this->createQueryBuilder('i')
            ->where('i.printer = :printer')
            ->andWhere('i.removedAt IS NULL')
            ->setParameter('printer', $printer)
            ->orderBy('i.installedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }// end findActiveForPrinter()

    /**
     * История завершенных установок для прогнозирования
     */
    public function findCompletedHistory(
        InventoryItem $printer,
        ?Cartridge $cartridge = null,
        int $limit = 5
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->where('i.printer = :printer')
            ->andWhere('i.removedAt IS NOT NULL')
            ->setParameter('printer', $printer)
            ->orderBy('i.removedAt', 'DESC')
            ->setMaxResults($limit);

        if ($cartridge) {
            $qb->andWhere('i.cartridge = :cartridge')
                ->setParameter('cartridge', $cartridge);
        }

        return $qb->getQuery()->getResult();
    }// end findCompletedHistory()

    /**
     * Статистика использования картриджа за период
     */
    public function getUsageStats(
        Cartridge $cartridge,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        $qb = $this->createQueryBuilder('i');
        return [
            'installations_count'   => (clone $qb)
                ->select('COUNT(i.id)')
                ->where('i.cartridge = :c')
                ->andWhere('i.installedAt BETWEEN :from AND :to')
                ->setParameter('c', $cartridge)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->getQuery()
                ->getSingleScalarResult(),

            'total_pages'           => (clone $qb)
                ->select('COALESCE(SUM(i.printedPages), 0)')
                ->where('i.cartridge = :c')
                ->andWhere('i.installedAt BETWEEN :from AND :to')
                ->setParameter('c', $cartridge)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->getQuery()
                ->getSingleScalarResult(),

            'avg_pages_per_install' => (clone $qb)
                ->select('AVG(i.printedPages)')
                ->where('i.cartridge = :c')
                ->andWhere('i.installedAt BETWEEN :from AND :to')
                ->andWhere('i.printedPages IS NOT NULL')
                ->setParameter('c', $cartridge)
                ->setParameter('from', $from)
                ->setParameter('to', $to)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }// end getUsageStats()

    /**
     * Картриджи, установленные сейчас на принтерах (для отчета)
     */
    public function findCurrentlyInstalled(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.removedAt IS NULL')
            ->join('i.printer', 'p')
            ->addSelect('p')
            ->join('i.cartridge', 'c')
            ->addSelect('c')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }// end findCurrentlyInstalled()
}// end class
