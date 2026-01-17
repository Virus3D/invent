<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MovementLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovementLogRepository::class)]
class MovementLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'movementLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?InventoryItem $inventoryItem = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $fromLocation = null;

    #[ORM\ManyToOne(inversedBy: 'movementLogs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Location $toLocation = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $movedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(length: 100)]
    private ?string $movedBy = null;

    public function __construct()
    {
        $this->movedAt = new \DateTimeImmutable();
    }// end __construct()

    /**
     * Get the ID of the movement log.
     */
    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    /**
     * Get the inventory item associated with this movement log.
     */
    public function getInventoryItem(): ?InventoryItem
    {
        return $this->inventoryItem;
    }// end getInventoryItem()

    /**
     * Set the inventory item associated with this movement log.
     */
    public function setInventoryItem(?InventoryItem $inventoryItem): static
    {
        $this->inventoryItem = $inventoryItem;
        return $this;
    }// end setInventoryItem()

    /**
     * Get the origin location.
     */
    public function getFromLocation(): ?Location
    {
        return $this->fromLocation;
    }// end getFromLocation()

    /**
     * Set the origin location.
     */
    public function setFromLocation(?Location $fromLocation): static
    {
        $this->fromLocation = $fromLocation;
        return $this;
    }// end setFromLocation()

    /**
     * Get the destination location.
     */
    public function getToLocation(): ?Location
    {
        return $this->toLocation;
    }// end getToLocation()

    /**
     * Set the destination location.
     */
    public function setToLocation(?Location $toLocation): static
    {
        $this->toLocation = $toLocation;
        return $this;
    }// end setToLocation()

    /**
     * Get the date and time when the movement occurred.
     */
    public function getMovedAt(): ?\DateTimeImmutable
    {
        return $this->movedAt;
    }// end getMovedAt()

    /**
     * Set the date and time when the movement occurred.
     */
    public function setMovedAt(\DateTimeImmutable $movedAt): static
    {
        $this->movedAt = $movedAt;
        return $this;
    }// end setMovedAt()

    /**
     * Get the reason for the movement.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }// end getReason()

    /**
     * Set the reason for the movement.
     */
    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }// end setReason()

    /**
     * Get who moved the item.
     */
    public function getMovedBy(): ?string
    {
        return $this->movedBy;
    }// end getMovedBy()

    /**
     * Set who moved the item.
     */
    public function setMovedBy(string $movedBy): static
    {
        $this->movedBy = $movedBy;
        return $this;
    }// end setMovedBy()
}// end class
