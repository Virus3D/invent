<?php

declare(strict_types=1);

// src/Repository/MaterialConsumptionRepository.php
namespace App\Repository;

use App\Entity\MaterialConsumption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MaterialConsumptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaterialConsumption::class);
    }// end __construct()

    /**
     * @return MaterialConsumption[]
     */
    public function findByPeriod(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('mc')
            ->andWhere('mc.consumedAt >= :start')
            ->andWhere('mc.consumedAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('mc.consumedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }// end findByPeriod()

    /**
     * Агрегированные данные по материалам за период.
     */
    public function getAggregatedByPeriod(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('mc')
            ->select('IDENTITY(mc.material) as materialId', 'SUM(mc.quantity) as totalQuantity')
            ->andWhere('mc.consumedAt >= :start')
            ->andWhere('mc.consumedAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('mc.material')
            ->getQuery()
            ->getResult();
    }// end getAggregatedByPeriod()
}// end class
