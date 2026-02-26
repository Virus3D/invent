<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SoftwareLicense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SoftwareLicense>
 */
final class SoftwareLicenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoftwareLicense::class);
    }

    /**
     * Найти лицензии по строке поиска (имя, ключ).
     *
     * @return SoftwareLicense[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.name LIKE :query')
            ->orWhere('l.licenseKey LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

