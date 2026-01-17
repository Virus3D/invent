<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    private ?string $roomNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: InventoryItem::class)]
    private Collection $inventoryItems;

    #[ORM\OneToMany(mappedBy: 'toLocation', targetEntity: MovementLog::class)]
    private Collection $movementLogs;

    public function __construct()
    {
        $this->inventoryItems = new ArrayCollection();
        $this->movementLogs = new ArrayCollection();
    }// end __construct()

    /**
     * Get the ID of the location.
     */
    public function getId(): ?int
    {
        return $this->id;
    }// end getId()

    /**
     * Get the name for the location.
     */
    public function getName(): ?string
    {
        return $this->name;
    }// end getName()

    /**
     * Set the name for the location.
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }// end setName()

    /**
     * Get the room number for the location.
     */
    public function getRoomNumber(): ?string
    {
        return $this->roomNumber;
    }// end getRoomNumber()

    /**
     * Set the room number for the location.
     */
    public function setRoomNumber(string $roomNumber): static
    {
        $this->roomNumber = $roomNumber;
        return $this;
    }// end setRoomNumber()

    /**
     * Get the description for the location.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }// end getDescription()

    /**
     * Set the description for the location.
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }// end setDescription()

    /**
     * Get all inventory items associated with this location.
     *
     * @return Collection<int, InventoryItem>
     */
    public function getInventoryItems(): Collection
    {
        return $this->inventoryItems;
    }// end getInventoryItems()

    /**
     * Get all movement logs associated with this location.
     *
     * @return Collection<int, MovementLog>
     */
    public function getMovementLogs(): Collection
    {
        return $this->movementLogs;
    }// end getMovementLogs()

    /**
     * Returns the string representation of the location.
     */
    public function __toString(): string
    {
        return sprintf('%s (каб. %s)', $this->name, $this->roomNumber);
    }// end __toString()
}// end class
