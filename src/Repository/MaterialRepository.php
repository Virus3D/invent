<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Material;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for Material entity.
 *
 * @extends ServiceEntityRepository<Material>
 */
final class MaterialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Material::class);
    }

    /**
     * Searches for materials matching the query in name or description.
     *
     * @return Material[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.name LIKE :query')
            ->orWhere('m.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds materials by location.
     *
     * @return Material[]
     */
    public function findByLocation(?int $locationId): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC');

        if ($locationId) {
            $qb->andWhere('m.location = :locationId')
                ->setParameter('locationId', $locationId);
        } else {
            $qb->andWhere('m.location IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get total quantity of all materials.
     */
    public function getTotalQuantity(): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.quantity) as total')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
