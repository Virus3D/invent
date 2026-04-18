<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\InventoryItem;
use App\Entity\Location;
use App\Entity\Material;
use App\Entity\MaterialConsumption;
use App\Entity\User;
use App\Enum\MaterialConsumptionTargetType;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;

final class MaterialWriteOffService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }// end __construct()

    /**
     * Списание материала на местоположение.
     *
     * @throws InvalidArgumentException
     */
    public function writeOff(
        Material $material,
        int $quantity,
        MaterialConsumptionTargetType $targetType,
        ?Location $location,
        ?InventoryItem $inventoryItem,
        ?User $user = null,
        ?string $comment = null,
    ): void {
        $this->validateQuantity($material, $quantity);

        $this->entityManager->beginTransaction();

        try {
            $newQuantity = $material->getQuantity() - $quantity;
            $material->setQuantity($newQuantity);

            $consumption = new MaterialConsumption();
            $consumption->setMaterial($material);
            $consumption->setQuantity($quantity);
            $consumption->setTargetType($targetType);
            $consumption->setTargetLocation($location);
            $consumption->setTargetInventoryItem($inventoryItem);
            $consumption->setConsumedBy($user);
            $consumption->setComment($comment);

            $this->entityManager->persist($consumption);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();

            throw $e;
        }// end try
    }// end writeOff()

    /**
     * Списание материала на местоположение.
     *
     * @throws InvalidArgumentException
     */
    public function writeOffToLocation(
        Material $material,
        int $quantity,
        Location $location,
        ?User $user = null,
        ?string $comment = null,
    ): void {
        $this->writeOff(
            $material,
            $quantity,
            MaterialConsumptionTargetType::LOCATION,
            $location,
            null,
            $user,
            $comment,
        );
    }// end writeOffToLocation()

    /**
     * Списание материала на инвентарный объект с возможностью обновления спецификаций.
     *
     * @throws InvalidArgumentException
     */
    public function writeOffToInventoryItem(
        Material $material,
        int $quantity,
        InventoryItem $inventoryItem,
        ?User $user = null,
        ?string $comment = null,
    ): void {
        $this->writeOff(
            $material,
            $quantity,
            MaterialConsumptionTargetType::INVENTORY_ITEM,
            null,
            $inventoryItem,
            $user,
            $comment,
        );
    }// end writeOffToInventoryItem()

    /**
     * Валидация колличества.
     *
     * @throws InvalidArgumentException
     */
    private function validateQuantity(Material $material, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }
        if ($material->getQuantity() - $quantity <= 0) {
            throw new InvalidArgumentException('Insufficient material quantity.');
        }
    }// end validateQuantity()
}// end class
