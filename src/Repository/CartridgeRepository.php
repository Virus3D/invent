<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Cartridge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for Cartridge entity.
 *
 * @extends ServiceEntityRepository<Cartridge>
 */
final class CartridgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cartridge::class);
    }// end __construct()
}// end class
