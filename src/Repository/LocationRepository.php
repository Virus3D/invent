<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for Location entity.
 *
 * @extends ServiceEntityRepository<Location>
 */
final class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }// end __construct()

    /**
     * Возвращает все локации, отсортированные по roomNumber, затем по name.
     *
     * @return Location[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.roomNumber', 'ASC')
            ->addOrderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }// end findAllOrdered()

    /**
     * Переопределяем findAll(), чтобы всегда использовать сортировку.
     *
     * @return Location[]
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
     * @return Location[]
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        if (null === $orderBy) {
            $orderBy = [
                'roomNumber' => 'ASC',
                'name'       => 'ASC',
            ];
        }

        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }// end findBy()

    /**
     * Находит одну локацию с возможностью задать сортировку (обычно не нужно, но для единообразия).
     *
     * @param array<mixed>      $criteria
     * @param array<mixed>|null $orderBy
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        if (null === $orderBy) {
            $orderBy = [
                'roomNumber' => 'ASC',
                'name'       => 'ASC',
            ];
        }

        return parent::findOneBy($criteria, $orderBy);
    }// end findOneBy()
}// end class
