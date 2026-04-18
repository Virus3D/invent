<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MaterialConsumptionTargetType;
use App\Repository\MaterialConsumptionRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaterialConsumptionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MaterialConsumption
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(inversedBy: 'consumptions')]
    private ?Material $material = null;

    #[Assert\Positive]
    #[ORM\Column(type: Types::INTEGER)]
    private int $quantity;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: MaterialConsumptionTargetType::class)]
    private MaterialConsumptionTargetType $targetType;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(inversedBy: 'materialConsumptions')]
    private ?Location $targetLocation = null;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne(inversedBy: 'materialConsumptions')]
    private ?InventoryItem $targetInventoryItem = null;

    #[ORM\JoinColumn(nullable: true)]
    #[ORM\ManyToOne]
    private ?User $consumedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $consumedAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->consumedAt = new DateTimeImmutable();
    }// end __construct()

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }// end setCreatedAtValue()

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }// end setUpdatedAtValue()

    public function getId(): int
    {
        return $this->id;
    }// end getId()

    public function setMaterial(Material $material): void
    {
        $this->material = $material;
    }// end setMaterial()

    public function getMaterial(): Material
    {
        return $this->material;
    }// end getMaterial()

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }// end setQuantity()

    public function getQuantity(): int
    {
        return $this->quantity;
    }// end getQuantity()

    public function setTargetType(MaterialConsumptionTargetType $targetType): void
    {
        $this->targetType = $targetType;
    }// end setTargetType()

    public function getTargetType(): MaterialConsumptionTargetType
    {
        return $this->targetType;
    }// end getTargetType()

    public function setTargetLocation(?Location $targetLocation = null): void
    {
        $this->targetLocation = $targetLocation;
    }// end setTargetLocation()

    public function getTargetLocation(): ?Location
    {
        return $this->targetLocation;
    }// end getTargetLocation()

    public function setTargetInventoryItem(?InventoryItem $targetInventoryItem = null): void
    {
        $this->targetInventoryItem = $targetInventoryItem;
    }// end setTargetInventoryItem()

    public function getTargetInventoryItem(): ?InventoryItem
    {
        return $this->targetInventoryItem;
    }// end getTargetInventoryItem()

    public function setConsumedBy(?User $consumedBy = null): void
    {
        $this->consumedBy = $consumedBy;
    }// end setConsumedBy()

    public function getConsumedBy(): ?User
    {
        return $this->consumedBy;
    }// end getConsumedBy()

    public function setConsumedAt(DateTimeImmutable $consumedAt): void
    {
        $this->consumedAt = $consumedAt;
    }// end setConsumedAt()

    public function getConsumedAt(): DateTimeImmutable
    {
        return $this->consumedAt;
    }// end getConsumedAt()

    public function setComment(?string $comment = null): void
    {
        $this->comment = $comment;
    }// end setComment()

    public function getComment(): ?string
    {
        return $this->comment;
    }// end getComment()

    public function getCreateAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }// end getCreateAt()

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }// end getUpdatedAt()
}// end class
